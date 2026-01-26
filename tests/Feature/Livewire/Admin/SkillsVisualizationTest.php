<?php

use App\Livewire\Admin\SkillsVisualization;
use App\Models\User;

it('requires authentication', function () {
    $this->get('/admin/skills/visualization')
        ->assertRedirect();
});

it('requires admin access', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/skills/visualization')
        ->assertForbidden();
});

it('allows admin access', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/skills/visualization')
        ->assertSuccessful()
        ->assertSeeLivewire(SkillsVisualization::class);
});

it('displays the page heading', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/skills/visualization')
        ->assertSee('Skills Map');
});

it('has a link back to skills page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/skills/visualization')
        ->assertSee('Back to Skills');
});
