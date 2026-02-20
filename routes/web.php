<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\Projects\BusinessRuleController;
use App\Http\Controllers\Projects\ConversationController;
use App\Http\Controllers\Projects\DecisionController;
use App\Http\Controllers\Projects\SelectAgentsController;
use App\Http\Controllers\Projects\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    Route::inertia('/', 'dashboard')->name('dashboard');

    /**
     * Projects
     */
    Route::get('p', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('p', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('p/{project}', [ProjectController::class, 'show'])->name('projects.show');

    /**
     * Chat
     */
    Route::post('p/{project}/n', ChatController::class)->name('projects.chat');
    Route::post('p/{project}/c/{conversation}/s', [SelectAgentsController::class, 'store'])->name('projects.conversations.select-agents');

    /**
     * Conversations
     */
    Route::get('p/{project}/c', [ConversationController::class, 'index'])->name('projects.conversations.index');
    Route::get('p/{project}/c/{conversation}', [ConversationController::class, 'show'])->name('projects.conversations.show');

    /**
     * Decisions
     */
    Route::get('p/{project}/d', [DecisionController::class, 'index'])->name('projects.decisions.index');

    /**
     * Business Rules
     */
    Route::get('p/{project}/b', [BusinessRuleController::class, 'index'])->name('projects.business-rules.index');

    /**
     * Tasks
     */
    Route::get('p/{project}/t', [TaskController::class, 'index'])->name('projects.tasks.index');
    Route::get('p/{project}/t/{task}', [TaskController::class, 'show'])->name('projects.tasks.show');
    Route::patch('p/{project}/t/{task}', [TaskController::class, 'update'])->name('projects.tasks.update');
});
