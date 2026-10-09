<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use Database\Factories\CategoryFactory;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['account_id', 'name', 'slug', 'type', 'is_system', 'icon', 'color', 'order', 'status'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => CategoryType::class,
            'status' => CategoryStatus::class,
            'is_system' => 'boolean',
            'order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Category $category): void {
            if (empty($category->account_id) && class_exists(Filament::class) && Filament::getTenant()) {
                $category->account_id = Filament::getTenant()->getKey();
            }

            if (empty($category->slug)) {
                $baseSlug = Str::slug($category->name);
                $slug = $baseSlug;
                $counter = 1;

                while (static::where('account_id', $category->account_id)->where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$counter;
                    $counter++;
                }

                $category->slug = $slug;
            }
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CategoryStatus::Active);
    }

    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', CategoryType::Income);
    }

    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', CategoryType::Expense);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
