<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contestant extends Model
{
    protected $with = ['municipality', 'event'];

    protected $fillable = ['name', 'gender', 'municipality_id', 'event_id', 'number'];

    public function scores(): HasMany {
        return $this->hasMany(Score::class);
    }

    public function results(): HasMany {
        return $this->hasMany(Result::class);
    }

    public function municipality() {
        return $this->belongsTo(Municipality::class);
    }

    public function event() {
        return $this->belongsTo(PageantEvent::class, 'event_id');
    }

}
