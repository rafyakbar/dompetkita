<?php

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

test('category types have expected values, labels, and colors', function () {
    expect(CategoryType::Income->value)->toBe('income')
        ->and(CategoryType::Expense->value)->toBe('expense')
        ->and(CategoryType::Income)->toBeInstanceOf(HasLabel::class)
        ->and(CategoryType::Income)->toBeInstanceOf(HasColor::class)
        ->and(CategoryType::Income->getLabel())->toBe('Pemasukan')
        ->and(CategoryType::Expense->getLabel())->toBe('Pengeluaran')
        ->and(CategoryType::Income->getColor())->toBe('success')
        ->and(CategoryType::Expense->getColor())->toBe('danger');
});

test('category statuses have expected values, labels, and colors', function () {
    expect(CategoryStatus::Active->value)->toBe('active')
        ->and(CategoryStatus::Inactive->value)->toBe('inactive')
        ->and(CategoryStatus::Active)->toBeInstanceOf(HasLabel::class)
        ->and(CategoryStatus::Active)->toBeInstanceOf(HasColor::class)
        ->and(CategoryStatus::Active->getLabel())->toBe('Aktif')
        ->and(CategoryStatus::Inactive->getLabel())->toBe('Nonaktif')
        ->and(CategoryStatus::Active->getColor())->toBe('success')
        ->and(CategoryStatus::Inactive->getColor())->toBe('gray');
});
