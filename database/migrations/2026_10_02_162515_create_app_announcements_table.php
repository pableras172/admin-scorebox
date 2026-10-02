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
        Schema::create('app_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('message')->nullable();
            $table->string('type', 30)->default('info');
            $table->string('image_url', 500)->nullable();
            $table->string('action_text', 100)->nullable();
            $table->string('action_url', 500)->nullable();
            $table->boolean('hide_for_pro')->default(false);
            $table->boolean('is_active')->default(false)->index();
            $table->timestamp('synced_to_firestore_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_announcements');
    }
};
