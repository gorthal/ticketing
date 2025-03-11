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
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('keyword');
            $table->foreignId('label_id')->constrained()->cascadeOnDelete();
            $table->enum('match_type', ['subject', 'body', 'both'])->default('both');
            $table->boolean('is_case_sensitive')->default(false);
            $table->text('notification_emails')->nullable()->comment('Emails séparés par des virgules');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};
