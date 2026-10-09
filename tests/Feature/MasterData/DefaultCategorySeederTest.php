<?php

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use App\Filament\Pages\Tenancy\RegisterAccount;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\DefaultCategorySeeder;
use Livewire\Livewire;

test('seeder creates 4 default system categories for account', function () {
    $account = Account::factory()->create();

    DefaultCategorySeeder::seedForAccount($account);

    $categories = Category::where('account_id', $account->id)->get();

    expect($categories)->toHaveCount(4);

    $initialBalance = $categories->firstWhere('name', 'SYSTEM_INITIAL_BALANCE');
    expect($initialBalance)->not->toBeNull()
        ->and($initialBalance->type)->toBe(CategoryType::Income)
        ->and($initialBalance->is_system)->toBeTrue()
        ->and($initialBalance->status)->toBe(CategoryStatus::Active);

    $transferIn = $categories->firstWhere('name', 'SYSTEM_TRANSFER_IN');
    expect($transferIn)->not->toBeNull()
        ->and($transferIn->type)->toBe(CategoryType::Income)
        ->and($transferIn->is_system)->toBeTrue()
        ->and($transferIn->status)->toBe(CategoryStatus::Active);

    $transferOut = $categories->firstWhere('name', 'SYSTEM_TRANSFER_OUT');
    expect($transferOut)->not->toBeNull()
        ->and($transferOut->type)->toBe(CategoryType::Expense)
        ->and($transferOut->is_system)->toBeTrue()
        ->and($transferOut->status)->toBe(CategoryStatus::Active);

    $adminFee = $categories->firstWhere('name', 'SYSTEM_TRANSFER_FEE');
    expect($adminFee)->not->toBeNull()
        ->and($adminFee->type)->toBe(CategoryType::Expense)
        ->and($adminFee->is_system)->toBeTrue()
        ->and($adminFee->status)->toBe(CategoryStatus::Active);
});

test('seeder is idempotent and does not create duplicates on multiple runs', function () {
    $account = Account::factory()->create();

    DefaultCategorySeeder::seedForAccount($account);
    DefaultCategorySeeder::seedForAccount($account);

    expect(Category::where('account_id', $account->id)->count())->toBe(4);
});

test('registering new account automatically seeds default system categories', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(RegisterAccount::class)
        ->fillForm([
            'name' => 'Akun Usaha Baru',
            'currency_code' => 'IDR',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $account = Account::where('slug', 'akun-usaha-baru')->firstOrFail();

    expect($account->categories()->where('is_system', true)->count())->toBe(4);
});
