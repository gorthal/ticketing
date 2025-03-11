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
        Schema::create('ticket_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->longText('content');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_internal')->default(false)->comment('Commentaire interne visible uniquement par les agents');
            $table->string('email_id')->nullable()->comment('ID unique de l\'email de réponse');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_responses');
    }
};
