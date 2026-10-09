<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'happened_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'account_id' => Account::factory(),
            'wallet_id' => Wallet::factory(),
            'category_id' => Category::factory(),
            'transfer_id' => null,
            'type' => 'transaction',
            'direction' => fake()->randomElement([1, -1]),
            'amount' => fake()->randomFloat(2, 10000, 500000),
            'category_name' => fake()->word(),
            'wallet_name' => fake()->word(),
            'note' => fake()->sentence(),
        ];
    }
}
