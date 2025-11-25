<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            'name'       => 'required|string|max:120',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:8',
            'role' => ['required', 'integer', 'exists:roles,id'],
            // roles can be role IDs or titles; controller will normalize
            'roles'      => 'array',
            'roles.*'    => 'string', // id or title as string is fine; we’ll coerce in controller
        ];
    }
}
