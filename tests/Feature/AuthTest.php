<?php

use App\Models\User;

test('login page is accessible', function () {
    $this->get('/app/login')
        ->assertSuccessful();
});

test('registration page is accessible', function () {
    $this->get('/app/register')
        ->assertSuccessful();
});

test('user can log in to app panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/app')
        ->assertSuccessful();
});

test('user can access breezy my-profile page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/app/my-profile')
        ->assertSuccessful();
});
