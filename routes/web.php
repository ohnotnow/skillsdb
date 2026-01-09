<?php

use App\Livewire\Admin\SkillsManager;
use App\Livewire\Admin\UserSkillsEditor;
use App\Livewire\Admin\UserSkillsManager;
use App\Livewire\HomePage;
use Illuminate\Support\Facades\Route;

require __DIR__.'/sso-auth.php';

Route::middleware('auth')->group(function () {
    Route::get('/', HomePage::class)->name('home');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/skills', SkillsManager::class)->name('skills');
        Route::get('/users', UserSkillsManager::class)->name('users');
        Route::get('/users/{user}', UserSkillsEditor::class)->name('users.skills');
    });
});
