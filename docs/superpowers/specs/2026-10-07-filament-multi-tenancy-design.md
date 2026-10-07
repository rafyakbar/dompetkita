# Filament 5.x Multi-Tenancy Design Specification

- **Date**: 2026-10-07
- **Topic**: Filament 5.x Multi-Tenancy Implementation
- **Status**: Approved

## 1. Overview
Implementasi multi-tenancy standar Filament 5.x pada panel `app` menggunakan model `Team` sebagai tenant dan relasi Many-to-Many dengan `User`. Setiap tenant dapat diakses melalui URL berbasis slug (`/app/{slug}`).

## 2. Architecture & Data Model

### 2.1 Database Migrations
1. **`teams` table**:
   - `id`: unsigned big integer (auto-increment primary key)
   - `name`: string, not null
   - `slug`: string, unique, not null, indexed
   - `created_at`, `updated_at`: timestamps

2. **`team_user` table**:
   - `id`: unsigned big integer (auto-increment primary key)
   - `team_id`: foreignId constrained to `teams.id` with cascade on delete
   - `user_id`: foreignId constrained to `users.id` with cascade on delete
   - `created_at`, `updated_at`: timestamps
   - Unique index: `[team_id, user_id]`

### 2.2 Eloquent Models
1. **`App\Models\Team`**:
   - Attributes fillable: `name`, `slug`
   - Relasi:
     - `members(): BelongsToMany` -> `User::class`
   - Booting logic:
     - Event `creating`: memastikan `slug` terisi (misalnya menggunakan `Str::slug($team->name)`) dengan penanganan slug unik jika duplikat.
   - Factory: `Database\Factories\TeamFactory`

2. **`App\Models\User`**:
   - Implementasi interface: `Filament\Models\Contracts\HasTenants`
   - Relasi:
     - `teams(): BelongsToMany` -> `Team::class`
   - Methods:
     - `getTenants(Panel $panel): Collection`: mengembalikan `$this->teams`
     - `canAccessTenant(Model $tenant): bool`: mengembalikan `$this->teams()->whereKey($tenant)->exists()`

## 3. Filament Tenancy Components (Filament 5.x)

### 3.1 Tenant Registration Page
- **Class**: `App\Filament\Pages\Tenancy\RegisterTeam`
- **Extends**: `Filament\Pages\Tenancy\RegisterTenant`
- **Form Schema**:
  - Menggunakan signature Filament 5.x: `public function form(Schema $schema): Schema`
  - Field: `TextInput::make('name')->required()->maxLength(255)`
- **Registration Handling**:
  - `protected function handleRegistration(array $data): Team`:
    - Membuat team baru via `Team::create($data)`
    - Menghubungkan user yang sedang login via `$team->members()->attach(auth()->user())`
    - Mengembalikan instance `$team`

### 3.2 Tenant Profile Page
- **Class**: `App\Filament\Pages\Tenancy\EditTeamProfile`
- **Extends**: `Filament\Pages\Tenancy\EditTenantProfile`
- **Form Schema**:
  - Menggunakan signature Filament 5.x: `public function form(Schema $schema): Schema`
  - Field: `TextInput::make('name')->required()->maxLength(255)`

### 3.3 Panel Configuration (`AppPanelProvider`)
- Mendaftarkan tenancy ke panel `app`:
  ```php
  $panel
      ->tenant(Team::class, slugAttribute: 'slug')
      ->tenantRegistration(RegisterTeam::class)
      ->tenantProfile(EditTeamProfile::class);
  ```

## 4. Security & Access Control
- Setiap request yang masuk ke panel `app` pada route `/app/{tenant}` akan diverifikasi oleh middleware Filament apakah user memiliki akses melalui `canAccessTenant()`.
- Jika user belum memiliki tenant saat mengakses `/app`, Filament secara otomatis mengarahkan user ke halaman registrasi tenant `/app/new`.
- Jika user mencoba mengakses slug tenant milik orang lain yang bukan anggotanya, HTTP response adalah 404 / 403 Forbidden sesuai proteksi Filament.

## 5. Verification & Testing Strategy
- Feature test menggunakan Pest (`tests/Feature/TenancyTest.php`):
  1. User baru yang login tanpa tenant dialihkan ke halaman registrasi tenant.
  2. User dapat mendaftarkan tim baru dan otomatis menjadi member tim tersebut.
  3. User yang memiliki tim dapat mengakses dashboard timnya (`/app/{slug}`).
  4. User tidak dapat mengakses tim orang lain di mana ia bukan merupakan member.
