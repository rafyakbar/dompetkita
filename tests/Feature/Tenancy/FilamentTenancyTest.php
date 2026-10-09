<?php

use App\Enums\AccountRole;
use App\Enums\MemberStatus;
use App\Filament\Pages\Tenancy\RegisterAccount;
use App\Models\Account;
use App\Models\User;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

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

test('user can register a new account and is redirected to its dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(RegisterAccount::class)
        ->fillForm([
            'name' => 'Tabungan Masa Depan',
        ])
        ->call('register')
        ->assertHasNoFormErrors()
        ->assertRedirect('/app/tabungan-masa-depan');

    $account = Account::where('slug', 'tabungan-masa-depan')->first();

    expect($account)->not->toBeNull()
        ->and($account->owner_id)->toBe($user->id);

    expect($user->accounts()->whereKey($account)->exists())->toBeTrue()
        ->and($account->members()->where('user_id', $user->id)->first()->pivot->role)->toBe(AccountRole::Owner->value)
        ->and($account->members()->where('user_id', $user->id)->first()->pivot->status)->toBe(MemberStatus::Active->value);
});

test('user can register a new account with custom currency selection', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(RegisterAccount::class)
        ->fillForm([
            'name' => 'Global Portfolio',
            'currency_code' => 'USD',
        ])
        ->call('register')
        ->assertHasNoFormErrors()
        ->assertRedirect('/app/global-portfolio');

    $account = Account::where('slug', 'global-portfolio')->first();

    expect($account)->not->toBeNull()
        ->and($account->currency_code)->toBe('USD');
});

test('tenant registration form includes searchable currency select with country options', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(RegisterAccount::class)
        ->assertFormFieldExists('currency_code', function ($field) {
            return $field instanceof Select
                && $field->isSearchable()
                && array_key_exists('IDR', $field->getOptions())
                && array_key_exists('USD', $field->getOptions());
        });
});

test('user can access own tenant dashboard', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['owner_id' => $user->id]);

    $account->members()->attach($user->id, [
        'email' => $user->email,
        'role' => AccountRole::Owner->value,
        'status' => MemberStatus::Active->value,
        'confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get("/app/{$account->slug}")
        ->assertSuccessful();
});

test('user cannot access other tenant dashboard where they are not a member', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $otherAccount = Account::factory()->create(['owner_id' => $otherUser->id]);
    $otherAccount->members()->attach($otherUser->id, [
        'email' => $otherUser->email,
        'role' => AccountRole::Owner->value,
        'status' => MemberStatus::Active->value,
        'confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get("/app/{$otherAccount->slug}")
        ->assertNotFound();
});

test('inactive member cannot access tenant dashboard', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();

    $account->members()->attach($user->id, [
        'email' => $user->email,
        'role' => AccountRole::Member->value,
        'status' => MemberStatus::Invited->value,
        'invited_at' => now(),
    ]);

    $this->actingAs($user)
        ->get("/app/{$account->slug}")
        ->assertNotFound();
});

test('force deleting account cascades to account_member records', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['owner_id' => $user->id]);

    $account->members()->attach($user->id, [
        'email' => $user->email,
        'role' => AccountRole::Owner->value,
        'status' => MemberStatus::Active->value,
        'confirmed_at' => now(),
    ]);

    expect(DB::table('account_member')->where('account_id', $account->id)->count())->toBe(1);

    $account->forceDelete();

    expect(DB::table('account_member')->where('account_id', $account->id)->count())->toBe(0);
});

test('users with identical tenant slug resolve to their own tenant dashboard', function () {
    $userA = User::factory()->create();
    $accountA = Account::create(['owner_id' => $userA->id, 'name' => 'Bisnis Saya']);
    $accountA->members()->attach($userA->id, [
        'email' => $userA->email,
        'role' => AccountRole::Owner->value,
        'status' => MemberStatus::Active->value,
        'confirmed_at' => now(),
    ]);

    $userB = User::factory()->create();
    $accountB = Account::create(['owner_id' => $userB->id, 'name' => 'Bisnis Saya']);
    $accountB->members()->attach($userB->id, [
        'email' => $userB->email,
        'role' => AccountRole::Owner->value,
        'status' => MemberStatus::Active->value,
        'confirmed_at' => now(),
    ]);

    expect($accountA->slug)->toBe('bisnis-saya')
        ->and($accountB->slug)->toBe('bisnis-saya');

    // Acting as User A accesses /app/bisnis-saya -> resolves to Account A
    $responseA = $this->actingAs($userA)->get('/app/bisnis-saya');
    $responseA->assertOk();

    // Acting as User B accesses /app/bisnis-saya -> resolves to Account B
    $responseB = $this->actingAs($userB)->get('/app/bisnis-saya');
    $responseB->assertOk();
});
