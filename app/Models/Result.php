<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    protected $fillable = ['contestant_id', 'category_id', 'total_score'];

    public function contestant(): BelongsTo {
        return $this->belongsTo(Contestant::class);
    }

    public function category(): BelongsTo {
        return $this->belongsTo(Category::class);
    }

    public function event() {
        return $this->belongsTo(PageantEvent::class, 'event_id');
    }
}
