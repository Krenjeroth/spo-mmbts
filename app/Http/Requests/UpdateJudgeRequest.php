<?php

namespace App\Http\Requests;

use App\Services\CurrentEventService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJudgeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // return $this->user()?->hasPermission('manage_judges') ?? false;
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
            'category_assignment' => 'sometimes|nullable|string|max:120',
            'judge_number'        => 'sometimes|nullable|integer|min:1',
            'is_active'           => 'sometimes|boolean',
        ];
    }

    /**
     * Automatically fill event_id if not provided.
     */
    protected function prepareForValidation(): void {
        $this->merge([
            'event_id' => $this->event_id ?? CurrentEventService::getId(),
        ]);
    }
}
