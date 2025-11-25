<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'event_id',
        'phase_id',
        'name',
        'slug',
        'weight',
        'order',
        'is_active',
    ];

    protected static function booted()
    {
        static::saving(function ($model) {
            // Auto-generate slug if not set
            if (!$model->slug && $model->name) {
                $base = Str::of($model->name)
                    ->lower()
                    ->replaceMatches('/[^a-z0-9]+/i', '-')
                    ->trim('-');

                $slug = (string) $base;
                $i = 1;

                while (
                    static::where('event_id', $model->event_id)
                        ->where('slug', $slug)
                        ->where('id', '!=', $model->id ?? 0)
                        ->exists()
                ) {
                    $slug = $base . '-' . $i++;
                }

                $model->slug = $slug;
            }
        });
    }

    /* ─────────────────────────────── Relationships ─────────────────────────────── */
    
    public function event() {
        return $this->belongsTo(PageantEvent::class, 'event_id');
    }

    public function phase() {
        return $this->belongsTo(Phase::class);
    }

    public function criteria(): HasMany {
        return $this->hasMany(Criterion::class);
    }

    public function results(): HasMany {
        return $this->hasMany(Result::class);
    }
}
