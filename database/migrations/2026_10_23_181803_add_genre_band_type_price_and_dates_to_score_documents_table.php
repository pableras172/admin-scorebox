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
        Schema::table('score_documents', function (Blueprint $table) {
            $table->foreignId('music_genre_id')->nullable()->after('genre')->constrained('music_genres')->nullOnDelete();
            $table->foreignId('band_type_id')->nullable()->after('music_genre_id')->constrained('band_types')->nullOnDelete();
            $table->decimal('price', 8, 2)->nullable()->after('is_premium_only');
            $table->dateTime('published_at')->nullable()->after('price')->index();
            $table->dateTime('publication_end_at')->nullable()->after('published_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('score_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('music_genre_id');
            $table->dropConstrainedForeignId('band_type_id');
            $table->dropColumn(['price', 'published_at', 'publication_end_at']);
        });
    }
};
