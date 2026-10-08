<?php

use App\Models\Country;
use Database\Seeders\CountrySeeder;
use Illuminate\Support\Facades\Schema;

test('countries table has expected columns', function () {
    expect(Schema::hasTable('countries'))->toBeTrue()
        ->and(Schema::hasColumns('countries', [
            'id', 'region', 'subregion', 'name', 'iso2', 'iso3',
            'numeric_code', 'phonecode', 'capital', 'currency',
            'currency_name', 'currency_symbol', 'nationality',
            'latitude', 'longitude', 'created_at', 'updated_at',
        ]))->toBeTrue();
});

test('country seeder seeds all countries from json', function () {
    $this->seed(CountrySeeder::class);

    expect(Country::count())->toBe(250);

    $indonesia = Country::where('iso2', 'ID')->first();

    expect($indonesia)->not->toBeNull()
        ->and($indonesia->name)->toBe('Indonesia')
        ->and($indonesia->currency)->toBe('IDR')
        ->and($indonesia->currency_symbol)->toBe('Rp');
});

test('country model provides unique currency options', function () {
    $this->seed(CountrySeeder::class);

    $options = Country::getCurrencyOptions();

    expect($options)->toBeArray()
        ->and($options)->toHaveKey('IDR')
        ->and($options['IDR'])->toBe('IDR - Indonesian rupiah (Rp)')
        ->and($options)->toHaveKey('USD')
        ->and(count($options))->toBe(154);
});
