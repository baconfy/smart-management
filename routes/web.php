<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\Projects\ConversationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    Route::inertia('/', 'dashboard')->name('dashboard');

    /**
     * Projects
     */
    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::post('projects/{project}/chat', ChatController::class)->name('projects.chat');

    /**
     * Conversations
     */
    Route::get('projects/{project}/conversations', [ConversationController::class, 'index'])->name('projects.conversations.index');
    Route::get('projects/{project}/conversations/{conversation}', [ConversationController::class, 'show'])->name('projects.conversations.show');

});
