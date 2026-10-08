<?php

use App\Enums\AccountRole;
use App\Enums\MemberStatus;
use App\Models\Account;
use App\Models\User;

test('account generates unique slug automatically when creating if not set', function () {
    $user = User::factory()->create();
    $account = Account::create([
        'owner_id' => $user->id,
        'name' => 'Keuangan Keluarga',
    ]);

    expect($account->slug)->toBe('keuangan-keluarga');

    $duplicate = Account::create([
        'owner_id' => $user->id,
        'name' => 'Keuangan Keluarga',
    ]);

    expect($duplicate->slug)->not->toBe('keuangan-keluarga')
        ->and($duplicate->slug)->toStartWith('keuangan-keluarga-');
});

test('user only retrieves active accounts via getTenants contract', function () {
    $user = User::factory()->create();
    $activeAccount = Account::factory()->create();
    $invitedAccount = Account::factory()->create();

    $activeAccount->members()->attach($user->id, [
        'email' => $user->email,
        'role' => AccountRole::Member->value,
        'status' => MemberStatus::Active->value,
        'confirmed_at' => now(),
    ]);

    $invitedAccount->members()->attach($user->id, [
        'email' => $user->email,
        'role' => AccountRole::Member->value,
        'status' => MemberStatus::Invited->value,
        'invited_at' => now(),
    ]);

    $panel = filament()->getCurrentOrDefaultPanel();
    $tenants = $user->getTenants($panel);

    expect($tenants->pluck('id'))->toContain($activeAccount->id)
        ->and($tenants->pluck('id'))->not->toContain($invitedAccount->id)
        ->and($user->canAccessTenant($activeAccount))->toBeTrue()
        ->and($user->canAccessTenant($invitedAccount))->toBeFalse()
        ->and($user->getDefaultTenant($panel)?->id)->toBe($activeAccount->id);
});
