<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Criterion extends Model
{
    protected $fillable = ['category_id', 'parent_id', 'event_id', 'name', 'percentage'];

    /* ─────────────────────────────── Relationships ─────────────────────────────── */
    
    public function event() {
        return $this->belongsTo(PageantEvent::class, 'event_id');
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function scores() {
        return $this->hasMany(Score::class);
    }

    public function parent() {
        return $this->belongsTo(Criterion::class, 'parent_id');
    }

    public function children() {
        return $this->hasMany(Criterion::class, 'parent_id')->orderBy('id');
    }

    // public function children() {
    //     return $this->hasMany(Criterion::class, 'parent_id')
    //         ->with('children'); // important for deep recursion
    // }
}
