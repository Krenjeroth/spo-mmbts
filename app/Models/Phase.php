<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Phase extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'description',
        'order',
        'is_active',
    ];

    protected static function booted() {
        static::creating(function (self $phase) {
            if (is_null($phase->order)) {
                $phase->order = static::nextOrderForEvent($phase->event_id);
            }
        });

        static::updating(function (self $phase) {
            if ($phase->isDirty('event_id')) {
                // If the client did NOT manually send a new order,
                // or explicitly sent null, we auto-assign.
                if ($phase->isDirty('event_id')) {
                    // user might have sent an order, but we ignore it for the new event
                    $phase->order = static::nextOrderForEvent($phase->event_id);
                }
            }
        });
    }

    public function event() {
        return $this->belongsTo(PageantEvent::class, 'event_id');
    }

    public function categories() {
        return $this->hasMany(Category::class);
    }

    public function judges() {
        return $this->belongsToMany(Judge::class, 'judge_phase');
    }

    public function scopeForEvent($q, int $eventId) {
        return $q->where('event_id', $eventId);
    }

    public static function nextOrderForEvent($eventId): int {
        $max = static::where('event_id', $eventId)->max('order');
        return ($max ?? 0) + 1;
    }
}
