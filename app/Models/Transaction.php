<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TransactionFactory;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'happened_at',
    'account_id',
    'wallet_id',
    'category_id',
    'transfer_id',
    'type',
    'direction',
    'amount',
    'category_name',
    'wallet_name',
    'note',
])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'happened_at' => 'datetime',
            'direction' => 'integer',
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction): void {
            if (empty($transaction->account_id) && class_exists(Filament::class) && Filament::getTenant()) {
                $transaction->account_id = Filament::getTenant()->getKey();
            }

            if (empty($transaction->happened_at)) {
                $transaction->happened_at = now();
            }
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
