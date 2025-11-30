<?php

namespace App\Http\Requests\Recipe;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecipeRequest extends FormRequest
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
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['nullable', 'string', 'max:45'],
            'prep_time_minutes' => ['nullable', 'integer', 'min:0'],
            'servings' => ['nullable', 'integer', 'min:1'],
            'instructions' => ['required', 'string'],
            'ingredients' => ['nullable', 'string'],
        ];
    }
}

