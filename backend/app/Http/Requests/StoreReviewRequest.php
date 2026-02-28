<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'author_name' => is_string($this->author_name) ? trim($this->author_name) : $this->author_name,
            'text' => is_string($this->text) ? trim($this->text) : $this->text,
            'author_contacts' => is_string($this->author_contacts) ? trim($this->author_contacts) : $this->author_contacts,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'author_name' => ['required', 'string', 'min:2', 'max:120'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'text' => ['required', 'string', 'min:10', 'max:4000'],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'doctor_name' => ['nullable', 'string', 'max:255'],
            'author_contacts' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'max:0'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'author_name.required' => 'Укажите имя.',
            'author_name.min' => 'Имя должно содержать минимум 2 символа.',
            'rating.required' => 'Укажите оценку.',
            'rating.min' => 'Оценка должна быть от 1 до 5.',
            'rating.max' => 'Оценка должна быть от 1 до 5.',
            'text.required' => 'Введите текст отзыва.',
            'text.min' => 'Текст отзыва должен содержать минимум 10 символов.',
            'doctor_id.exists' => 'Выбранный врач не найден.',
        ];
    }
}
