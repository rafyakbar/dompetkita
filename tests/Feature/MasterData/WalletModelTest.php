<?php

use App\Models\Account;
use App\Models\Wallet;
use Illuminate\Support\Facades\Schema;

test('wallets table has expected columns following standard ordering', function () {
    expect(Schema::hasTable('wallets'))->toBeTrue();

    $columns = Schema::getColumnListing('wallets');
    expect($columns)->toContain(
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'account_id',
        'name',
        'slug',
        'icon',
        'color',
        'current_balance',
        'allow_minus'
    );
});

test('wallet belongs to account and automatically generates slug', function () {
    $account = Account::factory()->create();
    $wallet = Wallet::factory()->create([
        'account_id' => $account->id,
        'name' => 'Bank BCA',
        'current_balance' => 1500000.50,
        'allow_minus' => false,
    ]);

    expect($wallet->account->id)->toBe($account->id)
        ->and($wallet->slug)->toBe('bank-bca')
        ->and($wallet->current_balance)->toBe('1500000.50')
        ->and($wallet->allow_minus)->toBeFalse();

    expect($account->wallets)->toHaveCount(1)
        ->and($account->wallets->first()->name)->toBe('Bank BCA');
});

test('wallet supports soft deletes', function () {
    $wallet = Wallet::factory()->create();
    $walletId = $wallet->id;

    $wallet->delete();

    expect(Wallet::find($walletId))->toBeNull()
        ->and(Wallet::withTrashed()->find($walletId))->not->toBeNull();
});
