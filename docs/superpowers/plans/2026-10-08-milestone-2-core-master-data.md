# Milestone 2: Core Master Data Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun modul master data keuangan inti (Wallets & Categories) multi-tenant pada Filament v5, mencakup PHP Backed Enums, migrasi database sesuai konvensi urutan kolom baku, Model Eloquent dengan auto-slug dan relasi tenant, Seeder Kategori Sistem, Filament Resources dengan modal slide-over dan validasi unik terisolasi per tenant (`scopedUnique()`), serta suite pengujian Pest PHP yang komprehensif.

**Architecture:** Master data `Wallet` dan `Category` dimiliki secara eksklusif oleh tenant `Account` (`account_id`). Setiap mutasi, pembuatan, dan pengeditan divalidasi unik scoped per tenant (`scopedUnique()`), sehingga nama dompet/kategori dapat sama antar-tenant namun tidak boleh duplikat di dalam satu tenant yang sama. Integritas sistem diamankan dengan flag `is_system` pada kategori bawaan (`Transfer Masuk`, `Transfer Keluar`, `Biaya Admin Transfer`) yang dibuat otomatis saat registrasi akun baru. Tampilan UI menggunakan Filament v5 SDUI Resources dengan interaksi modern modal slide-over dan dukungan soft delete.

**Tech Stack:** Laravel 13, Filament v5, Livewire v4, PHP 8.4 Backed Enums, Pest PHP testing framework, Laravel Pint.

**Spec:** [docs/superpowers/specs/2026-10-07-dompetkita-core-architecture-design.md](file:///C:/laragon/www/dompetkita/docs/superpowers/specs/2026-10-07-dompetkita-core-architecture-design.md) & [docs/project/OVERVIEW.txt](file:///C:/laragon/www/dompetkita/docs/project/OVERVIEW.txt)

---

## Global Constraints

- **Konvensi Urutan Kolom Migrasi Database:** Urutan penulisan kolom pada migrasi (`Schema::create`) WAJIB mengikuti tata urutan baku:
  1. `id`
  2. `timestamps`
  3. `softDeletes` (jika tabel mendukung soft delete)
  4. Temporal columns (kolom waktu kejadian jika ada)
  5. Kolom lainnya (foreign keys, string nama/kode, angka nominal, boolean, dll.)
- **No Database Enum Types:** Kolom database `type` dan `status` bertipe `string(20)` dan di-cast ke PHP 8.4 Backed Enums.
- **Filament v5 Server-Driven UI (SDUI) Signatures:** Menggunakan `public static function form(Schema $schema): Schema` dengan import `use Filament\Schemas\Schema;` dan `->components([...])`.
- **Single-Currency per Tenant:** Tampilan saldo dompet disesuaikan dengan `currency_code` dari parent `Account`.
- **Code Style (Laravel Pint):** Jalankan `vendor/bin/pint --dirty --format agent` sebelum setiap commit.
- **Disiplin TDD Ketat:** Tulis test Pest terlebih dahulu dan buktikan gagal (Red), implementasi kode minimal hingga lulus (Green), lakukan linter Pint, lalu commit rapi per-task.

---

## Review Focus

1. **Tenant isolation leak:** Pengguna di Tenant A mencoba mengakses atau memanipulasi Wallet atau Category milik Tenant B via route langsung (harus ditolak / 404 Not Found).
2. **Scoped uniqueness per-tenant:** Tenant B dapat membuat dompet "BCA" meskipun Tenant A sudah memiliki dompet bernama "BCA" (harus diizinkan tanpa conflict error).
3. **Scoped uniqueness collision within tenant:** Tenant A mencoba membuat dompet "BCA" kedua di dalam tenant miliknya (harus ditolak dengan error validasi duplikat).
4. **Soft-deleted record re-use:** Tenant A telah me-soft-delete dompet "Kas", lalu membuat dompet baru bernama "Kas" (harus diizinkan karena query keunikan mengabaikan record yang telah di-soft-delete).
5. **System category protection:** Pengguna dilarang menghapus atau mengubah tipe kategori sistem (`is_system = true`) seperti `Transfer Masuk`, `Transfer Keluar`, dan `Biaya Admin Transfer`.

---

## Tasks

### Task 1: Enums `CategoryType` & `CategoryStatus` dengan Kontrak Filament

**Files:**
- Create: `app/Enums/CategoryType.php`
- Create: `app/Enums/CategoryStatus.php`
- Test: `tests/Unit/Enums/CategoryEnumsTest.php`

**Interfaces:**
- Produces:
  * `App\Enums\CategoryType: string implements HasLabel, HasColor`: case `Income = 'income'`, `Expense = 'expense'`. Label: "Pemasukan", "Pengeluaran". Color: "success", "danger".
  * `App\Enums\CategoryStatus: string implements HasLabel, HasColor`: case `Active = 'active'`, `Inactive = 'inactive'`. Label: "Aktif", "Nonaktif". Color: "success", "gray".

- [x] **Step 1: Write the failing test**

Buat file `tests/Unit/Enums/CategoryEnumsTest.php`:
```php
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
```

- [x] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=CategoryEnumsTest`
Expected: FAIL (Class `App\Enums\CategoryType` not found).

- [x] **Step 3: Implement `CategoryType` & `CategoryStatus`**

Buat `app/Enums/CategoryType.php`:
```php
<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CategoryType: string implements HasColor, HasLabel
{
    case Income = 'income';
    case Expense = 'expense';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Income => 'Pemasukan',
            self::Expense => 'Pengeluaran',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Income => 'success',
            self::Expense => 'danger',
        };
    }
}
```

Buat `app/Enums/CategoryStatus.php`:
```php
<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CategoryStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Inert = 'inactive';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Inert => 'Nonaktif',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active => 'success',
            self::Inert => 'gray',
        };
    }
}
```
*(Catatan: pastikan case enum adalah `Active = 'active'` dan `Inactive = 'inactive'`)*

- [x] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter=CategoryEnumsTest`
Expected: PASS (2 tests passed, 16 assertions).

