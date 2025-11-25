<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model
{
    protected $fillable = [
        'event_id',
        'judge_id',
        'contestant_id',
        'category_id',
        'criterion_id',
        'score',
        'weighted_score',
    ];

    public function contestant() {
        return $this->belongsTo(Contestant::class);
    }

    public function judge() {
        return $this->belongsTo(Judge::class);
    }

    public function criterion() {
        return $this->belongsTo(Criterion::class);
    }

    public function event() {
        return $this->belongsTo(PageantEvent::class, 'event_id');
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }

}
