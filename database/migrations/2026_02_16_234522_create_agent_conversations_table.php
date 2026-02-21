<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Migrations\AiMigration;

return new class extends AiMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agent_conversations', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->foreignId('user_id');
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->string('title');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'updated_at']);
            $table->index(['project_id']);
            $table->unique('task_id');
        });

        Schema::create('agent_conversation_messages', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('conversation_id', 26)->index();
            $table->foreignId('user_id');
            $table->foreignId('project_agent_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('agent')->nullable();
            $table->string('role', 25);
            $table->text('content');
            $table->text('attachments')->nullable();
            $table->text('tool_calls')->nullable();
            $table->text('tool_results')->nullable();
            $table->text('usage')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['conversation_id', 'user_id', 'updated_at'], 'conversation_index');
            $table->index(['user_id']);
        });
    }
};
