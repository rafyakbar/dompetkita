# Milestone 1: Multi-Tenancy Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun fondasi arsitektur multi-tenancy Filament v5 untuk DompetKita sehingga pengguna dapat mendaftar, membuat akun pembukuan (*Account*), beralih akun melalui tenant switcher, dan seluruh akses tenant terlindungi dari kebocoran (*leakage*) sesuai standar resmi Filament v5 dan Livewire v4.

**Architecture:** Model `Account` dikonfigurasi sebagai tenant Filament v5 berbasis route slug (`/app/{slug}`). Relasi pengguna dengan tenant dikelola secara terpusat melalui tabel pivot `account_member`. Saat pengguna membuat akun, ia otomatis didaftarkan sebagai `owner` aktif di pivot, dan model `User` menerapkan interface `HasTenants` serta `HasDefaultTenant`. Seluruh Enum keanggotaan mengimplementasikan kontrak Filament `HasLabel` dan `HasColor`.

**Tech Stack:** Laravel 13, Filament v5, Livewire v4, PHP 8.4 Backed Enums, Pest PHP testing framework, Laravel Pint.

**Spec:** [docs/superpowers/specs/2026-10-07-dompetkita-core-architecture-design.md](file:///C:/laragon/www/dompetkita/docs/superpowers/specs/2026-10-07-dompetkita-core-architecture-design.md)
**Official References:**
- [Laravel 13 Documentation Reference](file:///D:/Code/docs-and-skills/docs/laravel_v13/references.md)
- [Livewire v4 Documentation Reference](file:///D:/Code/docs-and-skills/docs/livewire_v4/references.md)
- [Filament v5 Multi-Tenancy Reference](file:///D:/Code/docs-and-skills/docs/filament_v5/references/095_multi-tenancy.md)
- [Filament v5 Enum Tricks Reference](file:///D:/Code/docs-and-skills/docs/filament_v5/references/102_enum-tricks.md)

## Global Constraints

- PHP 8.4 syntax, strict types, explicit return types.
- Bebas DB ENUM: seluruh status dan tipe disimpan sebagai `string(20)` dan dicast ke PHP Backed Enums.
- Filament v5 Enum Tricks: seluruh enum mengimplementasikan `Filament\Support\Contracts\HasLabel` dan `Filament\Support\Contracts\HasColor`.
- Multi-tenancy Filament v5 berbasis `Account` dengan `slugAttribute: 'slug'`.
- Format kode sebelum commit menggunakan `vendor/bin/pint --dirty --format agent`.
- Seluruh pengujian menggunakan Pest PHP (`php artisan test --compact --filter=...`).

## Review Focus

1. **Slug Duplication Guard**: Dua akun dengan nama yang sama harus menghasilkan slug unik tanpa error collision.
2. **Inactive Member Gate**: Pengguna dengan status keanggotaan `'invited'`, `'revoked'`, atau `'left'` tidak boleh bisa mengakses tenant (`/app/{slug}`).
3. **Empty Tenant Redirect**: Pengguna login yang belum memiliki akun aktif harus otomatis dialihkan ke halaman registrasi tenant (`/app/new`).
4. **Cross-Tenant Access Rejection**: Pengguna tidak dapat membuka dashboard tenant orang lain di mana ia bukan anggota aktif (ekspektasi: 403 Forbidden / 404).
5. **Cascade Deletion Integrity**: Penghapusan akun harus menghapus relasi di `account_member` secara bersih tanpa *orphaned records*.

---

### Task 1: Membership Backed Enums (`AccountRole` & `MemberStatus`) dengan Filament Contracts

**Files:**
- Create: `app/Enums/AccountRole.php`
- Create: `app/Enums/MemberStatus.php`
- Test: `tests/Unit/Enums/MembershipEnumsTest.php`

**Interfaces:**
- Produces:
  - `App\Enums\AccountRole`: `Owner = 'owner'`, `Member = 'member'`, `Viewer = 'viewer'` (implements `HasLabel`, `HasColor`)
  - `App\Enums\MemberStatus`: `Invited = 'invited'`, `Active = 'active'`, `Left = 'left'`, `Revoked = 'revoked'` (implements `HasLabel`, `HasColor`)

- [ ] **Step 1: Write the failing unit test for membership enums**

```php
<?php

use App\Enums\AccountRole;
use App\Enums\MemberStatus;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

test('account roles have expected values, labels, and colors', function () {
    expect(AccountRole::Owner->value)->toBe('owner')
        ->and(AccountRole::Member->value)->toBe('member')
        ->and(AccountRole::Viewer->value)->toBe('viewer')
        ->and(AccountRole::Owner)->toBeInstanceOf(HasLabel::class)
        ->and(AccountRole::Owner)->toBeInstanceOf(HasColor::class)
        ->and(AccountRole::Owner->getLabel())->toBe('Pemilik')
        ->and(AccountRole::Member->getLabel())->toBe('Anggota')
        ->and(AccountRole::Viewer->getLabel())->toBe('Pengamat');
});

test('member statuses have expected values, labels, and colors', function () {
    expect(MemberStatus::Invited->value)->toBe('invited')
        ->and(MemberStatus::Active->value)->toBe('active')
        ->and(MemberStatus::Left->value)->toBe('left')
        ->and(MemberStatus::Revoked->value)->toBe('revoked')
        ->and(MemberStatus::Active)->toBeInstanceOf(HasLabel::class)
        ->and(MemberStatus::Active)->toBeInstanceOf(HasColor::class)
        ->and(MemberStatus::Active->getLabel())->toBe('Aktif')
        ->and(MemberStatus::Invited->getLabel())->toBe('Diundang');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=MembershipEnumsTest`
Expected: FAIL (Class "App\Enums\AccountRole" not found).

- [ ] **Step 3: Implement `AccountRole` and `MemberStatus` enums**

Create `app/Enums/AccountRole.php`:
```php
<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AccountRole: string implements HasColor, HasLabel
{
    case Owner = 'owner';
    case Member = 'member';
    case Viewer = 'viewer';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Owner => 'Pemilik',
            self::Member => 'Anggota',
            self::Viewer => 'Pengamat',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Owner => 'primary',
            self::Member => 'info',
            self::Viewer => 'gray',
        };
    }
}
```

Create `app/Enums/MemberStatus.php`:
```php
<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MemberStatus: string implements HasColor, HasLabel
{
    case Invited = 'invited';
    case Active = 'active';
    case Left = 'left';
    case Revoked = 'revoked';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Invited => 'Diundang',
            self::Active => 'Aktif',
            self::Left => 'Keluar',
            self::Revoked => 'Dicabut',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Invited => 'warning',
            self::Active => 'success',
            self::Left => 'gray',
            self::Revoked => 'danger',
        };
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter=MembershipEnumsTest`
Expected: PASS (2 tests passed).

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Enums/ tests/Unit/Enums/
git commit -m "feat(auth): create AccountRole and MemberStatus enums with Filament contracts"
```

---

### Task 2: Database Migrations for Tenancy (`accounts` & `account_member`)

**Files:**
- Create: `database/migrations/2026_10_07_060000_create_accounts_table.php`
- Create: `database/migrations/2026_10_07_060001_create_account_member_table.php`
- Create: `database/factories/AccountFactory.php`
- Test: `tests/Feature/Tenancy/AccountMigrationTest.php`

**Interfaces:**
- Consumes: `App\Enums\AccountRole`, `App\Enums\MemberStatus`
- Produces:
  - Database table `accounts` (`id`, `owner_id`, `name`, `slug`, `currency_code`, `description`, timestamps, softDeletes)
  - Database table `account_member` (`id`, `account_id`, `user_id`, `email`, `invitation_token`, `role`, `status`, `invited_at`, `confirmed_at`, `left_at`, `revoked_at`, timestamps)
  - `Database\Factories\AccountFactory`

- [ ] **Step 1: Write the failing test for tenancy tables schema**

```php
<?php

use Illuminate\Support\Facades\Schema;

test('accounts table has expected columns and indexes', function () {
    expect(Schema::hasTable('accounts'))->toBeTrue()
        ->and(Schema::hasColumns('accounts', [
            'id', 'owner_id', 'name', 'slug', 'currency_code', 'description', 'created_at', 'updated_at', 'deleted_at',
        ]))->toBeTrue();
});

test('account_member table has expected columns', function () {
    expect(Schema::hasTable('account_member'))->toBeTrue()
        ->and(Schema::hasColumns('account_member', [
            'id', 'account_id', 'user_id', 'email', 'invitation_token', 'role', 'status', 'invited_at', 'confirmed_at', 'left_at', 'revoked_at', 'created_at', 'updated_at',
        ]))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=AccountMigrationTest`
Expected: FAIL (accounts table does not exist).

- [ ] **Step 3: Implement migrations and AccountFactory**

Create `database/migrations/2026_10_07_060000_create_accounts_table.php`:
```php
<?php

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
            $table->string('slug')->unique();
            $table->string('currency_code', 3)->default('IDR');
            $table->text('description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
```

Create `database/migrations/2026_10_07_060001_create_account_member_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_member', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('email')->index();
            $table->string('invitation_token', 64)->nullable()->unique();
            $table->string('role', 20)->default('member')->index();
            $table->string('status', 20)->default('invited')->index();

            $table->unique(['account_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_member');
    }
};
```

Create `database/factories/AccountFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'owner_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->lexify('????'),
            'currency_code' => 'IDR',
            'description' => fake()->sentence(),
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter=AccountMigrationTest`
Expected: PASS (2 tests passed).

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/ database/factories/ tests/Feature/Tenancy/AccountMigrationTest.php
git commit -m "feat(tenancy): add accounts and account_member migrations and AccountFactory"
```

---

### Task 3: Eloquent Models & Relasi Tenant (`Account` & `User`)

**Files:**
- Create: `app/Models/Account.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/Tenancy/AccountModelTest.php`

**Interfaces:**
- Consumes: `App\Models\User`, `App\Enums\AccountRole`, `App\Enums\MemberStatus`
- Produces:
  - `App\Models\Account`: implements `Filament\Models\Contracts\HasCurrentTenantLabel`, relasi `owner(): BelongsTo`, `members(): BelongsToMany`, boot hook untuk slug otomatis.
  - `App\Models\User`: implements `Filament\Models\Contracts\HasTenants`, `HasDefaultTenant`, relasi `accounts(): BelongsToMany`, methods `getTenants(Panel $panel): Collection`, `canAccessTenant(Model $tenant): bool`, `getDefaultTenant(Panel $panel): ?Model`.

- [ ] **Step 1: Write failing test for Account and User tenancy relationships**

```php
<?php

use App\Enums\AccountRole;
use App\Enums\MemberStatus;
use App\Models\Account;
use App\Models\User;

test('account generates unique slug automatically when creating if not set', function () {
    $user = User::factory()->create();
    $account = Account::create([
        'owner_id' => $user->id,
        'name' => 'Keuangan Keluarga',
    ]);

    expect($account->slug)->toBe('keuangan-keluarga');

    $duplicate = Account::create([
        'owner_id' => $user->id,
        'name' => 'Keuangan Keluarga',
    ]);

    expect($duplicate->slug)->not->toBe('keuangan-keluarga')
        ->and($duplicate->slug)->toStartWith('keuangan-keluarga-');
});

test('user only retrieves active accounts via getTenants contract', function () {
    $user = User::factory()->create();
    $activeAccount = Account::factory()->create();
    $invitedAccount = Account::factory()->create();

    $activeAccount->members()->attach($user->id, [
        'email' => $user->email,
        'role' => AccountRole::Member->value,
        'status' => MemberStatus::Active->value,
        'confirmed_at' => now(),
    ]);

    $invitedAccount->members()->attach($user->id, [
        'email' => $user->email,
        'role' => AccountRole::Member->value,
        'status' => MemberStatus::Invited->value,
        'invited_at' => now(),
    ]);

    $panel = filament()->getCurrentOrDefaultPanel();
    $tenants = $user->getTenants($panel);

    expect($tenants->pluck('id'))->toContain($activeAccount->id)
        ->and($tenants->pluck('id'))->not->toContain($invitedAccount->id)
        ->and($user->canAccessTenant($activeAccount))->toBeTrue()
        ->and($user->canAccessTenant($invitedAccount))->toBeFalse()
        ->and($user->getDefaultTenant($panel)?->id)->toBe($activeAccount->id);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=AccountModelTest`
Expected: FAIL (Class "App\Models\Account" not found).

- [ ] **Step 3: Implement Account model and update User model**

Create `app/Models/Account.php`:
```php
<?php

namespace App\Models;

use Database\Factories\AccountFactory;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['owner_id', 'name', 'slug', 'currency_code', 'description'])]
class Account extends Model implements HasCurrentTenantLabel
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Account $account) {
            if (empty($account->slug)) {
                $baseSlug = Str::slug($account->name);
                $slug = $baseSlug;
                $counter = 1;

                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }

                $account->slug = $slug;
            }
        });
    }

    public function getCurrentTenantLabel(): string
    {
        return 'Akun Aktif';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'account_member')
            ->withPivot(['role', 'status', 'email', 'invitation_token', 'invited_at', 'confirmed_at', 'left_at', 'revoked_at'])
            ->withTimestamps();
    }
}
```

Modify `app/Models/User.php`:
Implement `Filament\Models\Contracts\HasTenants` and `HasDefaultTenant`:
```php
use App\Enums\MemberStatus;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class User extends Authenticatable implements FilamentUser, HasAvatar, HasDefaultTenant, HasTenants
{
    // ... casts, canAccessPanel, getFilamentAvatarUrl ...

    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'account_member')
            ->wherePivot('status', MemberStatus::Active->value)
            ->withPivot(['role', 'status'])
            ->withTimestamps();
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->accounts;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->accounts()->whereKey($tenant)->exists();
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->accounts()->first();
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter=AccountModelTest`
Expected: PASS (2 tests passed).

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/ tests/Feature/Tenancy/AccountModelTest.php
git commit -m "feat(tenancy): implement Account model and User HasTenants and HasDefaultTenant contracts"
```

---

### Task 4: Filament Tenancy Integration & Tenant Registration Page

**Files:**
- Create: `app/Filament/Pages/Tenancy/RegisterAccount.php`
- Modify: `app/Providers/Filament/AppPanelProvider.php`
- Test: `tests/Feature/Tenancy/FilamentTenancyTest.php`

**Interfaces:**
- Consumes: `App\Models\Account`, `App\Models\User`, `App\Enums\AccountRole`, `App\Enums\MemberStatus`
- Produces:
  - `App\Filament\Pages\Tenancy\RegisterAccount`: Filament Tenant Registration Page extending `Filament\Pages\Tenancy\RegisterTenant` with Filament 5 `Schema` signature.
  - `AppPanelProvider`: konfigurasi `$panel->tenant(Account::class, slugAttribute: 'slug')->tenantRegistration(RegisterAccount::class)`.

- [ ] **Step 1: Write failing test for Tenant Registration and Redirect**

```php
<?php

use App\Models\Account;
use App\Models\User;

test('authenticated user without account is redirected to tenant registration page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/app')
        ->assertRedirect('/app/new');
});

test('tenant registration page is accessible', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/app/new')
        ->assertSuccessful();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=FilamentTenancyTest`
Expected: FAIL (Redirected to unexpected location or 404).

- [ ] **Step 3: Implement RegisterAccount and update AppPanelProvider**

Create `app/Filament/Pages/Tenancy/RegisterAccount.php`:
```php
<?php

namespace App\Filament\Pages\Tenancy;

use App\Enums\AccountRole;
use App\Enums\MemberStatus;
use App\Models\Account;
use App\Models\Country;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;

class RegisterAccount extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Daftarkan Akun Pembukuan';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Akun')
                    ->placeholder('Contoh: Keuangan Pribadi')
                    ->required()
                    ->maxLength(255),
                Select::make('currency_code')
                    ->label('Mata Uang')
                    ->options(fn (): array => Country::getCurrencyOptions())
                    ->default('IDR')
                    ->searchable()
                    ->required(),
            ]);
    }

    protected function handleRegistration(array $data): Account
    {
        $account = Account::create([
            'owner_id' => auth()->id(),
            'name' => $data['name'],
            'currency_code' => $data['currency_code'] ?? 'IDR',
        ]);

        $account->members()->attach(auth()->user(), [
            'email' => auth()->user()->email,
            'role' => AccountRole::Owner->value,
            'status' => MemberStatus::Active->value,
            'confirmed_at' => now(),
        ]);

        return $account;
    }
}
```

Modify `app/Providers/Filament/AppPanelProvider.php`:
Add tenant configuration:
```php
use App\Filament\Pages\Tenancy\RegisterAccount;
use App\Models\Account;

// Di dalam method panel():
return $panel
    ->default()
    ->id('app')
    ->path('app')
    ->login()
    ->registration()
    ->passwordReset()
    ->tenant(Account::class, slugAttribute: 'slug')
    ->tenantRegistration(RegisterAccount::class)
    // ... middleware dan plugins ...
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter=FilamentTenancyTest`
Expected: PASS (2 tests passed).

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Filament/Pages/Tenancy/ app/Providers/Filament/ tests/Feature/Tenancy/FilamentTenancyTest.php
git commit -m "feat(tenancy): register Account tenant and RegisterAccount page in Filament"
```

---

### Task 5: End-to-End Tenancy Feature Tests & Route Guard Verification

**Files:**
- Modify: `tests/Feature/Tenancy/FilamentTenancyTest.php`

**Interfaces:**
- Consumes: Complete Milestone 1 components.
- Produces: Comprehensive Pest feature suite covering registration flow, tenant URL access, cross-tenant barrier, and switcher behavior.

- [ ] **Step 1: Add comprehensive end-to-end tenancy tests**

Append to `tests/Feature/Tenancy/FilamentTenancyTest.php`:
```php
use App\Enums\AccountRole;
use App\Enums\MemberStatus;
use Filament\Pages\Dashboard;
use Livewire\Livewire;
use App\Filament\Pages\Tenancy\RegisterAccount;

test('user can register a new account and is redirected to its dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(RegisterAccount::class)
        ->fillForm([
            'name' => 'Tabungan Masa Depan',
        ])
        ->call('register')
        ->assertHasNoFormErrors()
        ->assertRedirect('/app/tabungan-masa-depan');

    $account = Account::where('slug', 'tabungan-masa-depan')->first();

    expect($account)->not->toBeNull()
        ->and($account->owner_id)->toBe($user->id);

    expect($user->accounts()->whereKey($account)->exists())->toBeTrue()
        ->and($account->members()->where('user_id', $user->id)->first()->pivot->role)->toBe(AccountRole::Owner->value)
        ->and($account->members()->where('user_id', $user->id)->first()->pivot->status)->toBe(MemberStatus::Active->value);
});

test('user can access own tenant dashboard', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['owner_id' => $user->id]);

    $account->members()->attach($user->id, [
        'email' => $user->email,
        'role' => AccountRole::Owner->value,
        'status' => MemberStatus::Active->value,
        'confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get("/app/{$account->slug}")
        ->assertSuccessful();
});

test('user cannot access other tenant dashboard where they are not a member', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $otherAccount = Account::factory()->create(['owner_id' => $otherUser->id]);
    $otherAccount->members()->attach($otherUser->id, [
        'email' => $otherUser->email,
        'role' => AccountRole::Owner->value,
        'status' => MemberStatus::Active->value,
        'confirmed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get("/app/{$otherAccount->slug}")
        ->assertForbidden();
});
```

- [ ] **Step 2: Run tests to verify all feature tests pass**

Run: `php artisan test --compact --filter=FilamentTenancyTest`
Expected: PASS (All 5 tests passed).

- [ ] **Step 3: Run the full test suite to guarantee zero regression**

Run: `php artisan test --compact`
Expected: PASS (All tests passed, no regressions).

- [ ] **Step 4: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add tests/Feature/Tenancy/FilamentTenancyTest.php
git commit -m "test(tenancy): add comprehensive end-to-end tests for account registration and access guard"
```

---

## Plan Review Checklist

- [x] Every task has explicit file paths for creation, modification, and testing.
- [x] Filament v5 SDUI Schema signatures (`form(Schema $schema): Schema`) are used.
- [x] Filament v5 Enum Contracts (`HasLabel`, `HasColor`) are incorporated into Backed Enums.
- [x] Filament v5 Tenancy Contracts (`HasTenants`, `HasDefaultTenant`, `HasCurrentTenantLabel`) are used.
- [x] TDD cycle (Red -> Green -> Refactor -> Commit) is explicitly baked into every task.
- [x] All global constraints from the architecture design spec are respected.
- [x] Pint formatting and Git commit commands are specified for each task.
