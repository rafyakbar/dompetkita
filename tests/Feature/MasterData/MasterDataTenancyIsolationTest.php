<?php

use App\Enums\CategoryType;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\CategoryResource\Pages\ListCategories;
use App\Filament\Resources\WalletResource;
use App\Filament\Resources\WalletResource\Pages\ListWallets;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use App\Models\Wallet;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $panel = Filament::getCurrentOrDefaultPanel();
    Filament::setCurrentPanel($panel);
    WalletResource::registerTenancyModelGlobalScope($panel);
    CategoryResource::registerTenancyModelGlobalScope($panel);

    $this->userA = User::factory()->create();
    $this->accountA = Account::factory()->create(['owner_id' => $this->userA->id]);
    $this->accountA->members()->attach($this->userA->id, [
        'email' => $this->userA->email,
        'role' => 'owner',
        'status' => 'active',
        'confirmed_at' => now(),
    ]);

    $this->userB = User::factory()->create();
    $this->accountB = Account::factory()->create(['owner_id' => $this->userB->id]);
    $this->accountB->members()->attach($this->userB->id, [
        'email' => $this->userB->email,
        'role' => 'owner',
        'status' => 'active',
        'confirmed_at' => now(),
    ]);
});

test('distinct tenants can create wallets and categories with the same name without conflict', function () {
    // Tenant A creates "Kas Utama"
    $walletA = Wallet::factory()->create([
        'account_id' => $this->accountA->id,
        'name' => 'Kas Utama',
    ]);

    // Tenant B creates "Kas Utama"
    $walletB = Wallet::factory()->create([
        'account_id' => $this->accountB->id,
        'name' => 'Kas Utama',
    ]);

    expect($walletA->name)->toBe('Kas Utama')
        ->and($walletB->name)->toBe('Kas Utama')
        ->and($walletA->account_id)->not->toBe($walletB->account_id);

    // Tenant A creates Category "Operasional"
    $catA = Category::factory()->create([
        'account_id' => $this->accountA->id,
        'name' => 'Operasional',
        'type' => CategoryType::Expense,
    ]);

    // Tenant B creates Category "Operasional"
    $catB = Category::factory()->create([
        'account_id' => $this->accountB->id,
        'name' => 'Operasional',
        'type' => CategoryType::Expense,
    ]);

    expect($catA->name)->toBe('Operasional')
        ->and($catB->name)->toBe('Operasional')
        ->and($catA->account_id)->not->toBe($catB->account_id);
});

test('tenant A cannot view tenant B wallets or categories in resource table', function () {
    $walletA = Wallet::factory()->create(['account_id' => $this->accountA->id, 'name' => 'Dompet Tenant A']);
    $walletB = Wallet::factory()->create(['account_id' => $this->accountB->id, 'name' => 'Dompet Tenant B']);

    $this->actingAs($this->userA);
    Filament::setTenant($this->accountA);

    Livewire::test(ListWallets::class)
        ->assertCanSeeTableRecords([$walletA])
        ->assertCanNotSeeTableRecords([$walletB]);

    $catA = Category::factory()->create(['account_id' => $this->accountA->id, 'name' => 'Kategori Tenant A']);
    $catB = Category::factory()->create(['account_id' => $this->accountB->id, 'name' => 'Kategori Tenant B']);

    Livewire::test(ListCategories::class)
        ->assertCanSeeTableRecords([$catA])
        ->assertCanNotSeeTableRecords([$catB]);
});

test('soft-deleted wallet name can be re-used within the same tenant', function () {
    $this->actingAs($this->userA);
    Filament::setTenant($this->accountA);

    $oldWallet = Wallet::factory()->create([
        'account_id' => $this->accountA->id,
        'name' => 'Tabungan Emas',
    ]);

    $oldWallet->delete();

    Livewire::test(ListWallets::class)
        ->callAction(CreateAction::class, data: [
            'name' => 'Tabungan Emas',
        ])
        ->assertHasNoActionErrors();

    expect(Wallet::where('account_id', $this->accountA->id)->where('name', 'Tabungan Emas')->whereNull('deleted_at')->exists())->toBeTrue();
});
