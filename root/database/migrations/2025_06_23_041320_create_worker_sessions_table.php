<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('worker_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('worker_id');
            $table->string('keyword');
            $table->boolean('is_matched')->default(false);
            $table->tinyInteger('repeat_count')->default(0);
            $table->tinyInteger('current_repeat')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_sessions');
    }
};
