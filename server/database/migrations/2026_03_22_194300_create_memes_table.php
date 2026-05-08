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
    Schema::create('memes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('title');
        $table->string('image_path');
        $table->enum('age', [
        'Ancient (Pre-2004)',
        'Medieval (2004-2008)',
        'Classic (2009-2013)',
        'Golden (2014 - 2016)',
        'Modern (2017 - 2020)',
        'Postmodern (2021 - Present)',
        ])->default('Modern (2017 - 2020)');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memes');
    }
};
