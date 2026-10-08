<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Country extends Model
{
    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * Get distinct currency options formatted as ['IDR' => 'IDR - Indonesian rupiah (Rp)', ...]
     *
     * @return array<string, string>
     */
    public static function getCurrencyOptions(): array
    {
        return Cache::rememberForever('country_currency_options', function (): array {
            $countries = static::query()
                ->select(['currency', 'currency_name', 'currency_symbol'])
                ->whereNotNull('currency')
                ->where('currency', '!=', '')
                ->orderBy('currency')
                ->get()
                ->unique('currency');

            if ($countries->isEmpty()) {
                $jsonPath = database_path('seeders/data/countries.json');
                if (file_exists($jsonPath)) {
                    $json = json_decode((string) file_get_contents($jsonPath), true) ?? [];
                    $countries = collect($json)->unique('currency')->sortBy('currency');
                }
            }

            $options = [];

            foreach ($countries as $country) {
                $currency = is_array($country) ? $country['currency'] : $country->currency;
                $name = is_array($country) ? $country['currency_name'] : $country->currency_name;
                $symbol = is_array($country) ? ($country['currency_symbol'] ?? null) : $country->currency_symbol;
                $symbolPart = $symbol ? " ({$symbol})" : '';
                $options[$currency] = "{$currency} - {$name}{$symbolPart}";
            }

            return $options;
        });
    }
}
