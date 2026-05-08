<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memes', function (Blueprint $table) {
            $table->foreignId('board_id')->after('id')->constrained()->cascadeOnDelete();
            $table->boolean('is_anonymous')->default(true)->after('user_id');
            $table->string('author_name', 64)->nullable()->after('is_anonymous');
            $table->unsignedBigInteger('views_count')->default(0)->after('author_name');
        });
    }

    public function down(): void
    {
        Schema::table('memes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('board_id');
            $table->dropColumn(['is_anonymous', 'author_name', 'views_count']);
        });
    }
};