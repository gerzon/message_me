<?php

use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/messages');

Route::get('/hello', function () {
    $name = request('name', 'Guest');

    return [
        'message' => 'Hello '.$name.'!',
    ];
});

Route::resource('messages', MessageController::class)->except('show');
Route::resource('conversations', \App\Http\Controllers\ConversationController::class)->only(['edit', 'update', 'destroy']);

Route::post('/connections', [ConnectionController::class, 'store'])->name('connections.store');
Route::post('/connections/join', [ConnectionController::class, 'join'])
    ->middleware('throttle:10,1')
    ->name('connections.join');
Route::post('/connections/leave', [ConnectionController::class, 'leave'])->name('connections.leave');
