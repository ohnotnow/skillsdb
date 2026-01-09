<?php

use App\Livewire\HomePage;
use Illuminate\Support\Facades\Route;

require __DIR__.'/sso-auth.php';

Route::middleware('auth')->group(function () {
    Route::get('/', HomePage::class)->name('home');
});
