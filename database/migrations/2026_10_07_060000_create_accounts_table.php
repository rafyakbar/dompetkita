<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name')->index();
            $table->string('slug')->index();
            $table->string('currency_code', 3)->default('IDR');
            $table->text('description')->nullable();

            $table->unique(['owner_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
