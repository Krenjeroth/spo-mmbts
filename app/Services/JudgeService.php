<?php

namespace App\Services;

use App\Models\Judge;

class JudgeService
{
    public static function ensureForEvent(
        int $userId,
        int $eventId,
        array $attrs = [] // ['category_assignment'=>..., 'judge_number'=>..., 'is_active'=>...]
    ): Judge {
        $judge = Judge::firstOrCreate(
            ['user_id' => $userId, 'event_id' => $eventId],
            [
                'category_assignment' => $attrs['category_assignment'] ?? null,
                'judge_number'        => $attrs['judge_number'] ?? null,
                'is_active'           => $attrs['is_active'] ?? true,
            ]
        );

        // If it already existed and you want to update attributes when provided:
        if ($judge->wasRecentlyCreated === false && !empty($attrs)) {
            $judge->fill([
                'category_assignment' => $attrs['category_assignment'] ?? $judge->category_assignment,
                'judge_number'        => $attrs['judge_number']        ?? $judge->judge_number,
                'is_active'           => $attrs['is_active']           ?? $judge->is_active,
            ])->save();
        }

        return $judge;
    }
}
