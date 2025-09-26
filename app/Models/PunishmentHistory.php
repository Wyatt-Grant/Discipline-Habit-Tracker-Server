<?php

namespace App\Models;

use \Staudenmeir\EloquentEagerLimit\HasEagerLimit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PunishmentHistory extends Model
{
    use HasFactory, HasEagerLimit;

    const ASSIGNED = 0;
    const COMPLETE = 1;
    const FORGIVEN = 2;
    const AUTO_ASSIGNED = 3;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'date',
        'punishment_id',
        'action',
    ];
}
