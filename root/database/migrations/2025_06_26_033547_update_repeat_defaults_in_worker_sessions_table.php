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
        Schema::table('worker_sessions', function (Blueprint $table) {
            $table->tinyInteger('repeat_count')->default(3)->change();
            $table->tinyInteger('current_repeat')->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worker_sessions', function (Blueprint $table) {
            $table->tinyInteger('repeat_count')->default(0)->change();
            $table->tinyInteger('current_repeat')->default(0)->change();
        });
    }
};
