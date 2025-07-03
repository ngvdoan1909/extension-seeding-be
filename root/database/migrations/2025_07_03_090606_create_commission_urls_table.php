<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('commission_urls', function (Blueprint $table) {
            $table->id();
            $table->uuid('commission_url_id')->unique();
            $table->uuid('commission_id');
            $table->string('url');
            $table->string('key_word', 100);
            $table->string('key_word_image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_urls');
    }
};
