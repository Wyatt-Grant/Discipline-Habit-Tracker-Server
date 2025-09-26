<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dynamics', function (Blueprint $table) {
            $table->string("default_reward_emojis")->after('time_zone')->default('🎉,🎊,👑,💖,✨,🎀');
        });
    }
};