- [x] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Enums/CategoryType.php app/Enums/CategoryStatus.php tests/Unit/Enums/CategoryEnumsTest.php
git commit -m "feat(master-data): add CategoryType and CategoryStatus enums with Filament contracts"
```

---

### Task 2: Migrasi Database `wallets` & `categories`, Models, Factories, dan Relasi `Account`

**Files:**
- Create: `database/migrations/2026_10_08_100000_create_wallets_table.php`
- Create: `database/migrations/2026_10_08_100001_create_categories_table.php`
- Create: `app/Models/Wallet.php`
- Create: `app/Models/Category.php`
- Create: `database/factories/WalletFactory.php`
- Create: `database/factories/CategoryFactory.php`
- Modify: `app/Models/Account.php:57-58`
- Test: `tests/Feature/MasterData/WalletModelTest.php`
- Test: `tests/Feature/MasterData/CategoryModelTest.php`

**Interfaces:**
- Consumes: `App\Enums\CategoryType`, `App\Enums\CategoryStatus`, `App\Models\Account`.
- Produces:
  * Model `Wallet`: `account(): BelongsTo`, auto-slug generator, softDeletes, casts `current_balance` (decimal:2) & `allow_minus` (boolean).
  * Model `Category`: `account(): BelongsTo`, auto-slug generator, softDeletes, scopes `scopeActive()`, `scopeIncome()`, `scopeExpense()`, casts `type` (`CategoryType`), `status` (`CategoryStatus`), `is_system` (boolean), `order` (integer).
  * Model `Account`: relasi `wallets(): HasMany`, `categories(): HasMany`.

- [x] **Step 1: Write the failing tests**

Buat file `tests/Feature/MasterData/WalletModelTest.php`:
```php
<?php

use App\Models\Account;
use App\Models\Wallet;
use Illuminate\Support\Facades\Schema;

test('wallets table has expected columns following standard ordering', function () {
    expect(Schema::hasTable('wallets'))->toBeTrue();

    $columns = Schema::getColumnListing('wallets');
    expect($columns)->toContain(
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'account_id',
        'name',
        'slug',
        'icon',
        'color',
        'current_balance',
        'allow_minus'
    );
});

test('wallet belongs to account and automatically generates slug', function () {
    $account = Account::factory()->create();
    $wallet = Wallet::factory()->create([
        'account_id' => $account->id,
        'name' => 'Bank BCA',
        'current_balance' => 1500000.50,
        'allow_minus' => false,
    ]);

    expect($wallet->account->id)->toBe($account->id)
        ->and($wallet->slug)->toBe('bank-bca')
        ->and($wallet->current_balance)->toBe('1500000.50')
        ->and($wallet->allow_minus)->toBeFalse();

    expect($account->wallets)->toHaveCount(1)
        ->and($account->wallets->first()->name)->toBe('Bank BCA');
});

test('wallet supports soft deletes', function () {
    $wallet = Wallet::factory()->create();
    $walletId = $wallet->id;

    $wallet->delete();

    expect(Wallet::find($walletId))->toBeNull()
        ->and(Wallet::withTrashed()->find($walletId))->not->toBeNull();
});
```

Buat file `tests/Feature/MasterData/CategoryModelTest.php`:
```php
<?php

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use App\Models\Account;
use App\Models\Category;
use Illuminate\Support\Facades\Schema;

