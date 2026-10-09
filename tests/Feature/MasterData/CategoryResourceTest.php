<?php

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use App\Filament\Resources\CategoryResource\Pages\ListCategories;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Filament\Actions\CreateAction;
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

test('category resource list page renders correctly', function () {
    $category = Category::factory()->create([
        'account_id' => $this->account->id,
        'name' => 'Makanan & Minuman',
        'is_system' => false,
    ]);

    Livewire::test(ListCategories::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$category]);
});

test('system categories are not displayed in category list table', function () {
    $systemCategory = Category::factory()->create([
        'account_id' => $this->account->id,
        'name' => 'SYSTEM_TRANSFER_IN',
        'is_system' => true,
    ]);

    $customCategory = Category::factory()->create([
        'account_id' => $this->account->id,
        'name' => 'Hiburan',
        'is_system' => false,
    ]);

    Livewire::test(ListCategories::class)
        ->assertCanSeeTableRecords([$customCategory])
        ->assertCanNotSeeTableRecords([$systemCategory]);
});

test('can create category with scoped unique validation', function () {
    Livewire::test(ListCategories::class)
        ->callAction(CreateAction::class, data: [
            'name' => 'Transportasi',
            'type' => CategoryType::Expense->value,
            'status' => CategoryStatus::Active->value,
            'order' => 10,
        ])
        ->assertHasNoActionErrors();

    expect(Category::where('account_id', $this->account->id)->where('name', 'Transportasi')->exists())->toBeTrue();

    // Duplicate name inside same tenant must fail
    Livewire::test(ListCategories::class)
        ->callAction(CreateAction::class, data: [
            'name' => 'Transportasi',
            'type' => CategoryType::Expense->value,
        ])
        ->assertHasActionErrors(['name']);
});
