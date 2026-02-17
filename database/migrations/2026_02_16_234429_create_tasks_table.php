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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->ulid();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('conversation_message_id', 36)->nullable();
            $table->string('title');
            $table->text('description');
            $table->string('phase')->nullable();
            $table->string('milestone')->nullable();
            $table->string('status')->default('backlog');
            $table->string('priority')->default('medium');
            $table->string('estimate')->nullable();
            $table->integer('sort_order')->default(0);
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'priority']);
            $table->index(['parent_task_id']);
        });
    }
};
