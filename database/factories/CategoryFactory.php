<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use App\Models\Account;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => fake()->unique()->word().' Category',
            'type' => fake()->randomElement([CategoryType::Income, CategoryType::Expense]),
            'is_system' => false,
            'icon' => 'heroicon-o-tag',
            'color' => '#3b82f6',
            'order' => 0,
            'status' => CategoryStatus::Active,
        ];
    }
}
