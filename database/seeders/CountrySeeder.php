<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

        $records = [];
        foreach ($countries as $country) {
            $now = now();

            $records[] = [
                'id' => $country['id'],
                'created_at' => $now,
                'updated_at' => $now,
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
            ];
        }

        DB::table('countries')->insert($records);
    }
}
