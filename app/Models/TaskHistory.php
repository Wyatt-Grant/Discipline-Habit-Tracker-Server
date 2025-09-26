<?php

namespace App\Models;

use \Staudenmeir\EloquentEagerLimit\HasEagerLimit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskHistory extends Model
{
    use HasFactory, HasEagerLimit;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'date',
        'task_id',
        'was_complete',
        'count',
        'target_count',
    ];

    public function task(): BelongsTo
    {
        return $this->BelongsTo(Task::class);
    }
}
