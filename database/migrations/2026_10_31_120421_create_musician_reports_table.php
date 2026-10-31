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
        Schema::create('musician_reports', function (Blueprint $table) {
            $table->id();
            $table->string('firestore_id')->nullable()->unique();
            $table->string('reported_user_id')->index();
            $table->string('reporter_user_id')->nullable()->index();
            $table->string('reason');
            $table->text('details')->nullable();
            $table->string('status')->default('pending')->index(); // pending, reviewed, resolved, dismissed
            $table->timestamp('reported_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('action_taken')->nullable(); // hidden_profile, restored_profile, dismissed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('musician_reports');
    }
};
