<?php

namespace App\Http\Requests;

use App\Services\CurrentEventService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateScoreRequest extends FormRequest
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
            'event_id' => 'required|exists:pageant_events,id',
            // 'judge_id' => 'required|exists:judges,id',
            'contestant_id' => 'required|exists:contestants,id',
            // 'category_id' => 'required|exists:categories,id',
            'criterion_id' => 'required|exists:criteria,id',
            'score' => 'required|numeric|min:80|max:100',
        ];
    }
    
    protected function prepareForValidation(): void {
        $this->merge([
            'event_id' => $this->event_id ?? CurrentEventService::getId(),
        ]);
    }
}
