<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use App\Models\Account;
use App\Models\Category;
use Illuminate\Database\Seeder;

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
                'name' => 'Transfer Masuk',
                'type' => CategoryType::Income,
                'icon' => 'heroicon-o-arrow-down-left',
                'color' => 'success',
                'order' => 1,
            ],
            [
                'name' => 'Transfer Keluar',
                'type' => CategoryType::Expense,
                'icon' => 'heroicon-o-arrow-up-right',
                'color' => 'danger',
                'order' => 2,
            ],
            [
                'name' => 'Biaya Admin Transfer',
                'type' => CategoryType::Expense,
                'icon' => 'heroicon-o-banknotes',
                'color' => 'warning',
                'order' => 3,
            ],
        ];

        foreach ($defaultCategories as $cat) {
            Category::firstOrCreate(
                [
                    'account_id' => $account->id,
                    'name' => $cat['name'],
                ],
                [
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
