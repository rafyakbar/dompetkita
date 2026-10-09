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
            // --- SYSTEM CATEGORIES (is_system = true) ---
            [
                'name' => 'SYSTEM_INITIAL_BALANCE',
                'slug' => 'system-initial-balance',
                'type' => CategoryType::Income,
                'is_system' => true,
                'icon' => 'heroicon-o-sparkles',
                'color' => 'success',
                'order' => 1,
            ],
            [
                'name' => 'SYSTEM_TRANSFER_IN',
                'slug' => 'system-transfer-in',
                'type' => CategoryType::Income,
                'is_system' => true,
                'icon' => 'heroicon-o-arrow-down-left',
                'color' => 'success',
                'order' => 2,
            ],
            [
                'name' => 'SYSTEM_DEBT_RECEIVED',
                'slug' => 'system-debt-received',
                'type' => CategoryType::Income,
                'is_system' => true,
                'icon' => 'heroicon-o-inbox-arrow-down',
                'color' => 'info',
                'order' => 3,
            ],
            [
                'name' => 'SYSTEM_DEBT_COLLECTED',
                'slug' => 'system-debt-collected',
                'type' => CategoryType::Income,
                'is_system' => true,
                'icon' => 'heroicon-o-check-badge',
                'color' => 'success',
                'order' => 4,
            ],
            [
                'name' => 'SYSTEM_TRANSFER_OUT',
                'slug' => 'system-transfer-out',
                'type' => CategoryType::Expense,
                'is_system' => true,
                'icon' => 'heroicon-o-arrow-up-right',
                'color' => 'danger',
                'order' => 5,
            ],
            [
                'name' => 'SYSTEM_TRANSFER_FEE',
                'slug' => 'system-transfer-fee',
                'type' => CategoryType::Expense,
                'is_system' => true,
                'icon' => 'heroicon-o-banknotes',
                'color' => 'warning',
                'order' => 6,
            ],
            [
                'name' => 'SYSTEM_DEBT_GIVEN',
                'slug' => 'system-debt-given',
                'type' => CategoryType::Expense,
                'is_system' => true,
                'icon' => 'heroicon-o-paper-airplane',
                'color' => 'warning',
                'order' => 7,
            ],
            [
                'name' => 'SYSTEM_DEBT_REPAYMENT',
                'slug' => 'system-debt-repayment',
                'type' => CategoryType::Expense,
                'is_system' => true,
                'icon' => 'heroicon-o-arrow-path',
                'color' => 'danger',
                'order' => 8,
            ],

            // --- PENDAPATAN / INCOME (is_system = false) ---
            [
                'name' => 'Gaji',
                'slug' => 'gaji',
                'type' => CategoryType::Income,
                'is_system' => false,
                'icon' => 'heroicon-o-briefcase',
                'color' => '#10b981',
                'order' => 1,
            ],
            [
                'name' => 'Freelance',
                'slug' => 'freelance',
                'type' => CategoryType::Income,
                'is_system' => false,
                'icon' => 'heroicon-o-computer-desktop',
                'color' => '#06b6d4',
                'order' => 2,
            ],
            [
                'name' => 'Investasi',
                'slug' => 'investasi-income',
                'type' => CategoryType::Income,
                'is_system' => false,
                'icon' => 'heroicon-o-chart-bar',
                'color' => '#8b5cf6',
                'order' => 3,
            ],
            [
                'name' => 'Lainnya',
                'slug' => 'lainnya-income',
                'type' => CategoryType::Income,
                'is_system' => false,
                'icon' => 'heroicon-o-ellipsis-horizontal-circle',
                'color' => '#6b7280',
                'order' => 4,
            ],

            // --- PENGELUARAN / EXPENSE (is_system = false) ---
            [
                'name' => 'Dapur',
                'slug' => 'dapur',
                'type' => CategoryType::Expense,
                'is_system' => false,
                'icon' => 'heroicon-o-shopping-cart',
                'color' => '#f97316',
                'order' => 1,
            ],
            [
                'name' => 'Belanja',
                'slug' => 'belanja',
                'type' => CategoryType::Expense,
                'is_system' => false,
                'icon' => 'heroicon-o-shopping-bag',
                'color' => '#ec4899',
                'order' => 2,
            ],
            [
                'name' => 'Transportasi',
                'slug' => 'transportasi',
                'type' => CategoryType::Expense,
                'is_system' => false,
                'icon' => 'heroicon-o-truck',
                'color' => '#3b82f6',
                'order' => 3,
            ],
            [
                'name' => 'Tagihan & Utilitas',
                'slug' => 'tagihan-utilitas',
                'type' => CategoryType::Expense,
                'is_system' => false,
                'icon' => 'heroicon-o-bolt',
                'color' => '#eab308',
                'order' => 4,
            ],
            [
                'name' => 'Pendidikan',
                'slug' => 'pendidikan',
                'type' => CategoryType::Expense,
                'is_system' => false,
                'icon' => 'heroicon-o-academic-cap',
                'color' => '#6366f1',
                'order' => 5,
            ],
            [
                'name' => 'Kesehatan',
                'slug' => 'kesehatan',
                'type' => CategoryType::Expense,
                'is_system' => false,
                'icon' => 'heroicon-o-heart',
                'color' => '#ef4444',
                'order' => 6,
            ],
            [
                'name' => 'Properti',
                'slug' => 'properti',
                'type' => CategoryType::Expense,
                'is_system' => false,
                'icon' => 'heroicon-o-home',
                'color' => '#14b8a6',
                'order' => 7,
            ],
            [
                'name' => 'Hiburan',
                'slug' => 'hiburan',
                'type' => CategoryType::Expense,
                'is_system' => false,
                'icon' => 'heroicon-o-ticket',
                'color' => '#a855f7',
                'order' => 8,
            ],
            [
                'name' => 'Investasi',
                'slug' => 'investasi-expense',
                'type' => CategoryType::Expense,
                'is_system' => false,
                'icon' => 'heroicon-o-arrow-trending-up',
                'color' => '#059669',
                'order' => 9,
            ],
            [
                'name' => 'Lainnya',
                'slug' => 'lainnya-expense',
                'type' => CategoryType::Expense,
                'is_system' => false,
                'icon' => 'heroicon-o-ellipsis-horizontal-circle',
                'color' => '#9ca3af',
                'order' => 10,
            ],
        ];

        foreach ($defaultCategories as $cat) {
            Category::firstOrCreate(
                [
                    'account_id' => $account->id,
                    'name' => $cat['name'],
                    'type' => $cat['type'],
                ],
                [
                    'slug' => $cat['slug'] ?? Str::slug($cat['name']),
                    'is_system' => $cat['is_system'],
                    'icon' => $cat['icon'],
                    'color' => $cat['color'],
                    'order' => $cat['order'],
                    'status' => CategoryStatus::Active,
                ]
            );
        }
    }
}
