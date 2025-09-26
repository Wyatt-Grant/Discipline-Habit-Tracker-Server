<?php

use App\Http\Controllers\DynamicController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PunishmentController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\RuleController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// USER
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// DYNAMICS
Route::middleware(['auth:sanctum'])->group(function () {
    // Route::get('/dynamics', fn (Request $request) => $request->user()->dynamics()->get());
    Route::get('/dynamic', [DynamicController::class, 'info']);
    // Route::post('/dynamic', [DynamicController::class, 'create']);
    Route::middleware(['owns.dynamic'])->group(function () {
        Route::put('/dynamic/{dynamic}', [DynamicController::class, 'update']);
    });
});

// TASKS
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/tasks', [TaskController::class, 'all']);
    Route::post('/tasks', [TaskController::class, 'create']);
    Route::middleware(['owns.task'])->group(function () {
        Route::put('/task/{task}', [TaskController::class, 'update']);
        Route::post('/complete-task/{task}', [TaskController::class, 'complete']);
        Route::post('/uncomplete-task/{task}', [TaskController::class, 'uncomplete']);
        Route::delete('/task/{task}', [TaskController::class, 'delete']);
        Route::middleware(['owns.group'])->group(function () {
            Route::post('/assign-group/{task}/{group}', [TaskController::class, 'assignGroup']);
            Route::post('/unassign-group/{task}/{group}', [TaskController::class, 'unassignGroup']);
        });
    });
    Route::get('/tasks/remaining', [TaskController::class, 'dailyRemainingCount']);
    Route::middleware(['owns.taskhistory'])->group(function () {
        Route::post('/complete-task-history/{taskHistory}', [TaskController::class, 'completeHistory']);
        Route::post('/uncomplete-task-history/{taskHistory}', [TaskController::class, 'uncompleteHistory']);
    });
    Route::get('/tasks/reminders', [TaskController::class, 'reminders']);
});

// PUNISHMENTS
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/punishments', [PunishmentController::class, 'all']);
    Route::post('/punishments', [PunishmentController::class, 'create']);
    Route::middleware(['owns.punishment'])->group(function () {
        Route::put('/punishment/{punishment}', [PunishmentController::class, 'update']);
        Route::post('/add-punishment/{punishment}', [PunishmentController::class, 'add']);
        Route::post('/remove-punishment/{punishment}', [PunishmentController::class, 'remove']);
        Route::delete('/punishment/{punishment}', [PunishmentController::class, 'delete']);
        Route::middleware(['owns.task'])->group(function () {
            Route::post('/assign-punishment/{punishment}/{task}', [PunishmentController::class, 'assign']);
            Route::post('/unassign-punishment/{punishment}/{task}', [PunishmentController::class, 'unassign']);
        });
    });
    Route::get('/punishments/assigned', [PunishmentController::class, 'totalAssignedCount']);
});

// REWARDS
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/rewards', [RewardController::class, 'all']);
    Route::post('/rewards', [RewardController::class, 'create']);
    Route::middleware(['owns.reward'])->group(function () {
        Route::put('/reward/{reward}', [RewardController::class, 'update']);
        Route::post('/add-reward/{reward}', [RewardController::class, 'add']);
        Route::post('/remove-reward/{reward}', [RewardController::class, 'remove']);
        Route::delete('/reward/{reward}', [RewardController::class, 'delete']);
        Route::middleware(['owns.task'])->group(function () {
            Route::post('/assign-reward/{reward}/{task}', [RewardController::class, 'assign']);
            Route::post('/unassign-reward/{reward}/{task}', [RewardController::class, 'unassign']);
        });
    });
    Route::get('/points', [RewardController::class, 'points']);
    Route::post('/add-point', [RewardController::class, 'addPoint']);
    Route::post('/remove-point', [RewardController::class, 'removePoint']);
    Route::get('/bank', [RewardController::class, 'BankRewardCount']);
});

// MESSAGES
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/messages', [MessageController::class, 'all']);
    Route::post('/messages', [MessageController::class, 'create']);
    Route::middleware(['owns.message'])->group(function () {
        Route::put('/message/{message}', [MessageController::class, 'update']);
        Route::delete('/message/{message}', [MessageController::class, 'delete']);
        Route::middleware(['owns.task'])->group(function () {
            Route::post('/assign-message/{message}/{task}', [MessageController::class, 'assign']);
            Route::post('/unassign-message/{message}/{task}', [MessageController::class, 'unassign']);
        });
    });
});

// RULES
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/rules', [RuleController::class, 'all']);
    Route::post('/rules', [RuleController::class, 'create']);
    Route::middleware(['owns.rule'])->group(function () {
        Route::put('/rule/{rule}', [RuleController::class, 'update']);
        Route::delete('/rule/{rule}', [RuleController::class, 'delete']);
    });
});

// GROUPS
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/groups', [GroupController::class, 'all']);
    Route::post('/groups', [GroupController::class, 'create']);
    Route::post('/sort-groups', [GroupController::class, 'updateSort']);
    Route::middleware(['owns.group'])->group(function () {
        Route::put('/group/{group}', [GroupController::class, 'update']);
        Route::delete('/group/{group}', [GroupController::class, 'delete']);
    });
});

// AUTH
Route::post('/token', [TokenController::class, 'auth']);
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/setAPN', [TokenController::class, 'setAPN']);
});

// REGISTER
Route::post('/register', [RegistrationController::class, 'registerNewUser']);
