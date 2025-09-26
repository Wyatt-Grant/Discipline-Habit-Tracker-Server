<?php

namespace App\Models;

use \Staudenmeir\EloquentEagerLimit\HasEagerLimit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory, HasEagerLimit;

    const TYPE_ENCOURAGE = 1;
    const TYPE_DISCOURAGE = 2;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'dynamic_id',
        'group_id',
        'name',
        'description',
        'type',
        'value',
        'count',
        'target_count',
        'max_count',
        'rrule',
        'start',
        'end',
        'remove_points_on_failure',
        'remind',
        'remind_time',
        'restrict',
        'restrict_time',
        'restrict_before',
    ];

    public function history(): HasMany
    {
        $timeZone = $this->dynamic()->first()->time_zone;
        return $this->hasMany(TaskHistory::class)
            ->where('date', '!=', today($timeZone))
            ->orderBy('task_histories.date', 'DESC');
    }

    public function messages(): BelongsToMany
    {
        return $this->belongsToMany(Message::class, "messages_tasks");
    }

    public function dynamic(): BelongsTo
    {
        return $this->BelongsTo(Dynamic::class);
    }

    public function group(): BelongsTo
    {
        return $this->BelongsTo(Group::class);
    }

    public function punishments(): BelongsToMany
    {
        return $this->belongsToMany(Punishment::class, "punishments_tasks");
    }

    public function rewards(): BelongsToMany
    {
        return $this->belongsToMany(Reward::class, "rewards_tasks");
    }
}
