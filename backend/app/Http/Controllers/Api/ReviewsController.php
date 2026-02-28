<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Models\Doctor;
use App\Models\Review;
use App\Support\CaptchaVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ReviewsController extends Controller
{
    public function __construct(
        private readonly CaptchaVerifier $captchaVerifier
    ) {
    }

    public function index(): JsonResponse
    {
        $reviews = Review::query()
            ->published()
            ->with('doctor:id,full_name')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get()
            ->map(static fn (Review $review): array => [
                'id' => $review->id,
                'author_name' => $review->author_name,
                'doctor_name' => optional($review->doctor)->full_name,
                'rating' => $review->rating,
                'text' => $review->text,
                'published_at' => optional($review->published_at)?->toDateString(),
            ])
            ->values();

        return response()->json($reviews);
    }

    public function store(StoreReviewRequest $request): JsonResponse
    {
        if (filled($request->input('website'))) {
            Log::notice('Reviews honeypot triggered', ['ip' => $request->ip()]);

            return response()->json([
                'status' => 'ok',
                'message' => 'Отзыв принят на модерацию',
            ]);
        }

        if (! $this->passesCaptcha($request)) {
            return response()->json([
                'status' => 'error',
                'message' => $this->captchaVerifier->isConfigured()
                    ? 'Подтвердите, что вы не робот.'
                    : 'Капча временно недоступна. Обратитесь к администратору.',
            ], $this->captchaVerifier->isConfigured() ? 422 : 503);
        }

        $doctorId = $request->input('doctor_id');
        if (! $doctorId && filled($request->input('doctor_name'))) {
            $doctorId = Doctor::query()
                ->where('full_name', $request->string('doctor_name')->trim()->toString())
                ->value('id');
        }

        $review = $this->createDraftReview(
            authorName: $request->string('author_name')->trim()->toString(),
            rating: (int) $request->input('rating'),
            text: $request->string('text')->trim()->toString(),
            doctorId: $doctorId,
            authorContacts: $request->input('author_contacts'),
            meta: [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        $this->notifyAdmin($review);

        return response()->json([
            'status' => 'ok',
            'message' => 'Отзыв принят на модерацию',
        ]);
    }

    public function legacyStore(Request $request): Response
    {
        if (filled($request->input('antispam'))) {
            Log::notice('Legacy reviews honeypot triggered', ['ip' => $request->ip()]);

            return response('Обнаружена спам-активность', 400, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        if (! $this->passesCaptcha($request)) {
            return response(
                $this->captchaVerifier->isConfigured()
                    ? 'Подтвердите, что вы не робот.'
                    : 'Капча временно недоступна. Обратитесь к администратору.',
                $this->captchaVerifier->isConfigured() ? 422 : 503,
                ['Content-Type' => 'text/plain; charset=UTF-8']
            );
        }

        $validator = Validator::make($request->all(), [
            'userName' => ['required', 'string', 'min:2', 'max:120'],
            'doctorSelect' => ['nullable', 'string', 'max:255'],
            'reviewRating' => ['required', 'integer', 'min:1', 'max:5'],
            'reviewText' => ['required', 'string', 'min:10', 'max:4000'],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $message = 'Проверьте корректность заполнения формы.';

            if ($errors->has('userName')) {
                $message = 'Имя должно содержать минимум 2 символа.';
            } elseif ($errors->has('doctorSelect')) {
                $message = 'Пожалуйста, выберите корректного врача.';
            } elseif ($errors->has('reviewRating')) {
                $message = 'Некорректная оценка (допустимо от 1 до 5).';
            } elseif ($errors->has('reviewText')) {
                $message = 'Текст отзыва должен содержать минимум 10 символов.';
            }

            return response($message, 422, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        $data = $validator->validated();

        $doctorId = null;
        if (! empty($data['doctorSelect'])) {
            $doctorId = Doctor::query()->where('full_name', $data['doctorSelect'])->value('id');
        }

        $review = $this->createDraftReview(
            authorName: trim((string) $data['userName']),
            rating: (int) $data['reviewRating'],
            text: trim((string) $data['reviewText']),
            doctorId: $doctorId,
            authorContacts: null,
            meta: [
                'legacy' => true,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        $this->notifyAdmin($review);

        return response('Спасибо! Отзыв принят на модерацию.', 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    private function passesCaptcha(Request $request): bool
    {
        $candidates = [
            'h-captcha-response',
            'h_captcha_response',
            'captcha_token',
            'g-recaptcha-response',
            'g_recaptcha_response',
        ];

        $token = null;
        foreach ($candidates as $key) {
            $value = $request->input($key);
            if (is_string($value) && trim($value) !== '') {
                $token = trim($value);
                break;
            }
        }

        if ($token === null) {
            Log::warning('Review captcha token missing', [
                'ip' => $request->ip(),
                'keys' => array_keys($request->all()),
            ]);

            return false;
        }

        $isValid = $this->captchaVerifier->verify($token, $request->ip());
        if (! $isValid) {
            Log::warning('Review captcha verification failed', [
                'ip' => $request->ip(),
                'token_length' => mb_strlen($token),
            ]);
        }

        return $isValid;
    }

    private function createDraftReview(
        string $authorName,
        int $rating,
        string $text,
        ?int $doctorId,
        ?string $authorContacts,
        array $meta
    ): Review {
        return Review::query()->create([
            'author_name' => $authorName,
            'rating' => $rating,
            'text' => $text,
            'source' => 'form',
            'status' => 'draft',
            'doctor_id' => $doctorId,
            'author_contacts' => $authorContacts,
            'meta' => $meta,
        ]);
    }

    private function notifyAdmin(Review $review): void
    {
        $recipient = env('REVIEWS_NOTIFY_EMAIL') ?: config('mail.from.address');

        if (! filled($recipient)) {
            return;
        }

        $doctorName = optional($review->doctor)->full_name ?? 'Не указан';
        $body = "Новый отзыв с сайта\n\n"
            . "Автор: {$review->author_name}\n"
            . "Оценка: {$review->rating}\n"
            . "Врач: {$doctorName}\n"
            . "Текст:\n{$review->text}\n";

        try {
            Mail::raw($body, static function ($message) use ($recipient): void {
                $message
                    ->to($recipient)
                    ->subject('FDC: новый отзыв на модерацию');
            });
        } catch (Throwable $e) {
            Log::warning('Failed to send review notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