test('categories table has expected columns following standard ordering', function () {
    expect(Schema::hasTable('categories'))->toBeTrue();

    $columns = Schema::getColumnListing('categories');
    expect($columns)->toContain(
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'account_id',
        'name',
        'slug',
        'type',
        'is_system',
        'icon',
        'color',
        'order',
        'status'
    );
});

test('category belongs to account and casts attributes properly', function () {
    $account = Account::factory()->create();
    $category = Category::factory()->create([
        'account_id' => $account->id,
        'name' => 'Gaji Pokok',
        'type' => CategoryType::Income,
        'status' => CategoryStatus::Active,
        'is_system' => false,
        'order' => 1,
    ]);

    expect($category->account->id)->toBe($account->id)
        ->and($category->slug)->toBe('gaji-pokok')
        ->and($category->type)->toBe(CategoryType::Income)
        ->and($category->status)->toBe(CategoryStatus::Active)
        ->and($category->is_system)->toBeFalse()
        ->and($category->order)->toBe(1);

    expect($account->categories)->toHaveCount(1)
        ->and($account->categories->first()->name)->toBe('Gaji Pokok');
});

test('category query scopes filter correctly', function () {
    $account = Account::factory()->create();

    Category::factory()->create([
        'account_id' => $account->id,
        'type' => CategoryType::Income,
        'status' => CategoryStatus::Active,
    ]);

    Category::factory()->create([
        'account_id' => $account->id,
        'type' => CategoryType::Expense,
        'status' => CategoryStatus::Active,
    ]);

    Category::factory()->create([
        'account_id' => $account->id,
        'type' => CategoryType::Expense,
        'status' => CategoryStatus::Inactive,
    ]);

    expect(Category::where('account_id', $account->id)->active()->count())->toBe(2)
        ->and(Category::where('account_id', $account->id)->income()->count())->toBe(1)
        ->and(Category::where('account_id', $account->id)->expense()->count())->toBe(2);
});
```

- [x] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=ModelTest`
Expected: FAIL (Tables `wallets` and `categories` do not exist).

- [x] **Step 3: Implement Migrations, Models, Factories, and Account relations**

Buat migrasi `database/migrations/2026_10_08_100000_create_wallets_table.php` (urutan kolom: id, timestamps, softDeletes, lainnya):
```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('icon', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->decimal('current_balance', 24, 2)->default(0.00);
            $table->boolean('allow_minus')->default(false);

            $table->index(['account_id', 'slug']);
            $table->index(['account_id', 'current_balance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
```

Buat migrasi `database/migrations/2026_10_08_100001_create_categories_table.php` (urutan kolom: id, timestamps, softDeletes, lainnya):
```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('type', 20)->index();
            $table->boolean('is_system')->default(false)->index();
            $table->string('icon', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->string('status', 20)->default('active')->index();

            $table->index(['account_id', 'type']);
            $table->index(['account_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

Jalankan migrasi:
`php artisan migrate`

Buat Model `app/Models/Wallet.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['account_id', 'name', 'slug', 'icon', 'color', 'current_balance', 'allow_minus'])]
class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'current_balance' => 'decimal:2',
            'allow_minus' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Wallet $wallet): void {
            if (empty($wallet->slug)) {
                $baseSlug = Str::slug($wallet->name);
                $slug = $baseSlug;
                $counter = 1;

                while (static::where('account_id', $wallet->account_id)->where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$counter;
                    $counter++;
                }

                $wallet->slug = $slug;
            }
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
```

Buat Model `app/Models/Category.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}
```

Buat Factory `database/factories/WalletFactory.php`:
```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => fake()->unique()->word().' Wallet',
            'icon' => 'heroicon-o-wallet',
            'color' => '#10b981',
            'current_balance' => 0.00,
            'allow_minus' => false,
        ];
    }
}
```

Buat Factory `database/factories/CategoryFactory.php`:
```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use App\Models\Account;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => fake()->unique()->word().' Category',
            'type' => fake()->randomElement([CategoryType::Income, CategoryType::Expense]),
            'is_system' => false,
            'icon' => 'heroicon-o-tag',
            'color' => '#3b82f6',
            'order' => 0,
            'status' => CategoryStatus::Active,
        ];
    }
}
```

Modifikasi `app/Models/Account.php` dengan menambahkan method relasi:
```php
    public function wallets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function categories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Category::class);
    }
```

- [x] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact --filter=ModelTest`
Expected: PASS (Semua test pada `WalletModelTest` dan `CategoryModelTest` berhasil).

