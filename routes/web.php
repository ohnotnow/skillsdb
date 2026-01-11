<?php

use App\Livewire\Admin\ApiTokensManager;
use App\Livewire\Admin\SkillsManager;
use App\Livewire\Admin\SkillsMatrix;
use App\Livewire\Admin\UserSkillsEditor;
use App\Livewire\Admin\UserSkillsManager;
use App\Livewire\HomePage;
use App\Livewire\PlaySpace;
use Illuminate\Support\Facades\Route;

require __DIR__.'/sso-auth.php';

Route::middleware('auth')->group(function () {
    Route::get('/', HomePage::class)->name('home');
    Route::get('/play', PlaySpace::class)->name('play');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/skills', SkillsManager::class)->name('skills');
        Route::get('/users', UserSkillsManager::class)->name('users');
        Route::get('/users/{user}', UserSkillsEditor::class)->name('users.skills');
        Route::get('/matrix', SkillsMatrix::class)->name('matrix');
        Route::get('/api-tokens', ApiTokensManager::class)->name('api-tokens');
    });
});
