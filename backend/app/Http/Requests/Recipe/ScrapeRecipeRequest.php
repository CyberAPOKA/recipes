<?php

namespace App\Http\Requests\Recipe;

use Illuminate\Foundation\Http\FormRequest;

class ScrapeRecipeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'url', function ($attribute, $value, $fail) {
                if (!str_contains($value, 'tudogostoso.com.br')) {
                    $fail('A URL deve ser do site TudoGostoso.');
                }
            }],
        ];
    }
}