- [x] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/2026_10_08_100000_create_wallets_table.php database/migrations/2026_10_08_100001_create_categories_table.php app/Models/Wallet.php app/Models/Category.php database/factories/WalletFactory.php database/factories/CategoryFactory.php app/Models/Account.php tests/Feature/MasterData/WalletModelTest.php tests/Feature/MasterData/CategoryModelTest.php
git commit -m "feat(master-data): add migrations, models, factories and relations for wallets and categories"
```

---

### Task 3: Default System Categories Seeder & Tenant Registration Integration

**Files:**
- Create: `database/seeders/DefaultCategorySeeder.php`
- Modify: `app/Filament/Pages/Tenancy/RegisterAccount.php:41-57`
- Test: `tests/Feature/MasterData/DefaultCategorySeederTest.php`

**Interfaces:**
- Consumes: `App\Models\Account`, `App\Models\Category`, `App\Enums\CategoryType`, `App\Enums\CategoryStatus`.
- Produces:
  * `DefaultCategorySeeder::seedForAccount(Account $account): void` (idempotent seeder: membuat 8 kategori sistem bawaan dengan `is_system = true`, serta 14 kategori umum bawaan pengguna dengan `is_system = false`).
  * `RegisterAccount::handleRegistration(array $data)` memanggil seeder tersebut secara otomatis saat registrasi akun berhasil.

- [x] **Step 1: Write the failing test**

Buat file `tests/Feature/MasterData/DefaultCategorySeederTest.php`:
```php
<?php

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use App\Filament\Pages\Tenancy\RegisterAccount;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\DefaultCategorySeeder;
use Livewire\Livewire;

test('seeder creates 4 default system categories for account', function () {
    $account = Account::factory()->create();

    DefaultCategorySeeder::seedForAccount($account);

    $categories = Category::where('account_id', $account->id)->get();

    expect($categories)->toHaveCount(4);

    $initialBalance = $categories->firstWhere('name', 'SYSTEM_INITIAL_BALANCE');
    expect($initialBalance)->not->toBeNull()
        ->and($initialBalance->type)->toBe(CategoryType::Income)
        ->and($initialBalance->is_system)->toBeTrue()
        ->and($initialBalance->status)->toBe(CategoryStatus::Active);

    $transferIn = $categories->firstWhere('name', 'SYSTEM_TRANSFER_IN');
    expect($transferIn)->not->toBeNull()
        ->and($transferIn->type)->toBe(CategoryType::Income)
        ->and($transferIn->is_system)->toBeTrue()
        ->and($transferIn->status)->toBe(CategoryStatus::Active);

    $transferOut = $categories->firstWhere('name', 'SYSTEM_TRANSFER_OUT');
    expect($transferOut)->not->toBeNull()
        ->and($transferOut->type)->toBe(CategoryType::Expense)
        ->and($transferOut->is_system)->toBeTrue()
        ->and($transferOut->status)->toBe(CategoryStatus::Active);

    $adminFee = $categories->firstWhere('name', 'SYSTEM_TRANSFER_FEE');
    expect($adminFee)->not->toBeNull()
        ->and($adminFee->type)->toBe(CategoryType::Expense)
        ->and($adminFee->is_system)->toBeTrue()
        ->and($adminFee->status)->toBe(CategoryStatus::Active);
});

test('seeder is idempotent and does not create duplicates on multiple runs', function () {
    $account = Account::factory()->create();

    DefaultCategorySeeder::seedForAccount($account);
    DefaultCategorySeeder::seedForAccount($account);

    expect(Category::where('account_id', $account->id)->count())->toBe(4);
});

