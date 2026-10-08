<?php

use App\Models\User;

test('authenticated user without account is redirected to tenant registration page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/app')
        ->assertRedirect('/app/new');
});

test('tenant registration page is accessible', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/app/new')
        ->assertSuccessful();
});
