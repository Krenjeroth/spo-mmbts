<?php

namespace App\Http\Requests;

use App\Services\CurrentEventService;
use Illuminate\Foundation\Http\FormRequest;

class StoreCriterionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // return $this->user()?->hasPermission('manage_criteria') ?? false;
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
            'category_id' => 'required|exists:categories,id',
            'parent_id' => 'nullable|exists:criteria,id',
            'name' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:0|max:100',
            'order' => 'nullable|integer|min:1',
        ];
    }

    protected function prepareForValidation(): void {
        $this->merge([
            'event_id' => CurrentEventService::getId(),
        ]);
    }
}