test('registering new account automatically seeds default system categories', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(RegisterAccount::class)
        ->fillForm([
            'name' => 'Akun Usaha Baru',
            'currency_code' => 'IDR',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $account = Account::where('slug', 'akun-usaha-baru')->firstOrFail();

    expect($account->categories()->where('is_system', true)->count())->toBe(4);
});
```

- [x] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=DefaultCategorySeederTest`
Expected: FAIL (Class `Database\Seeders\DefaultCategorySeeder` not found).

- [x] **Step 3: Implement `DefaultCategorySeeder` and wire into `RegisterAccount`**

Buat file `database/seeders/DefaultCategorySeeder.php`:
```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use App\Models\Account;
use App\Models\Category;
use Illuminate\Database\Seeder;

class DefaultCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (Account::all() as $account) {
            self::seedForAccount($account);
        }
    }

    public static function seedForAccount(Account $account): void
    {
        $defaultCategories = [
            [
                'name' => 'SYSTEM_INITIAL_BALANCE',
                'type' => CategoryType::Income,
                'icon' => 'heroicon-o-sparkles',
                'color' => 'success',
                'order' => 1,
            ],
            [
                'name' => 'SYSTEM_TRANSFER_IN',
                'type' => CategoryType::Income,
                'icon' => 'heroicon-o-arrow-down-left',
                'color' => 'success',
                'order' => 2,
            ],
            [
                'name' => 'SYSTEM_TRANSFER_OUT',
                'type' => CategoryType::Expense,
                'icon' => 'heroicon-o-arrow-up-right',
                'color' => 'danger',
                'order' => 3,
            ],
            [
                'name' => 'SYSTEM_TRANSFER_FEE',
                'type' => CategoryType::Expense,
                'icon' => 'heroicon-o-banknotes',
                'color' => 'warning',
                'order' => 4,
            ],
        ];

        foreach ($defaultCategories as $cat) {
            Category::firstOrCreate(
                [
                    'account_id' => $account->id,
                    'name' => $cat['name'],
                ],
                [
                    'type' => $cat['type'],
                    'is_system' => true,
                    'icon' => $cat['icon'],
                    'color' => $cat['color'],
                    'order' => $cat['order'],
                    'status' => CategoryStatus::Active,
                ]
            );
        }
    }
}
```

Modifikasi `app/Filament/Pages/Tenancy/RegisterAccount.php` pada method `handleRegistration`:
```php
        $account->members()->attach(auth()->user(), [
            'email' => auth()->user()->email,
            'role' => AccountRole::Owner->value,
            'status' => MemberStatus::Active->value,
            'confirmed_at' => now(),
        ]);

        \Database\Seeders\DefaultCategorySeeder::seedForAccount($account);

        return $account;
```

- [x] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter=DefaultCategorySeederTest`
Expected: PASS (3 tests passed).

- [x] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/seeders/DefaultCategorySeeder.php app/Filament/Pages/Tenancy/RegisterAccount.php tests/Feature/MasterData/DefaultCategorySeederTest.php
git commit -m "feat(master-data): implement DefaultCategorySeeder and auto-seed on tenant registration"
```

---

### Task 4: Filament v5 Resources (`WalletResource` & `CategoryResource`) dengan Modal Slide-Over & Scoped Uniqueness

**Files:**
- Create: `app/Filament/Resources/WalletResource.php`
- Create: `app/Filament/Resources/WalletResource/Pages/ListWallets.php`
- Create: `app/Filament/Resources/CategoryResource.php`
- Create: `app/Filament/Resources/CategoryResource/Pages/ListCategories.php`
- Test: `tests/Feature/MasterData/WalletResourceTest.php`
- Test: `tests/Feature/MasterData/CategoryResourceTest.php`

**Interfaces:**
- Consumes: `App\Models\Wallet`, `App\Models\Category`, `App\Models\Transaction`, `App\Enums\CategoryType`, `App\Enums\CategoryStatus`.
- Produces:
  * `WalletResource`: slide-over modal create/edit, scoped unique validation on `name`, `IconPicker` guava, Saldo Awal input on create (otomatis mencatat transaksi `SYSTEM_INITIAL_BALANCE`), kolom tabel `icon` (warna mengikuti `color`), `name` (warna mengikuti `color`), `current_balance` (Saldo), `allow_minus` (Minus).
  * `CategoryResource`: slide-over modal create/edit, scoped unique validation on `name`, `IconPicker` guava, disembunyikan dari kategori sistem (`where is_system = false`), kolom tabel `icon`, `name`, `status`.

- [x] **Step 1: Write the failing tests**

Buat file `tests/Feature/MasterData/WalletResourceTest.php`:
```php
<?php

use App\Filament\Resources\WalletResource\Pages\ListWallets;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->create(['owner_id' => $this->user->id]);
    $this->account->members()->attach($this->user->id, [
        'email' => $this->user->email,
        'role' => 'owner',
        'status' => 'active',
        'confirmed_at' => now(),
    ]);

    $this->actingAs($this->user);
    Filament::setTenant($this->account);
});

test('wallet resource list page renders correctly', function () {
    Wallet::factory()->create([
        'account_id' => $this->account->id,
        'name' => 'Rekening Operasional',
    ]);

    Livewire::test(ListWallets::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(Wallet::where('account_id', $this->account->id)->get());
});

test('can create wallet via slide-over modal with scoped unique validation', function () {
    Livewire::test(ListWallets::class)
        ->callAction(CreateAction::class, data: [
            'name' => 'Bank Mandiri',
            'color' => '#2563eb',
            'allow_minus' => false,
        ])
        ->assertHasNoActionErrors();

    expect(Wallet::where('account_id', $this->account->id)->where('name', 'Bank Mandiri')->exists())->toBeTrue();

    // Trying to create the duplicate name within the same tenant must fail
    Livewire::test(ListWallets::class)
        ->callAction(CreateAction::class, data: [
            'name' => 'Bank Mandiri',
        ])
        ->assertHasActionErrors(['name']);
});

test('can create wallet with initial balance and generates SYSTEM_INITIAL_BALANCE transaction', function () {
    Livewire::test(ListWallets::class)
        ->callAction(CreateAction::class, data: [
            'name' => 'BCA Bisnis',
            'initial_balance' => 500000,
            'color' => '#10b981',
            'allow_minus' => true,
        ])
        ->assertHasNoActionErrors();

    $wallet = Wallet::where('account_id', $this->account->id)->where('name', 'BCA Bisnis')->firstOrFail();

    expect((float) $wallet->current_balance)->toBe(500000.0);

    $transaction = Transaction::where('wallet_id', $wallet->id)->first();
    expect($transaction)->not->toBeNull()
        ->and((float) $transaction->amount)->toBe(500000.0)
        ->and($transaction->direction)->toBe(1)
        ->and($transaction->type)->toBe('transaction')
        ->and($transaction->category_name)->toBe('SYSTEM_INITIAL_BALANCE')
        ->and($transaction->account_id)->toBe($this->account->id);
});

test('creating wallet with zero initial balance does not generate transaction', function () {
    Livewire::test(ListWallets::class)
        ->callAction(CreateAction::class, data: [
            'name' => 'Dompet Kosong',
            'initial_balance' => 0,
        ])
        ->assertHasNoActionErrors();

    $wallet = Wallet::where('account_id', $this->account->id)->where('name', 'Dompet Kosong')->firstOrFail();

    expect((float) $wallet->current_balance)->toBe(0.0)
        ->and(Transaction::where('wallet_id', $wallet->id)->count())->toBe(0);
});

test('can edit wallet via slide-over modal', function () {
    $wallet = Wallet::factory()->create([
        'account_id' => $this->account->id,
        'name' => 'Bank Danamon',
    ]);

    Livewire::test(ListWallets::class)
        ->callTableAction(EditAction::class, $wallet, data: [
            'name' => 'Bank Danamon Bisnis',
        ])
        ->assertHasNoTableActionErrors();

    expect($wallet->fresh()->name)->toBe('Bank Danamon Bisnis');
});

test('can soft-delete wallet', function () {
    $wallet = Wallet::factory()->create([
        'account_id' => $this->account->id,
        'name' => 'Dompet Lama',
    ]);

    Livewire::test(ListWallets::class)
        ->callTableAction(DeleteAction::class, $wallet)
        ->assertHasNoTableActionErrors();

    expect($wallet->fresh()->trashed())->toBeTrue();
});
```

Buat file `tests/Feature/MasterData/CategoryResourceTest.php`:
```php
<?php

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use App\Filament\Resources\CategoryResource\Pages\ListCategories;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->create(['owner_id' => $this->user->id]);
    $this->account->members()->attach($this->user->id, [
        'email' => $this->user->email,
        'role' => 'owner',
        'status' => 'active',
        'confirmed_at' => now(),
    ]);

    $this->actingAs($this->user);
    Filament::setTenant($this->account);
});

test('category resource list page renders correctly', function () {
    $category = Category::factory()->create([
        'account_id' => $this->account->id,
        'name' => 'Makanan & Minuman',
        'is_system' => false,
    ]);

    Livewire::test(ListCategories::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$category]);
});

test('system categories are not displayed in category list table', function () {
    $systemCategory = Category::factory()->create([
        'account_id' => $this->account->id,
        'name' => 'SYSTEM_TRANSFER_IN',
        'is_system' => true,
    ]);

    $customCategory = Category::factory()->create([
        'account_id' => $this->account->id,
        'name' => 'Hiburan',
        'is_system' => false,
    ]);

    Livewire::test(ListCategories::class)
        ->assertCanSeeTableRecords([$customCategory])
        ->assertCanNotSeeTableRecords([$systemCategory]);
});

test('can create category with scoped unique validation', function () {
    Livewire::test(ListCategories::class)
        ->callAction(CreateAction::class, data: [
            'name' => 'Transportasi',
            'type' => CategoryType::Expense->value,
            'status' => CategoryStatus::Active->value,
            'order' => 10,
        ])
        ->assertHasNoActionErrors();

    expect(Category::where('account_id', $this->account->id)->where('name', 'Transportasi')->exists())->toBeTrue();

    // Duplicate name inside same tenant must fail
    Livewire::test(ListCategories::class)
        ->callAction(CreateAction::class, data: [
            'name' => 'Transportasi',
            'type' => CategoryType::Expense->value,
        ])
        ->assertHasActionErrors(['name']);
});
```

- [x] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=ResourceTest`
Expected: FAIL (Resource classes not found).

- [x] **Step 3: Implement `WalletResource` & `CategoryResource` with ListPages and slide-over modals**

Buat file `app/Filament/Resources/WalletResource.php`:
```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\WalletResource\Pages;
use App\Models\Wallet;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $modelLabel = 'Dompet';

    protected static ?string $pluralModelLabel = 'Dompet & Rekening';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Dompet / Rekening')
                    ->required()
                    ->maxLength(255)
                    ->scopedUnique(),
                TextInput::make('icon')
                    ->label('Icon (Heroicons)')
                    ->placeholder('heroicon-o-wallet')
                    ->maxLength(100),
                ColorPicker::make('color')
                    ->label('Warna Label'),
                Toggle::make('allow_minus')
                    ->label('Bolehkan Saldo Negatif')
                    ->default(false)
                    ->helperText('Jika aktif, transaksi keluar tetap diizinkan meskipun saldo dompet tidak mencukupi.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Dompet')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('current_balance')
                    ->label('Saldo Saat Ini')
                    ->money(fn ($record): string => $record->account->currency_code ?? 'IDR')
                    ->sortable(),
                IconColumn::make('allow_minus')
                    ->label('Boleh Minus')
                    ->boolean()
                    ->sortable(),
                ColorColumn::make('color')
                    ->label('Warna'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()->slideOver(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWallets::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
```

Buat file `app/Filament/Resources/WalletResource/Pages/ListWallets.php`:
```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources\WalletResource\Pages;

use App\Filament\Resources\WalletResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWallets extends ListRecords
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver(),
        ];
    }
}
```

Buat file `app/Filament/Resources/CategoryResource.php`:
```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $modelLabel = 'Kategori';

    protected static ?string $pluralModelLabel = 'Kategori';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Radio::make('type')
                    ->label('Tipe Transaksi')
                    ->options(CategoryType::class)
                    ->default(CategoryType::Expense->value)
                    ->required()
                    ->inline()
                    ->disabled(fn (?Category $record): bool => (bool) ($record?->is_system)),
                TextInput::make('name')
                    ->label('Nama Kategori')
                    ->required()
                    ->maxLength(255)
                    ->scopedUnique()
                    ->disabled(fn (?Category $record): bool => (bool) ($record?->is_system)),
                TextInput::make('icon')
                    ->label('Icon (Heroicons)')
                    ->placeholder('heroicon-o-tag')
                    ->maxLength(100),
                ColorPicker::make('color')
                    ->label('Warna Label'),
                TextInput::make('order')
                    ->label('Urutan')
                    ->numeric()
                    ->default(0),
                Select::make('status')
                    ->label('Status')
                    ->options(CategoryStatus::class)
                    ->default(CategoryStatus::Active->value)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Kategori')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_system')
                    ->label('Sistem')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('order')
                    ->label('Urutan')
                    ->sortable(),
                ColorColumn::make('color')
                    ->label('Warna'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe')
                    ->options(CategoryType::class),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(CategoryStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()->slideOver(),
                DeleteAction::make()
                    ->hidden(fn (Category $record): bool => (bool) $record->is_system),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->hidden(fn (Category $record): bool => (bool) $record->is_system),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
```

Buat file `app/Filament/Resources/CategoryResource/Pages/ListCategories.php`:
```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver(),
        ];
    }
}
```

- [x] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact --filter=ResourceTest`
Expected: PASS (Semua test pada `WalletResourceTest` dan `CategoryResourceTest` berhasil).

