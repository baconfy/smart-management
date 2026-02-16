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
        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('conversation_message_id', 36)->nullable();
            $table->string('title');
            $table->text('choice');
            $table->text('reasoning');
            $table->json('alternatives_considered')->nullable();
            $table->text('context')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }
};
