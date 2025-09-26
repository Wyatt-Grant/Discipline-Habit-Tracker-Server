<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    const ROLE_DOM = 1;
    const ROLE_SUB = 2;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'user_name',
        'remember_token',
        'password',
        'points',
        'role',
        'device',
        'APN',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Define a many-to-many relationship between User and Dynamic models.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function dynamics(): BelongsToMany
    {
        return $this->belongsToMany(Dynamic::class, "dynamics_users")->withTimestamps();
    }

    /**
     * Check if the user has a 'sub' role.
     *
     * @return bool
     */
    public function isSub() {
        return $this->role == self::ROLE_SUB;
    }

    /**
     * Check if the user has a 'dom' role.
     *
     * @return bool
     */
    public function isDom() {
        return $this->role == self::ROLE_DOM;
    }
}
