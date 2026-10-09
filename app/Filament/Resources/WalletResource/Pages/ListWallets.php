<?php

declare(strict_types=1);

namespace App\Filament\Resources\WalletResource\Pages;

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use App\Filament\Resources\WalletResource;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Wallet;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ListWallets extends ListRecords
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->using(function (array $data, string $model): Model {
                    return DB::transaction(function () use ($data, $model): Model {
                        $initialBalance = (float) ($data['initial_balance'] ?? ($data['current_balance'] ?? 0));
                        unset($data['initial_balance']);

                        $data['current_balance'] = $initialBalance;
                        /** @var Wallet $wallet */
                        $wallet = $model::create($data);

                        if ($initialBalance > 0) {
                            $category = Category::firstOrCreate(
                                [
                                    'account_id' => $wallet->account_id,
                                    'name' => 'SYSTEM_INITIAL_BALANCE',
                                ],
                                [
                                    'type' => CategoryType::Income,
                                    'is_system' => true,
                                    'icon' => 'heroicon-o-sparkles',
                                    'color' => 'success',
                                    'order' => 1,
                                    'status' => CategoryStatus::Active,
                                ]
                            );

                            Transaction::create([
                                'happened_at' => now(),
                                'account_id' => $wallet->account_id,
                                'wallet_id' => $wallet->id,
                                'category_id' => $category->id,
                                'type' => 'transaction',
                                'direction' => 1,
                                'amount' => $initialBalance,
                                'category_name' => $category->name,
                                'wallet_name' => $wallet->name,
                                'note' => 'Saldo awal',
                            ]);
                        }

                        return $wallet;
                    });
                }),
        ];
    }
}
