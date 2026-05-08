<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('votes');

        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meme_id')->constrained()->cascadeOnDelete();
            $table->decimal('value', 3, 1); // 0.5 .. 5.0
            $table->timestamps();

            $table->unique(['user_id', 'meme_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};