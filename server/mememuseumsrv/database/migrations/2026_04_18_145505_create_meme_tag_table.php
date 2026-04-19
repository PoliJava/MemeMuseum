<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meme_tag', function (Blueprint $table) {
            $table->foreignId('meme_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['meme_id', 'tag_id']); // composite PK, no duplicate pairs
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meme_tag');
    }
};