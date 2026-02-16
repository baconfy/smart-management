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
        Schema::create('implementation_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('conversation_message_id', 36)->nullable();
            $table->string('title');
            $table->text('content');
            $table->json('code_snippets')->nullable();
            $table->timestamps();

            $table->index(['task_id']);
        });
    }
};
