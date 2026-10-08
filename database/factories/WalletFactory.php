<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => fake()->unique()->word().' Wallet',
            'icon' => 'heroicon-o-wallet',
            'color' => '#10b981',
            'current_balance' => 0.00,
            'allow_minus' => false,
        ];
    }
}
