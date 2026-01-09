<?php

use App\Models\User;

test('unauthenticated users are redirected to login', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});

test('authenticated users can access the home page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertOk();
    $response->assertSee('My Skills');
});
