<?php

use App\Models\Account;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\UserSeeder;
use Filament\Auth\Pages\Login;
use Livewire\Livewire;

beforeEach(function () {
    auth()->logout();
});

test('unauthenticated user accessing non-existent tenant is redirected to login without stale intended url', function () {
    $response = $this->withSession(['url.intended' => 'http://localhost/app/keuangan-pribadi/categories'])
        ->get('/app/keuangan-pribadi/categories');

    $response->assertRedirect('/app/login')
        ->assertSessionMissing('url.intended');
});

test('authenticated user with no accounts accessing non-existent tenant is redirected to /app/new', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['url.intended' => 'http://localhost/app/keuangan-pribadi/categories'])
        ->get('/app/keuangan-pribadi/categories');

    $response->assertRedirect('/app/new')
        ->assertSessionMissing('url.intended');
});

test('authenticated user with account accessing non-existent tenant is redirected to their default account dashboard', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['owner_id' => $user->id, 'slug' => 'akun-saya']);
    $account->members()->attach($user->id, [
        'email' => $user->email,
        'role' => 'owner',
        'status' => 'active',
        'confirmed_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->withSession(['url.intended' => 'http://localhost/app/keuangan-pribadi/categories'])
        ->get('/app/keuangan-pribadi/categories');

    $response->assertRedirect('/app/akun-saya')
        ->assertSessionMissing('url.intended');
});

test('seeder creates default keuangan-pribadi account with wallet and system categories for admin user', function () {
    $this->seed(UserSeeder::class);
    $this->seed(AccountSeeder::class);

    $admin = User::where('email', 'admin@email.com')->first();
    expect($admin)->not->toBeNull();

    $account = Account::where('slug', 'keuangan-pribadi')->first();
    expect($account)->not->toBeNull()
        ->and($account->owner_id)->toBe($admin->id)
        ->and($account->members()->where('user_id', $admin->id)->exists())->toBeTrue()
        ->and($account->categories()->where('is_system', true)->count())->toBe(8)
        ->and($account->categories()->where('is_system', false)->count())->toBe(14)
        ->and($account->wallets()->where('name', 'Dompet Utama')->exists())->toBeTrue();
});

test('unauthenticated request to non-existent tenant followed by login does not 404', function () {
    $this->seed(UserSeeder::class);
    $this->seed(AccountSeeder::class);

    // 1. Visit deleted / non-existent tenant while unauthenticated
    $response = $this->get('/app/non-existent-tenant/categories');
    $response->assertRedirect('/app/login')
        ->assertSessionMissing('url.intended');

    // 2. Login as admin
    $admin = User::where('email', 'admin@email.com')->first();
    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'admin@email.com',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect('/app/keuangan-pribadi');
});
