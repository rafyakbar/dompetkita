<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WalletFactory;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['account_id', 'name', 'slug', 'icon', 'color', 'current_balance', 'allow_minus'])]
class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'current_balance' => 'decimal:2',
            'allow_minus' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Wallet $wallet): void {
            if (empty($wallet->account_id) && class_exists(Filament::class) && Filament::getTenant()) {
                $wallet->account_id = Filament::getTenant()->getKey();
            }

            if (empty($wallet->slug)) {
                $baseSlug = Str::slug($wallet->name);
                $slug = $baseSlug;
                $counter = 1;

                while (static::where('account_id', $wallet->account_id)->where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$counter;
                    $counter++;
                }

                $wallet->slug = $slug;
            }
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
