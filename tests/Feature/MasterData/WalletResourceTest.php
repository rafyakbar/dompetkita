<?php

use App\Filament\Resources\WalletResource\Pages\ListWallets;
use App\Models\Account;
use App\Models\User;
use App\Models\Wallet;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->create(['owner_id' => $this->user->id]);
    $this->account->members()->attach($this->user->id, [
        'email' => $this->user->email,
        'role' => 'owner',
        'status' => 'active',
        'confirmed_at' => now(),
    ]);

    $this->actingAs($this->user);
    Filament::setTenant($this->account);
});

test('wallet resource list page renders correctly', function () {
    Wallet::factory()->create([
        'account_id' => $this->account->id,
        'name' => 'Rekening Operasional',
    ]);

    Livewire::test(ListWallets::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(Wallet::where('account_id', $this->account->id)->get());
});

test('can create wallet via slide-over modal with scoped unique validation', function () {
    Livewire::test(ListWallets::class)
        ->callAction(CreateAction::class, data: [
            'name' => 'Bank Mandiri',
            'color' => '#2563eb',
            'allow_minus' => false,
        ])
        ->assertHasNoActionErrors();

    expect(Wallet::where('account_id', $this->account->id)->where('name', 'Bank Mandiri')->exists())->toBeTrue();

    // Trying to create the duplicate name within the same tenant must fail
    Livewire::test(ListWallets::class)
        ->callAction(CreateAction::class, data: [
            'name' => 'Bank Mandiri',
        ])
        ->assertHasActionErrors(['name']);
});

test('can edit wallet via slide-over modal', function () {
    $wallet = Wallet::factory()->create([
        'account_id' => $this->account->id,
        'name' => 'Bank Danamon',
    ]);

    Livewire::test(ListWallets::class)
        ->callTableAction(EditAction::class, $wallet, data: [
            'name' => 'Bank Danamon Bisnis',
        ])
        ->assertHasNoTableActionErrors();

    expect($wallet->fresh()->name)->toBe('Bank Danamon Bisnis');
});

test('can soft-delete wallet', function () {
    $wallet = Wallet::factory()->create([
        'account_id' => $this->account->id,
        'name' => 'Dompet Lama',
    ]);

    Livewire::test(ListWallets::class)
        ->callTableAction(DeleteAction::class, $wallet)
        ->assertHasNoTableActionErrors();

    expect($wallet->fresh()->trashed())->toBeTrue();
});
