public function up(): void
{
    Schema::create('memes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('title');
        $table->string('image_path');
        $table->enum('age', ['everyone', 'teen', 'mature'])->default('everyone');
        $table->timestamps();
    });
}