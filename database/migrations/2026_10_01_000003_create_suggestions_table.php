<?php

declare(strict_types=1);

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
        Schema::create('suggestions', function (Blueprint $table) {
            $table->id();
            $table->string('source')->default('app'); // 'app', 'web', 'admin'
            $table->string('uid')->nullable()->index(); // Firestore user UID
            $table->string('email')->index();
            $table->string('name')->nullable();
            $table->boolean('is_premium')->default(false);
            $table->string('type')->default('idea'); // 'idea', 'bug', 'scores_request', 'usability', 'other'
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('app_version')->nullable();
            $table->string('device_info')->nullable();
            $table->string('status')->default('new'); // 'new', 'in_review', 'planned', 'completed', 'dismissed'
            $table->text('admin_notes')->nullable();
            $table->string('firestore_id')->nullable()->unique(); // To prevent duplicate Firestore imports
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggestions');
    }
};
