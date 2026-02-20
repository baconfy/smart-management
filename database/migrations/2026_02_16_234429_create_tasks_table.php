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
            $table->string('title');
            $table->text('description');
            $table->string('phase')->nullable();
            $table->string('milestone')->nullable();
            $table->foreignId('task_status_id')->nullable()->constrained('task_statuses')->restrictOnDelete();
            $table->string('priority')->default('medium');
            $table->string('estimate')->nullable();
            $table->integer('sort_order')->default(0);
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['project_id', 'task_status_id']);
            $table->index(['project_id', 'priority']);
            $table->index(['parent_task_id']);
        });
    }
};
