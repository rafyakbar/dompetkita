<?php

use Illuminate\Support\Collection;

function country_with_currency_and_symbol($state = null): Collection|string {
    $countries = collect(countries())->mapWithKeys(function ($country) {
        try {
            $currency = currency($country['currency']);
            return [
                $country['currency'] => sprintf('%s - %s - %s (%s)',$country['name'], $currency->getCurrency(), $currency->getName(), $currency->getSymbol())
            ];
        } catch (\Exception $e) {
            return [null => null];
        }
    })->filter();

    if($state) {
        return $countries->get($state);
    }

    return $countries;
}

function month_ordinal_numbers(): Collection
{
    return collect(range(1, 31))->map(fn ($day) => sprintf('%s%s', $day, match ($day) {
        1 => 'st',
        2 => 'nd',
        3 => 'rd',
        default => 'th'
    }));
}

function current_currency(?string $currency = null): string
{
    if (!blank($currency)) {
        return $currency;
    }

    try {
        $tenant = \Filament\Facades\Filament::getTenant();
        if ($tenant) {
            $wallet = $tenant->wallets()->first();
            if ($wallet && !blank($wallet->currency_code)) {
                return $wallet->currency_code;
            }
        }
    } catch (\Throwable $e) {
    }

    return config('app.currency', 'IDR');
}

function format_money($amount, ?string $currency = null, bool $isFloat = false): string
{
    if (blank($amount)) {
        $amount = 0;
    }

    $currency = current_currency($currency);

    try {
        if ($isFloat) {
            return (string) money((float) $amount, $currency, true);
        }
        return (string) money((int) $amount, $currency, false);
    } catch (\Throwable $e) {
        return (string) $amount;
    }
}