<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('/chat/{receiver?}', 'chat')
    ->middleware(['auth', 'verified'])
    ->name('chat');



Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__ . '/auth.php';
