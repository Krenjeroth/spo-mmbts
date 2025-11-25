<?php

namespace App\Http\Requests;

use App\Services\CurrentEventService;
use Illuminate\Foundation\Http\FormRequest;

class StoreJudgeRequest extends FormRequest
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
            'user_id'    => ['nullable', 'exists:users,id'],

            'create_user'    => ['sometimes', 'boolean'],
            'user_name'      => ['required_if:create_user,1', 'string', 'max:255'],
            'user_email'     => ['required_if:create_user,1', 'email', 'max:255', 'unique:users,email'],
            'user_password'  => ['required_if:create_user,1', 'string', 'min:8'],

            'category_assignment' => ['nullable', 'string', 'max:255'],
            'judge_number'        => ['nullable', 'integer'],
            'is_active'           => ['sometimes', 'boolean'],

            'phase_ids'           => ['sometimes', 'array'],
            'phase_ids.*'         => ['integer', 'exists:phases,id'],
        ];
    }

    /**
     * Modify data before validation.
     */
    protected function prepareForValidation(): void {
        // Automatically attach current active event if none provided
        $this->merge([
            'event_id' => $this->event_id ?? CurrentEventService::getId(),
        ]);
    }
}