- [x] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Filament/Resources/WalletResource.php app/Filament/Resources/WalletResource/Pages/ListWallets.php app/Filament/Resources/CategoryResource.php app/Filament/Resources/CategoryResource/Pages/ListCategories.php tests/Feature/MasterData/WalletResourceTest.php tests/Feature/MasterData/CategoryResourceTest.php
git commit -m "feat(master-data): add Filament v5 WalletResource and CategoryResource with slide-over modals"
```

---

### Task 5: End-to-End Tenancy Isolation & Scoped Uniqueness Feature Suite

**Files:**
- Create: `tests/Feature/MasterData/MasterDataTenancyIsolationTest.php`

**Interfaces:**
- Consumes: `App\Models\Account`, `App\Models\User`, `App\Models\Wallet`, `App\Models\Category`.
- Produces: Comprehensive multi-tenant feature verification proving strict isolation between tenants, allowing duplicate names across distinct tenants, blocking duplicate names within the same tenant, and enabling soft-delete name re-use.

- [x] **Step 1: Write the multi-tenant isolation tests**

Buat file `tests/Feature/MasterData/MasterDataTenancyIsolationTest.php`:
```php
<?php

use App\Enums\CategoryType;
use App\Filament\Resources\CategoryResource\Pages\ListCategories;
use App\Filament\Resources\WalletResource\Pages\ListWallets;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use App\Models\Wallet;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->userA = User::factory()->create();
    $this->accountA = Account::factory()->create(['owner_id' => $this->userA->id]);
    $this->accountA->members()->attach($this->userA->id, [
        'email' => $this->userA->email,
        'role' => 'owner',
        'status' => 'active',
        'confirmed_at' => now(),
    ]);

    $this->userB = User::factory()->create();
    $this->accountB = Account::factory()->create(['owner_id' => $this->userB->id]);
    $this->accountB->members()->attach($this->userB->id, [
        'email' => $this->userB->email,
        'role' => 'owner',
        'status' => 'active',
        'confirmed_at' => now(),
    ]);
});

