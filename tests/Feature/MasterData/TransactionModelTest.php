<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\CarbonInterface;

test('transaction model has valid relationships and casts', function () {
    $account = Account::factory()->create();
    $wallet = Wallet::factory()->create(['account_id' => $account->id]);
    $category = Category::factory()->create(['account_id' => $account->id]);

    $transaction = Transaction::factory()->create([
        'account_id' => $account->id,
        'wallet_id' => $wallet->id,
        'category_id' => $category->id,
        'direction' => 1,
        'amount' => 75000.50,
        'happened_at' => now(),
    ]);

    expect($transaction->account->id)->toBe($account->id)
        ->and($transaction->wallet->id)->toBe($wallet->id)
        ->and($transaction->category->id)->toBe($category->id)
        ->and($transaction->direction)->toBe(1)
        ->and((float) $transaction->amount)->toBe(75000.50)
        ->and($transaction->happened_at)->toBeInstanceOf(CarbonInterface::class);

    expect($account->transactions)->toHaveCount(1)
        ->and($wallet->transactions)->toHaveCount(1)
        ->and($category->transactions)->toHaveCount(1);
});

test('transaction supports soft deletion', function () {
    $transaction = Transaction::factory()->create();

    $transaction->delete();

    expect($transaction->trashed())->toBeTrue()
        ->and(Transaction::find($transaction->id))->toBeNull()
        ->and(Transaction::withTrashed()->find($transaction->id))->not->toBeNull();
});
