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
        Schema::create('score_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('composer')->nullable()->index();
            $table->string('instrument')->nullable()->index();
            $table->string('genre')->nullable()->index();
            $table->string('difficulty')->nullable();
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type')->default('application/pdf');
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_premium_only')->default(false)->index();
            $table->unsignedInteger('downloads_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('score_documents');
    }
};
