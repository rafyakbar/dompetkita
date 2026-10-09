<?php

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use App\Filament\Pages\Tenancy\RegisterAccount;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\DefaultCategorySeeder;
use Livewire\Livewire;

test('seeder creates 8 system categories and 14 user categories for account', function () {
    $account = Account::factory()->create();

    DefaultCategorySeeder::seedForAccount($account);

    $categories = Category::where('account_id', $account->id)->get();

    // 8 system + 14 user categories = 22 categories
    expect($categories)->toHaveCount(22);

    $systemCategories = $categories->where('is_system', true);
    expect($systemCategories)->toHaveCount(8);

    // Verify 8 system categories
    $expectedSystem = [
        'SYSTEM_INITIAL_BALANCE' => CategoryType::Income,
        'SYSTEM_TRANSFER_IN' => CategoryType::Income,
        'SYSTEM_DEBT_RECEIVED' => CategoryType::Income,
        'SYSTEM_DEBT_COLLECTED' => CategoryType::Income,
        'SYSTEM_TRANSFER_OUT' => CategoryType::Expense,
        'SYSTEM_TRANSFER_FEE' => CategoryType::Expense,
        'SYSTEM_DEBT_GIVEN' => CategoryType::Expense,
        'SYSTEM_DEBT_REPAYMENT' => CategoryType::Expense,
    ];

    foreach ($expectedSystem as $name => $type) {
        $cat = $categories->firstWhere('name', $name);
        expect($cat)->not->toBeNull()
            ->and($cat->type)->toBe($type)
            ->and($cat->is_system)->toBeTrue()
            ->and($cat->status)->toBe(CategoryStatus::Active);
    }

    // Verify user categories
    $userCategories = $categories->where('is_system', false);
    expect($userCategories)->toHaveCount(14);

    $expectedUserIncome = ['Gaji', 'Freelance', 'Investasi', 'Lainnya'];
    foreach ($expectedUserIncome as $name) {
        $cat = $userCategories->where('type', CategoryType::Income)->firstWhere('name', $name);
        expect($cat)->not->toBeNull()
            ->and($cat->is_system)->toBeFalse()
            ->and($cat->status)->toBe(CategoryStatus::Active);
    }

    $expectedUserExpense = [
        'Dapur',
        'Belanja',
        'Transportasi',
        'Tagihan & Utilitas',
        'Pendidikan',
        'Kesehatan',
        'Properti',
        'Hiburan',
        'Investasi',
        'Lainnya',
    ];
    foreach ($expectedUserExpense as $name) {
        $cat = $userCategories->where('type', CategoryType::Expense)->firstWhere('name', $name);
        expect($cat)->not->toBeNull()
            ->and($cat->is_system)->toBeFalse()
            ->and($cat->status)->toBe(CategoryStatus::Active);
    }
});

test('seeder is idempotent and does not create duplicates on multiple runs', function () {
    $account = Account::factory()->create();

    DefaultCategorySeeder::seedForAccount($account);
    DefaultCategorySeeder::seedForAccount($account);

    expect(Category::where('account_id', $account->id)->count())->toBe(22);
});

test('registering new account automatically seeds default system and user categories', function () {
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

    expect($account->categories()->where('is_system', true)->count())->toBe(8)
        ->and($account->categories()->where('is_system', false)->count())->toBe(14);
});
