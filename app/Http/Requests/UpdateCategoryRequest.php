<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // return $this->user()?->hasPermission('manage_categories') ?? false;
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
            'phase_id' => 'required|exists:phases,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0|max:100',
            'order' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ];
    }
}
