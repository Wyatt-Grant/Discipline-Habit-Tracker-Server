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
            $table->string('rrule')->after('max_count')->default('RRULE:FREQ=DAILY;INTERVAL=1');
            $table->date('start')->after('rrule')->nullable();
            $table->date('end')->after('start')->nullable();
            $table->integer('remove_points_on_failure')->after('end')->default(false);
        });
    }
};
