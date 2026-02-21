<?php

use App\Http\Controllers\Project\BusinessRule\IndexController as BusinessRuleIndexController;
use App\Http\Controllers\Project\Chat\IndexController as ChatIndexController;
use App\Http\Controllers\Project\Chat\SelectAgentsController;
use App\Http\Controllers\Project\Conversation\IndexController as ConversationIndexController;
use App\Http\Controllers\Project\Conversation\ShowController as ConversationShowController;
use App\Http\Controllers\Project\Decision\IndexController as DecisionIndexController;
use App\Http\Controllers\Project\IndexController as ProjectIndexController;
use App\Http\Controllers\Project\ShowController as ProjectShowController;
use App\Http\Controllers\Project\StoreController as ProjectStoreController;
use App\Http\Controllers\Project\Task\ChatController as TaskChatController;
use App\Http\Controllers\Project\Task\IndexController as TaskIndexController;
use App\Http\Controllers\Project\Task\ShowController as TaskShowController;
use App\Http\Controllers\Project\Task\StartController as TaskStartController;
use App\Http\Controllers\Project\Task\UpdateController as TaskUpdateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    Route::inertia('/', 'dashboard')->name('dashboard');

    /**
     * Projects
     */
    Route::get('p', ProjectIndexController::class)->name('projects.index');
    Route::post('p', ProjectStoreController::class)->name('projects.store');
    Route::get('p/{project}', ProjectShowController::class)->name('projects.show');

    /**
     * Chat
     */
    Route::post('p/{project}/n', ChatIndexController::class)->name('projects.chat');
    Route::post('p/{project}/c/{conversation}/s', SelectAgentsController::class)->name('projects.conversations.select-agents');

    /**
     * Conversations
     */
    Route::get('p/{project}/c', ConversationIndexController::class)->name('projects.conversations.index');
    Route::get('p/{project}/c/{conversation}', ConversationShowController::class)->name('projects.conversations.show');

    /**
     * Decisions
     */
    Route::get('p/{project}/d', DecisionIndexController::class)->name('projects.decisions.index');

    /**
     * Business Rules
     */
    Route::get('p/{project}/b', BusinessRuleIndexController::class)->name('projects.business-rules.index');

    /**
     * Tasks
     */
    Route::get('p/{project}/t', TaskIndexController::class)->name('projects.tasks.index');
    Route::get('p/{project}/t/{task}', TaskShowController::class)->name('projects.tasks.show');
    Route::patch('p/{project}/t/{task}', TaskUpdateController::class)->name('projects.tasks.update');
    Route::post('p/{project}/t/{task}/c', TaskChatController::class)->name('projects.tasks.chat');
    Route::post('p/{project}/t/{task}/s', TaskStartController::class)->name('projects.tasks.start');
});
