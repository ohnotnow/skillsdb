<?php

use App\Livewire\Admin\SkillsManager;
use App\Livewire\HomePage;
use Illuminate\Support\Facades\Route;

require __DIR__.'/sso-auth.php';

Route::middleware('auth')->group(function () {
    Route::get('/', HomePage::class)->name('home');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/skills', SkillsManager::class)->name('skills');
    });
});
