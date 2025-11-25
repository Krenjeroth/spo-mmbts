<?php

namespace App\Http\Requests;

use App\Models\Judge;
use App\Models\Criterion;
use Illuminate\Support\Facades\Auth;
use App\Services\CurrentEventService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBulkScoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        $eventId = $this->input('event_id') ?? CurrentEventService::getId();

        if (!$user) return false;
        if (method_exists($user, 'hasRole') && $user->hasRole('Admin')) return true;

        return Judge::where('user_id', $user->id)
            ->where('event_id', $eventId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $eventId = $this->input('event_id');

        return [
            'event_id' => ['required', 'exists:pageant_events,id'],
            'scores' => ['required', 'array', 'min:1'],

            'scores.*.contestant_id'   => [
                'required',
                Rule::exists('contestants', 'id')->where(fn ($q) =>
                    $q->where('event_id', $eventId)
                ),
            ],
            'scores.*.criterion_id'    => [
                'required',
                Rule::exists('criteria', 'id')->where(function ($q) use ($eventId) {
                    $q->whereIn('category_id', function ($sub) use ($eventId) {
                        $sub->select('id')
                            ->from('categories')
                            ->whereIn('phase_id', function ($sub2) use ($eventId) {
                                $sub2->select('id')
                                    ->from('phases')
                                    ->where('event_id', $eventId);
                            });
                    });
                }),
            ],
            // 'scores.*.score'           => ['required', 'numeric', 'between:80,100'],
            'scores.*.score'           => ['required', 'numeric', 'between:60,100'],
        ];
    }

    public function messages(): array {
        return [
            'scores.*.criterion_id.exists'  => 'One or more criteria are not part of the current event.',
            'scores.*.contestant_id.exists' => 'One or more contestants are not part of the current event.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'event_id' => $this->event_id ?? CurrentEventService::getId(),
        ]);

        // scrub client-sent fields we don’t accept
        $scores = collect($this->input('scores', []))
            ->map(function ($row) {
                unset($row['category_id'], $row['judge_id'], $row['weighted_score']);
                if (isset($row['score'])) $row['score'] = (float) $row['score'];
                return $row;
            })
            ->all();

        $this->merge(['scores' => $scores]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $rows = collect($this->input('scores', []));
            if ($rows->isEmpty()) return;

            // Disallow parent criteria in bulk
            $criterionIds = $rows->pluck('criterion_id')->filter()->unique()->all();
            $criteria = Criterion::with('children')->whereIn('id', $criterionIds)->get()->keyBy('id');

            foreach ($rows as $idx => $row) {
                $crit = $criteria->get((int)($row['criterion_id'] ?? 0));
                if ($crit && $crit->children && $crit->children->count() > 0) {
                    $v->errors()->add("scores.$idx.criterion_id", 'Only leaf sub-criteria can be scored.');
                }
            }
        });
    }
}
