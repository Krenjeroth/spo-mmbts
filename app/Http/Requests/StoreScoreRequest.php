<?php

namespace App\Http\Requests;

use App\Models\Judge;
use App\Models\Criterion;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\CurrentEventService;
use Illuminate\Foundation\Http\FormRequest;

class StoreScoreRequest extends FormRequest
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

        // Must be an active judge for the event
        $judge = Judge::with('phases:id')
            ->where('user_id', $user->id)
            ->where('event_id', $eventId)
            ->where('is_active', true)
            ->first();

        if (!$judge) return false;

        // If criterion already in input, ensure it belongs to a phase assigned to the judge
        $criterionId = $this->input('criterion_id');
        if ($criterionId) {
            $phaseId = DB::table('criteria')
                ->join('categories', 'categories.id', '=', 'criteria.category_id')
                ->where('criteria.id', $criterionId)
                ->value('categories.phase_id');

            if ($phaseId && !$judge->phases->pluck('id')->contains($phaseId)) {
                return false;
            }
        }

        return true;
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
            'event_id'      => ['required', 'exists:pageant_events,id'],
            'contestant_id' => [
                'required',
                Rule::exists('contestants', 'id')->where(fn ($q) =>
                    $q->where('event_id', $eventId)
                ),
            ],
            'criterion_id'  => [
                'required',
                Rule::exists('criteria', 'id')->where(function ($q) use ($eventId) {
                    // Constrain to categories that belong to phases in this event
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
            // Keep your 80–100 rubric here (or replace with per-criterion bounds later)
            'score'         => ['required', 'numeric', 'between:80,100'],
        ];
    }

    public function messages(): array {
        return [
            'criterion_id.exists'  => 'Selected criterion is not part of the current event.',
            'contestant_id.exists' => 'Selected contestant is not part of the current event.',
        ];
    }

    protected function prepareForValidation(): void {
        $this->merge([
            'event_id' => $this->event_id ?? CurrentEventService::getId(),
        ]);

        // Harden client payload (we don’t accept category_id/judge_id here)
        $this->request->remove('category_id');
        $this->request->remove('judge_id');
        $this->request->remove('weighted_score');

        // normalize score to float
        if ($this->has('score')) {
            $this->merge(['score' => (float) $this->input('score')]);
        }
    }

    public function withValidator($validator): void {
        $validator->after(function ($v) {
            $criterionId = $this->input('criterion_id');
            if (!$criterionId) return;

            $crit = Criterion::with('children')->find($criterionId);
            if (!$crit) return;

            // Disallow scoring parent criteria; parents are computed server-side
            if ($crit->children && $crit->children->count() > 0) {
                $v->errors()->add('criterion_id', 'You can only submit scores for sub-criteria (leaf nodes).');
            }
        });
    }
}
