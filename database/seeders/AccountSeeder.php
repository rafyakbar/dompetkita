<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AccountRole;
use App\Enums\MemberStatus;
use App\Models\Account;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstWhere('email', 'admin@email.com') ?? User::first();

        if (! $user) {
            return;
        }

        $account = Account::firstOrCreate(
            ['slug' => 'keuangan-pribadi'],
            [
                'name' => 'Keuangan Pribadi',
                'owner_id' => $user->id,
                'currency_code' => 'IDR',
                'description' => 'Akun pembukuan keuangan pribadi default',
            ]
        );

        $account->members()->syncWithoutDetaching([
            $user->id => [
                'email' => $user->email,
                'role' => AccountRole::Owner->value,
                'status' => MemberStatus::Active->value,
                'confirmed_at' => now(),
            ],
        ]);

        DefaultCategorySeeder::seedForAccount($account);

        Wallet::firstOrCreate(
            [
                'account_id' => $account->id,
                'name' => 'Dompet Utama',
            ],
            [
                'slug' => 'dompet-utama',
                'icon' => 'heroicon-o-wallet',
                'color' => '#10b981',
                'current_balance' => 0.00,
                'allow_minus' => false,
            ]
        );
    }
}
