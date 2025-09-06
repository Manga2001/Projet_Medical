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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('conversation_id')->unique();
            $table->string('user_type')->nullable(); // patient, doctor, admin, anonymous
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('user_message');
            $table->text('ai_response');
            $table->boolean('is_medical_question')->default(false);
            $table->string('ai_mode')->default('simulator'); // openai, gemini, simulator
            $table->json('user_context')->nullable();
            $table->json('suggestions')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamps();
            
            $table->index(['conversation_id', 'created_at']);
            $table->index(['user_type', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
