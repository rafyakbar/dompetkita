<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name')->index();
            $table->string('iso2', 2)->unique();
            $table->string('iso3', 3)->unique();
            $table->string('numeric_code', 10)->nullable();
            $table->string('phonecode', 20)->nullable();
            $table->string('capital')->nullable();
            $table->string('currency', 10)->index();
            $table->string('currency_name')->index();
            $table->string('currency_symbol', 20)->nullable();
            $table->string('region')->nullable()->index();
            $table->string('subregion')->nullable()->index();
            $table->string('nationality')->nullable()->index();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
