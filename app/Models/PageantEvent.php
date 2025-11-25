<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageantEvent extends Model
{
    protected $table = 'pageant_events';
    protected $fillable = [
        'title',
        'year',
        'start_date',
        'end_date',
        'status',
    ];

    public function contestants() {
        return $this->hasMany(Contestant::class, 'event_id');
    }

    public function judges() {
        return $this->hasMany(Judge::class, 'event_id');
    }

    public function scores() {
        return $this->hasMany(Score::class, 'event_id');
    }

    public function results() {
        return $this->hasMany(Result::class, 'event_id');
    }
    
    public function categories() {
        return $this->hasMany(Category::class, 'event_id');
    }

    public function criteria() {
        return $this->hasMany(Criterion::class, 'event_id');
    }
}
