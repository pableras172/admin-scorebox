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
        Schema::create('promotional_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('assigned_email')->nullable()->index();
            $table->string('assigned_uid')->nullable();
            $table->foreignId('marketing_campaign_id')
                ->nullable()
                ->constrained('marketing_campaigns')
                ->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotional_codes');
    }
};
