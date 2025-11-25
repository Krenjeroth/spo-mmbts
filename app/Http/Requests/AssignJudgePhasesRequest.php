<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignJudgePhasesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phase_ids'   => 'required|array',
            'phase_ids.*' => 'integer|exists:phases,id',
        ];
    }
}
