<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CaptchaVerifier
{
    public function isRequired(): bool
    {
        return (bool) config('services.hcaptcha.required', false);
    }

    public function isConfigured(): bool
    {
        return filled(config('services.hcaptcha.site_key'))
            && filled(config('services.hcaptcha.secret'));
    }

    public function verify(?string $token, ?string $ip): bool
    {
        if (! $this->isRequired()) {
            return true;
        }

        if (! $this->isConfigured()) {
            Log::error('hCaptcha is required but not configured');

            return false;
        }

        if (blank($token)) {
            return false;
        }

        try {
            $payload = [
                'secret' => (string) config('services.hcaptcha.secret'),
                'response' => $token,
            ];

            $response = Http::asForm()
                ->timeout(10)
                ->post(
                    (string) config('services.hcaptcha.verify_url', 'https://hcaptcha.com/siteverify'),
                    $payload
                );

            if (! $response->ok()) {
                Log::warning('hCaptcha verification HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            $json = $response->json();
            $success = (bool) data_get($json, 'success', false);

            if (! $success) {
                Log::warning('hCaptcha verification rejected', [
                    'error_codes' => data_get($json, 'error-codes', []),
                    'hostname' => data_get($json, 'hostname'),
                    'challenge_ts' => data_get($json, 'challenge_ts'),
                    'ip_present' => filled($ip),
                ]);
            }

            return $success;
        } catch (\Throwable $e) {
            Log::warning('hCaptcha verification failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
