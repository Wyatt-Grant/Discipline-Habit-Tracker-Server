<?php

namespace App\Models;

use \Staudenmeir\EloquentEagerLimit\HasEagerLimit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model
{
    use HasFactory, HasEagerLimit;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'dynamic_id',
        'name',
        'description',
        'value',
        'bank',
    ];

    public function history(): HasMany
    {
        return $this->hasMany(RewardHistory::class)
            ->orderBy('reward_histories.date', 'DESC');
    }

    public function dynamic(): BelongsTo
    {
        return $this->BelongsTo(Dynamic::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, "rewards_tasks");
    }
}
