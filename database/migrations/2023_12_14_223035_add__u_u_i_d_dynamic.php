<?php

use App\Models\Dynamic;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dynamics', function (Blueprint $table) {
            $table->string('UUID')->after('default_reward_emojis')->default(null);
        });

        Dynamic::chunk(100, function ($chunk) {
            $chunk->each(function (Dynamic $dynamic) {
                $dynamic->UUID = Str::uuid();
                $dynamic->save();
            });
        });
    }
};
