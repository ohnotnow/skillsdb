<?php

use App\Livewire\Admin\ApiTokensManager;
use App\Livewire\Admin\SkillsDashboard;
use App\Livewire\Admin\SkillsManager;
use App\Livewire\Admin\SkillsMatrix;
use App\Livewire\Admin\TeamCoach;
use App\Livewire\Admin\TrainingCoursesManager;
use App\Livewire\Admin\UserSkillsEditor;
use App\Livewire\Admin\UserSkillsManager;
use App\Livewire\HomePage;
use App\Livewire\Manager\PendingTrainingRequests;
use App\Livewire\PlaySpace;
use App\Livewire\SkillsCoach;
use Illuminate\Support\Facades\Route;

require __DIR__.'/sso-auth.php';

Route::middleware('auth')->group(function () {
    Route::get('/', HomePage::class)->name('home');
    Route::get('/coach', SkillsCoach::class)->name('coach');
    Route::get('/play', PlaySpace::class)->name('play');
    Route::get('/manager/training-requests', PendingTrainingRequests::class)->name('manager.training-requests');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/skills', SkillsManager::class)->name('skills');
        Route::get('/users', UserSkillsManager::class)->name('users');
        Route::get('/users/{user}', UserSkillsEditor::class)->name('users.skills');
        Route::get('/matrix', SkillsMatrix::class)->name('matrix');
        Route::get('/dashboard', SkillsDashboard::class)->name('dashboard');
        Route::get('/api-tokens', ApiTokensManager::class)->name('api-tokens');
        Route::get('/training', TrainingCoursesManager::class)->name('training');
        Route::get('/team-coach', TeamCoach::class)->name('team-coach');
    });
});
