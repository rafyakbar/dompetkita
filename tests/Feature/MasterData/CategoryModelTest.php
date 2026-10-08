<?php

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use App\Models\Account;
use App\Models\Category;
use Illuminate\Support\Facades\Schema;

test('categories table has expected columns following standard ordering', function () {
    expect(Schema::hasTable('categories'))->toBeTrue();

    $columns = Schema::getColumnListing('categories');
    expect($columns)->toContain(
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'account_id',
        'name',
        'slug',
        'type',
        'is_system',
        'icon',
        'color',
        'order',
        'status'
    );
});

test('category belongs to account and casts attributes properly', function () {
    $account = Account::factory()->create();
    $category = Category::factory()->create([
        'account_id' => $account->id,
        'name' => 'Gaji Pokok',
        'type' => CategoryType::Income,
        'status' => CategoryStatus::Active,
        'is_system' => false,
        'order' => 1,
    ]);

    expect($category->account->id)->toBe($account->id)
        ->and($category->slug)->toBe('gaji-pokok')
        ->and($category->type)->toBe(CategoryType::Income)
        ->and($category->status)->toBe(CategoryStatus::Active)
        ->and($category->is_system)->toBeFalse()
        ->and($category->order)->toBe(1);

    expect($account->categories)->toHaveCount(1)
        ->and($account->categories->first()->name)->toBe('Gaji Pokok');
});

test('category query scopes filter correctly', function () {
    $account = Account::factory()->create();

    Category::factory()->create([
        'account_id' => $account->id,
        'type' => CategoryType::Income,
        'status' => CategoryStatus::Active,
    ]);

    Category::factory()->create([
        'account_id' => $account->id,
        'type' => CategoryType::Expense,
        'status' => CategoryStatus::Active,
    ]);

    Category::factory()->create([
        'account_id' => $account->id,
        'type' => CategoryType::Expense,
        'status' => CategoryStatus::Inactive,
    ]);

    expect(Category::where('account_id', $account->id)->active()->count())->toBe(2)
        ->and(Category::where('account_id', $account->id)->income()->count())->toBe(1)
        ->and(Category::where('account_id', $account->id)->expense()->count())->toBe(2);
});
