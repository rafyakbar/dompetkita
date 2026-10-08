<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Cache::forget('country_currency_options');

        $jsonPath = database_path('seeders/data/countries.json');

        if (! File::exists($jsonPath)) {
            return;
        }

        $countries = json_decode(File::get($jsonPath), true);

        if (! is_array($countries)) {
            return;
        }

        $now = now();
        $records = [];

        foreach ($countries as $country) {
            $records[] = [
                'id' => $country['id'],
                'region' => $country['region'] ?? null,
                'subregion' => $country['subregion'] ?? null,
                'name' => $country['name'],
                'iso2' => $country['iso2'],
                'iso3' => $country['iso3'],
                'numeric_code' => $country['numeric_code'] ?? null,
                'phonecode' => $country['phonecode'] ?? null,
                'capital' => $country['capital'] ?? null,
                'currency' => $country['currency'],
                'currency_name' => $country['currency_name'],
                'currency_symbol' => $country['currency_symbol'] ?? null,
                'nationality' => $country['nationality'] ?? null,
                'latitude' => $country['latitude'] ?? null,
                'longitude' => $country['longitude'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($records, 50) as $chunk) {
            Country::upsert(
                $chunk,
                ['id'],
                [
                    'region', 'subregion', 'name', 'iso2', 'iso3',
                    'numeric_code', 'phonecode', 'capital', 'currency',
                    'currency_name', 'currency_symbol', 'nationality',
                    'latitude', 'longitude', 'updated_at',
                ]
            );
        }
    }
}
