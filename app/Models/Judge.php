<?php

namespace App\Models;

use App\Models\Score;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Judge extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'category_assignment',
        'judge_number',
        'is_active',
    ];

    public function scores(): HasMany {
        return $this->hasMany(Score::class);
    }

    public function event() {
        return $this->belongsTo(PageantEvent::class, 'event_id');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function phases() {
        return $this->belongsToMany(Phase::class, 'judge_phase');
    }


    public function scopeForEvent($q, int $eventId) {
        return $q->where('event_id', $eventId);
    }
}
