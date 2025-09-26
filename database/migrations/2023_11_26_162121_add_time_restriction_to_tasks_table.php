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
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('restrict')->after('remove_points_on_failure')->default(false);
            $table->boolean('restrict_before')->after('restrict')->default(true);
            $table->time('restrict_time')->after('restrict_before')->nullable();
        });
    }
};
