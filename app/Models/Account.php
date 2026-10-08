<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AccountFactory;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['owner_id', 'name', 'slug', 'currency_code', 'description'])]
class Account extends Model implements HasCurrentTenantLabel
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Account $account): void {
            if (empty($account->slug)) {
                $baseSlug = Str::slug($account->name);
                $slug = $baseSlug;
                $counter = 1;

                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$counter;
                    $counter++;
                }

                $account->slug = $slug;
            }
        });
    }

    public function getCurrentTenantLabel(): string
    {
        return 'Akun Aktif';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'account_member')
            ->withPivot(['role', 'status', 'email', 'invitation_token', 'invited_at', 'confirmed_at', 'left_at', 'revoked_at'])
            ->withTimestamps();
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }
}
