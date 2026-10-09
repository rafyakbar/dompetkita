<?php

use App\Filament\Resources\WalletResource;
use App\Filament\Resources\WalletResource\Pages\ListWallets;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
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

test('can create wallet with initial balance and generates SYSTEM_INITIAL_BALANCE transaction', function () {
    Livewire::test(ListWallets::class)
        ->callAction(CreateAction::class, data: [
            'name' => 'BCA Bisnis',
            'initial_balance' => 500000,
            'color' => '#10b981',
            'allow_minus' => true,
        ])
        ->assertHasNoActionErrors();

    $wallet = Wallet::where('account_id', $this->account->id)->where('name', 'BCA Bisnis')->firstOrFail();

    expect((float) $wallet->current_balance)->toBe(500000.0);

    $transaction = Transaction::where('wallet_id', $wallet->id)->first();
    expect($transaction)->not->toBeNull()
        ->and((float) $transaction->amount)->toBe(500000.0)
        ->and($transaction->direction)->toBe(1)
        ->and($transaction->type)->toBe('transaction')
        ->and($transaction->category_name)->toBe('SYSTEM_INITIAL_BALANCE')
        ->and($transaction->account_id)->toBe($this->account->id);
});

test('creating wallet with zero initial balance does not generate transaction', function () {
    Livewire::test(ListWallets::class)
        ->callAction(CreateAction::class, data: [
            'name' => 'Dompet Kosong',
            'initial_balance' => 0,
        ])
        ->assertHasNoActionErrors();

    $wallet = Wallet::where('account_id', $this->account->id)->where('name', 'Dompet Kosong')->firstOrFail();

    expect((float) $wallet->current_balance)->toBe(0.0)
        ->and(Transaction::where('wallet_id', $wallet->id)->count())->toBe(0);
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

test('wallet form icon field has full width column span and responsive columns', function () {
    $page = new ListWallets;
    $schema = Schema::make($page);
    $form = WalletResource::form($schema);

    $iconField = collect($form->getComponents())->first(fn ($c) => $c->getName() === 'icon');

    expect($iconField)->not->toBeNull()
        ->and($iconField->getColumnSpan('default'))->toBe('full')
        ->and($iconField->getColumns())->toBe([
            'default' => 1,
            'lg' => 3,
            '2xl' => 5,
        ]);
});
