<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('tmdb_id');
            $table->string('title');
            $table->text('overview')->nullable();
            $table->string('poster_path')->nullable();
            $table->date('release_date')->nullable();
            $table->jsonb('genre_ids');
            $table->timestamps();

            $table->index('user_id');
            $table->unique(['user_id', 'tmdb_id']);
        });

        DB::statement('CREATE INDEX favorites_genre_ids_gin_idx ON favorites USING GIN (genre_ids)');
        DB::statement("ALTER TABLE favorites ADD CONSTRAINT favorites_genre_ids_array CHECK (jsonb_typeof(genre_ids) = 'array')");
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