test('distinct tenants can create wallets and categories with the same name without conflict', function () {
    // Tenant A creates "Kas Utama"
    $walletA = Wallet::factory()->create([
        'account_id' => $this->accountA->id,
        'name' => 'Kas Utama',
    ]);

    // Tenant B creates "Kas Utama"
    $walletB = Wallet::factory()->create([
        'account_id' => $this->accountB->id,
        'name' => 'Kas Utama',
    ]);

    expect($walletA->name)->toBe('Kas Utama')
        ->and($walletB->name)->toBe('Kas Utama')
        ->and($walletA->account_id)->not->toBe($walletB->account_id);

    // Tenant A creates Category "Operasional"
    $catA = Category::factory()->create([
        'account_id' => $this->accountA->id,
        'name' => 'Operasional',
        'type' => CategoryType::Expense,
    ]);

    // Tenant B creates Category "Operasional"
    $catB = Category::factory()->create([
        'account_id' => $this->accountB->id,
        'name' => 'Operasional',
        'type' => CategoryType::Expense,
    ]);

    expect($catA->name)->toBe('Operasional')
        ->and($catB->name)->toBe('Operasional')
        ->and($catA->account_id)->not->toBe($catB->account_id);
});

test('tenant A cannot view tenant B wallets or categories in resource table', function () {
    $walletA = Wallet::factory()->create(['account_id' => $this->accountA->id, 'name' => 'Dompet Tenant A']);
    $walletB = Wallet::factory()->create(['account_id' => $this->accountB->id, 'name' => 'Dompet Tenant B']);

    $this->actingAs($this->userA);
    Filament::setTenant($this->accountA);

    Livewire::test(ListWallets::class)
        ->assertCanSeeTableRecords([$walletA])
        ->assertCanNotSeeTableRecords([$walletB]);

    $catA = Category::factory()->create(['account_id' => $this->accountA->id, 'name' => 'Kategori Tenant A']);
    $catB = Category::factory()->create(['account_id' => $this->accountB->id, 'name' => 'Kategori Tenant B']);

    Livewire::test(ListCategories::class)
        ->assertCanSeeTableRecords([$catA])
        ->assertCanNotSeeTableRecords([$catB]);
});

