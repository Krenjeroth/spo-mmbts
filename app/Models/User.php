<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Services\CurrentEventService;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles() {
      return $this->belongsToMany(Role::class);
    }

    public function judge() {
        return $this->hasOne(Judge::class);
    }

    public function judgeAssignments() {
        return $this->hasMany(Judge::class);
    }

    public function permissions() {
        return $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id');
    }

    public function currentJudge() {
      return $this->hasOne(Judge::class)
        ->where('event_id', CurrentEventService::getId());
    }

    public function hasRole(string $roleTitle): bool {
        return $this->roles()->where('title', $roleTitle)->exists();
    }

    public function hasPermission(string $permissionTitle): bool {
        return $this->permissions()->contains('title', $permissionTitle);
    }
}
