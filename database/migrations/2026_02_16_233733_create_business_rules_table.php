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
        Schema::create('business_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('category');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'category']);
        });
    }
};