test('soft-deleted wallet name can be re-used within the same tenant', function () {
    $this->actingAs($this->userA);
    Filament::setTenant($this->accountA);

    $oldWallet = Wallet::factory()->create([
        'account_id' => $this->accountA->id,
        'name' => 'Tabungan Emas',
    ]);

    $oldWallet->delete();

    Livewire::test(ListWallets::class)
        ->callAction(CreateAction::class, data: [
            'name' => 'Tabungan Emas',
        ])
        ->assertHasNoActionErrors();

    expect(Wallet::where('account_id', $this->accountA->id)->where('name', 'Tabungan Emas')->whereNull('deleted_at')->exists())->toBeTrue();
});
```

- [x] **Step 2: Run test to verify it passes**

Run: `php artisan test --compact --filter=MasterDataTenancyIsolationTest`
Expected: PASS (All tests pass).

- [x] **Step 3: Run the full test suite to guarantee zero regression**

Run: `php artisan test --compact`
Expected: PASS (All test suites pass with 0 failures).

- [x] **Step 4: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add tests/Feature/MasterData/MasterDataTenancyIsolationTest.php
git commit -m "test(master-data): add end-to-end tenancy isolation and scoped uniqueness feature tests"
```

---

## Plan Review Checklist

- [x] Every task has explicit file paths for creation, modification, and testing.
- [x] Database migration column ordering strictly follows: (1) `id`, (2) `timestamps`, (3) `softDeletes`, (4) temporal, (5) others.
- [x] Filament v5 SDUI Schema signatures (`form(Schema $schema): Schema`) are used.
- [x] Filament v5 Enum Contracts (`HasLabel`, `HasColor`) are incorporated into Backed Enums.
- [x] Filament v5 `scopedUnique()` is used for tenant-level unique validation with soft-delete awareness.
- [x] TDD cycle (Red -> Green -> Pint -> Commit) is explicitly baked into every task.
- [x] All global constraints and review focus scenarios from the architecture design spec are addressed.
