<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // return $this->user()?->hasPermission('manage_users') ?? false;
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
            'name'     => 'sometimes|required|string|max:120',
            'email'    => ['sometimes','required','email', Rule::unique('users','email')->ignore($this->user?->id ?? $this->route('user')?->id)],
            // change password via dedicated endpoint, but allow here if provided
            'password' => 'sometimes|required|string|min:8',
            'role' => ['required', 'integer', 'exists:roles,id'],
            // 'roles'    => 'sometimes|array',
            // 'roles.*'  => 'string',
        ];
    }
}
