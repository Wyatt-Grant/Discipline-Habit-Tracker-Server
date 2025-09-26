<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'dynamic_id',
        'sort_order',
        'name',
        'color',
    ];

    public function dynamic(): BelongsTo
    {
        return $this->BelongsTo(Dynamic::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
