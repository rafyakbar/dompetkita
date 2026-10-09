<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use App\Models\Account;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DefaultCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (Account::all() as $account) {
            self::seedForAccount($account);
        }
    }

    public static function seedForAccount(Account $account): void
    {
        $defaultCategories = [
            [
                'name' => 'SYSTEM_INITIAL_BALANCE',
                'type' => CategoryType::Income,
                'icon' => 'heroicon-o-sparkles',
                'color' => 'success',
                'order' => 1,
            ],
            [
                'name' => 'SYSTEM_TRANSFER_IN',
                'type' => CategoryType::Income,
                'icon' => 'heroicon-o-arrow-down-left',
                'color' => 'success',
                'order' => 2,
            ],
            [
                'name' => 'SYSTEM_TRANSFER_OUT',
                'type' => CategoryType::Expense,
                'icon' => 'heroicon-o-arrow-up-right',
                'color' => 'danger',
                'order' => 3,
            ],
            [
                'name' => 'SYSTEM_TRANSFER_FEE',
                'type' => CategoryType::Expense,
                'icon' => 'heroicon-o-banknotes',
                'color' => 'warning',
                'order' => 4,
            ],
        ];

        foreach ($defaultCategories as $cat) {
            Category::firstOrCreate(
                [
                    'account_id' => $account->id,
                    'name' => $cat['name'],
                ],
                [
                    'slug' => Str::slug($cat['name']),
                    'type' => $cat['type'],
                    'is_system' => true,
                    'icon' => $cat['icon'],
                    'color' => $cat['color'],
                    'order' => $cat['order'],
                    'status' => CategoryStatus::Active,
                ]
            );
        }
    }
}
