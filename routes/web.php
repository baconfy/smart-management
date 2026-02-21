<?php

use App\Http\Controllers\Project;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    Route::inertia('/', 'dashboard')->name('dashboard');

    /**
     * Projects
     */
    Route::get('p', Project\IndexController::class)->name('projects.index');
    Route::post('p', Project\StoreController::class)->name('projects.store');
    Route::get('p/{project}', Project\ShowController::class)->name('projects.show');

    /**
     * Conversations
     */
    Route::get('p/{project}/c/{conversation?}', Project\Conversation\IndexController::class)->name('projects.conversations.index');
    Route::post('p/{project}/c', Project\Conversation\SendMessageController::class)->name('projects.conversations.send');
    Route::post('p/{project}/c/{conversation}/s', Project\Conversation\SelectAgentsController::class)->name('projects.conversations.select-agents');

    /**
     * Decisions
     */
    Route::get('p/{project}/d', Project\Decision\IndexController::class)->name('projects.decisions.index');

    /**
     * Business Rules
     */
    Route::get('p/{project}/b', Project\BusinessRule\IndexController::class)->name('projects.business-rules.index');

    /**
     * Tasks
     */
    Route::get('p/{project}/t', Project\Task\IndexController::class)->name('projects.tasks.index');
    Route::get('p/{project}/t/{task}', Project\Task\ShowController::class)->name('projects.tasks.show');
    Route::patch('p/{project}/t/{task}', Project\Task\UpdateController::class)->name('projects.tasks.update');
    Route::post('p/{project}/t/{task}/c', Project\Task\ChatController::class)->name('projects.tasks.chat');
    Route::post('p/{project}/t/{task}/s', Project\Task\StartController::class)->name('projects.tasks.start');

});
