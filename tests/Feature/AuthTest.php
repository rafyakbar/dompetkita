<?php

use App\Enums\AccountRole;
use App\Enums\MemberStatus;
use App\Models\Account;
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
    $account = Account::factory()->create(['owner_id' => $user->id]);

    $account->members()->attach($user->id, [
        'email' => $user->email,
        'role' => AccountRole::Owner->value,
        'status' => MemberStatus::Active->value,
        'confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/app')
        ->assertRedirect("/app/{$account->slug}");

    $this->actingAs($user)
        ->get("/app/{$account->slug}")
        ->assertSuccessful();
});

test('user can access breezy my-profile page', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['owner_id' => $user->id]);

    $account->members()->attach($user->id, [
        'email' => $user->email,
        'role' => AccountRole::Owner->value,
        'status' => MemberStatus::Active->value,
        'confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get("/app/{$account->slug}/my-profile")
        ->assertSuccessful();
});
