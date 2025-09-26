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
        Schema::create('task_histories', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer("task_id");
            $table->boolean("was_complete");
            $table->integer("count");
            $table->integer("target_count");
            $table->timestamps();
        });
    }
};
