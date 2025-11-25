<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryResult extends Model
{
  protected $fillable = [
      'contestant_id',
      'category_id',
      'event_id',
      'average_score',
      'category_total',
  ];

  public function contestant() {
      return $this->belongsTo(Contestant::class);
  }

  public function category() {
      return $this->belongsTo(Category::class);
  }

  public function event() {
      return $this->belongsTo(PageantEvent::class);
  }

  public function phase() {
      return $this->belongsTo(Phase::class);
  }
}
