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
        Schema::create('task_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('color', 7)->default('#71717a');
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_in_progress')->default(false);
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->unique(['project_id', 'slug']);
            $table->index(['project_id', 'position']);
        });
    }
};
