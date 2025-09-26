<?php

namespace App\Models;

use \Staudenmeir\EloquentEagerLimit\HasEagerLimit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardHistory extends Model
{
    use HasFactory, HasEagerLimit;

    const BOUGHT = 0;
    const USED = 1;
    const GIVEN = 2;
    const TAKEN = 3;
    const AUTO_GIVEN = 4;
    const AUTO_TAKEN = 5;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'date',
        'reward_id',
        'action',
    ];
}
