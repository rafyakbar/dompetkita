# DompetKita Core Architectural Design Specification

- **Date**: 2026-10-07
- **Topic**: DompetKita Core Architecture, Database Schema, & Financial Engine
- **Status**: Approved by User

---

## 1. Executive Summary & Goals

**DompetKita** adalah aplikasi manajemen keuangan multi-tenant yang dirancang untuk kebutuhan personal, rumah tangga, dan usaha kecil. Dibangun di atas **Laravel 13**, **Filament PHP v5**, dan **Livewire v4**, aplikasi ini memprioritaskan:
1. **Integritas Saldo & Keuangan Mutlak**: Zero-loss audit trail, pencegahan race condition via pessimistic locking (`lockForUpdate`), dan buku besar (*ledger*) yang bersih.
2. **Multi-Tenancy Tanpa Kebocoran**: Model tenant `Account` dengan manajemen anggota terintegrasi Filament v5 `HasTenants`.
3. **Performa Tinggi & Mobile-First**: Pemuatan halaman instan dengan indeks komposit terarah, query konsolidasi SQL murni, serta preset mobile ramah ibu jari (*thumb-reach*).
4. **Kompatibilitas DBMS Penuh**: Tanpa DB Enums di tingkat database; seluruh status dan tipe divalidasi menggunakan PHP 8.1+ Backed Enums dan Eloquent `$casts`.

---

## 2. Core Architecture & Design Decisions

Berdasarkan tinjauan kritis arsitektur, keputusan desain final yang disepakati adalah:

1. **Strategi Mata Uang (Single-Currency per Tenant)**:
   - Setiap `Account` memiliki satu mata uang acuan (`currency_code`, default: `IDR`), dipilih saat pembuatan akun dari 154 mata uang unik global berbasis tabel master `countries` (`Country::getCurrencyOptions()`).
   - Seluruh dompet di bawah akun tersebut menggunakan mata uang yang sama. Pengelolaan aset dalam mata uang asing (valas) dilakukan dengan membuat entitas Akun Pembukuan terpisah.
2. **Buku Besar Mutasi Kas Riil (`Pure Event Ledger`)**:
   - Transaksi murni mencatat pergerakan riil (`amount`, `direction`, `happened_at`). Saldo berjalan (*running balance*) dihitung secara dinamis via SQL Window Function saat laporan rekening koran dibuka.
   - Desain ini mendukung input transaksi mundur (*backdated*) serta edit/hapus transaksi secara aman tanpa merusak kebenaran data baris-baris historis lainnya.
3. **Integritas Konkurensi Saldo & Saldo Awal**:
   - Seluruh pembaruan saldo wajib dibungkus dalam `DB::transaction()` dengan pessimistic lock:
     `$wallet = Wallet::where('id', $walletId)->lockForUpdate()->first()`.
   - Pembuatan dompet dengan Saldo Awal > 0 otomatis mencatat transaksi pemasukan kas riil (`direction = 1`) berlabel kategori sistem `SYSTEM_INITIAL_BALANCE` sehingga integritas buku besar tetap 100% terjaga sejak hari pertama.
4. **Relasi Transfer Dua Arah & Kategori Sistem Default**:
   - Tabel `transactions` memiliki foreign key nullable `transfer_id` yang merujuk ke tabel `transfers`.
   - Transfer antar dompet menghasilkan 2 row di `transactions` (keluar dan masuk) bertipe `transfer`.
   - 4 Kategori default sistem otomatis dibuat saat akun didaftarkan:
     * `SYSTEM_INITIAL_BALANCE` (Pemasukan, icon `heroicon-o-sparkles`, color `success`, order 1)
     * `SYSTEM_TRANSFER_IN` (Pemasukan, icon `heroicon-o-arrow-down-left`, color `success`, order 2)
     * `SYSTEM_TRANSFER_OUT` (Pengeluaran, icon `heroicon-o-arrow-up-right`, color `danger`, order 3)
     * `SYSTEM_TRANSFER_FEE` (Pengeluaran, icon `heroicon-o-banknotes`, color `warning`, order 4)
   - Kategori sistem disembunyikan dari tabel manajemen kategori (`where is_system = false`).
   - Widget dan grafik statistik di dashboard menyediakan filter opsional untuk **sertakan atau kecualikan** transaksi transfer dari perhitungan pengeluaran/pemasukan operasional.
   - Biaya admin transfer (`fee_amount`) selalu memotong dompet asal (`from_wallet_id`) dan dicatat dengan kategori sistem `SYSTEM_TRANSFER_FEE`.
5. **Alur Hutang & Piutang**:
   - Pembuatan hutang (*payable*) atau piutang (*receivable*) selalu mewajibkan pemilihan dompet.
   - Saldo dompet penerima langsung bertambah (hutang) atau berkurang (piutang) dengan pembuatan baris transaksi awal (`initial_transaction_id`).
6. **Multi-Tenancy & Membership**:
   - Tabel pivot `account_member` menjadi satu-satunya sumber relasi keanggotaan.
   - Saat user membuat akun, user tersebut otomatis didaftarkan ke `account_member` dengan role `'owner'` dan status `'active'`.
   - Relasi `HasTenants` pada model `User` secara ketat hanya mengambil tenant dengan `status = 'active'`.
7. **Konvensi Urutan Kolom Migrasi Database (Column Ordering Standard)**:
   - Setiap definisi tabel pada migrasi (`Schema::create`) wajib mengikuti urutan baku:
     1. `id` (primary key)
     2. `timestamps` (`created_at`, `updated_at`)
     3. `softDeletes` (`deleted_at`, jika ada)
     4. Kolom Waktu / Temporal (misal: `happened_at`, `invited_at`, `due_date`, dll.)
     5. Kolom lainnya (Foreign keys, attribute strings, numeric, notes, booleans, dll.)
8. **Proteksi Akses Tenant & Pencegahan Stale Intended URL (`ValidateTenantAccess`)**:
   - Mencegah error 404 ketika database di-refresh (`migrate:fresh --seed`) saat browser masih berada di rute tenant lama.
   - Diposisikan sebelum `AuthenticatesRequests` via `$middleware->prependToPriorityList(...)` di `bootstrap/app.php`.
   - Mengosongkan session `url.intended` jika merujuk ke tenant yang tidak ada di database, mengalihkan unauthenticated visitor ke `/app/login` secara aman, dan mengalihkan authenticated user ke default tenant atau `/app/new`.
   - `DatabaseSeeder` otomatis menjalankan `AccountSeeder` untuk menyuntikkan tenant default `keuangan-pribadi` untuk `admin@email.com` beserta dompet default dan 4 kategori sistem.

---

## 3. Database Schema Specification (No DB Enums)

### 3.1 Core & Autentikasi

#### `users` (Bawaan Laravel + Breezy)
- `id` : unsignedBigInteger, primary key
- `created_at`, `updated_at` : timestamps
- `email_verified_at` : timestamp, nullable
- `name` : string(255)
- `email` : string(255), unique
- `password` : string(255)
- `two_factor_secret` : text, nullable
- `two_factor_recovery` : text, nullable
- `avatar_url` : string(500), nullable
- `remember_token` : string(100), nullable

#### `accounts` (Tenant / Akun Pembukuan)
- `id` : unsignedBigInteger, primary key
- `created_at`, `updated_at` : timestamps
- `deleted_at` : timestamp, nullable (soft deletes)
- `owner_id` : foreignId -> `users.id` (cascade on delete)
- `name` : string(255), index
- `slug` : string(255), index (route tenant key)
- `currency_code` : string(3), default('IDR')
- `description` : text, nullable
- Unique constraint: `unique(owner_id, slug)`

#### `account_member` (Pivot Keanggotaan & Undangan Tenant)
- `id` : unsignedBigInteger, primary key
- `created_at`, `updated_at` : timestamps
- `invited_at` : timestamp, nullable
- `confirmed_at` : timestamp, nullable
- `left_at` : timestamp, nullable
- `revoked_at` : timestamp, nullable
- `account_id` : foreignId -> `accounts.id` (cascade on delete)
- `user_id` : foreignId -> `users.id`, nullable (diisi saat user terdaftar/klaim)
- `email` : string(255), index
- `invitation_token` : string(64), nullable, unique
- `role` : string(20), default('member'), index (`owner`, `member`, `viewer`)
- `status` : string(20), default('invited'), index (`invited`, `active`, `left`, `revoked`)
- Unique constraint: `unique(account_id, email)`
 
#### `countries` (Master Data Negara & Mata Uang)
- `id` : unsignedBigInteger, primary key
- `created_at`, `updated_at` : timestamps
- `name` : string(255), index
- `iso2` : string(2), unique
- `iso3` : string(3), unique
- `numeric_code` : string(10), nullable
- `phonecode` : string(20), nullable
- `capital` : string(255), nullable
- `currency` : string(10), index (kode mata uang ISO 4217, misal: 'IDR', 'USD')
- `currency_name` : string(255), index (nama mata uang, misal: 'Indonesian rupiah')
- `currency_symbol` : string(20), nullable (simbol mata uang, misal: 'Rp', '$')
- `region` : string(255), nullable, index
- `subregion` : string(255), nullable, index
- `nationality` : string(255), nullable, index
- `latitude` : decimal(10, 8), nullable
- `longitude` : decimal(11, 8), nullable

---

### 3.2 Master Data Keuangan

#### `wallets` (Dompet / Akun Kas)
- `id` : unsignedBigInteger, primary key
- `created_at`, `updated_at` : timestamps
- `deleted_at` : timestamp, nullable
- `account_id` : foreignId -> `accounts.id` (cascade on delete)
- `name` : string(255)
- `slug` : string(255)
- `icon` : string(100), nullable (diinput via `guava/filament-icon-picker` + `mallardduck/blade-lucide-icons`, full width dengan responsive columns: default 1, lg 3, 2xl 5)
- `color` : string(50), nullable (diinput via ColorPicker)
- `current_balance` : decimal(24, 2), default(0.00)
- `allow_minus` : boolean, default(false)
- Saldo Awal: Diinput saat pembuatan dompet, jika > 0 otomatis mencatat transaksi `SYSTEM_INITIAL_BALANCE`.
- Tampilan Tabel: Kolom `icon` (warna mengikuti `color`), `name` (warna teks mengikuti `color`), `current_balance` (Saldo), `allow_minus` (Minus).
- Indexes: `index(account_id, slug)`, `index(account_id, current_balance)`
- Validasi Unik: Ditegakkan di level aplikasi via `Rule::unique('wallets', 'name')->where('account_id', $accountId)->whereNull('deleted_at')`

#### `categories` (Kategori Transaksi)
- `id` : unsignedBigInteger, primary key
- `created_at`, `updated_at` : timestamps
- `deleted_at` : timestamp, nullable
- `account_id` : foreignId -> `accounts.id` (cascade on delete)
- `name` : string(255)
- `slug` : string(255)
- `type` : string(20), index (`income`, `expense`)
- `is_system` : boolean, default(false), index
- `icon` : string(100), nullable (diinput via `guava/filament-icon-picker`)
- `color` : string(50), nullable
- `order` : unsignedInteger, default(0)
- `status` : string(20), default('active'), index (`active`, `inactive`)
- Kategori Sistem Default:
  * `SYSTEM_INITIAL_BALANCE` (Income, order 1, sparkles)
  * `SYSTEM_TRANSFER_IN` (Income, order 2, arrow-down-left)
  * `SYSTEM_TRANSFER_OUT` (Expense, order 3, arrow-up-right)
  * `SYSTEM_TRANSFER_FEE` (Expense, order 4, banknotes)
  * Catatan: Kategori sistem disembunyikan dari tabel manajemen kategori (`where is_system = false`).
- Tampilan Tabel: Kolom `icon` (warna mengikuti `color`), `name`, `status`.
- Indexes: `index(account_id, type)`, `index(account_id, slug)`
- Validasi Unik: Ditegakkan di level aplikasi via `Rule::unique('categories', 'name')->where('account_id', $accountId)->whereNull('deleted_at')`

---

### 3.3 Transaksi & Mutasi

#### `transfers` (Header Pemindahan Dana Antar Dompet)
- `id` : unsignedBigInteger, primary key
- `created_at`, `updated_at` : timestamps
- `happened_at` : timestamp, index
- `account_id` : foreignId -> `accounts.id` (cascade on delete)
- `from_wallet_id` : foreignId -> `wallets.id` (restrict on delete)
- `to_wallet_id` : foreignId -> `wallets.id` (restrict on delete)
- `from_transaction_id` : foreignId -> `transactions.id`, nullable
- `to_transaction_id` : foreignId -> `transactions.id`, nullable
- `fee_transaction_id` : foreignId -> `transactions.id`, nullable
- `amount` : decimal(24, 2) (selalu positif)
- `fee_amount` : decimal(24, 2), default(0.00)
- `note` : text, nullable
- Composite Index: `index(account_id, happened_at)`

#### `transactions` (Ledger Mutasi Kas)
- `id` : unsignedBigInteger, primary key
- `created_at`, `updated_at` : timestamps
- `deleted_at` : timestamp, nullable (soft deletes)
- `happened_at` : timestamp, index
- `account_id` : foreignId -> `accounts.id` (cascade on delete)
- `wallet_id` : foreignId -> `wallets.id` (cascade on delete)
- `category_id` : foreignId -> `categories.id`, nullable (null on delete)
- `transfer_id` : unsignedBigInteger, nullable, index (fk ke transfers di Milestone 3)
- `type` : string(20), default('transaction'), index (`transaction`, `transfer`)
- `direction` : smallInteger, index (`1` = masuk, `-1` = keluar)
- `amount` : decimal(24, 2), index (nominal mutasi positif)
- `category_name` : string(255), nullable (arsip denormalisasi)
- `wallet_name` : string(255) (arsip denormalisasi)
- `note` : text, nullable
- Composite Index: `index(account_id, happened_at)`, `index(wallet_id, happened_at)`
- Sort Order Baku: `happened_at DESC, id DESC`

---

### 3.4 Anggaran & Hutang-Piutang

#### `budgets` (Plafon Pengeluaran Kategori)
- `id` : unsignedBigInteger, primary key
- `created_at`, `updated_at` : timestamps
- `period_start` : timestamp, nullable
- `period_end` : timestamp, nullable
- `account_id` : foreignId -> `accounts.id` (cascade on delete)
- `category_id` : foreignId -> `categories.id` (cascade on delete)
- `max_amount` : decimal(24, 2)
- `alert_percentage` : unsignedSmallInteger, default(80)
- `period_type` : string(20) (`monthly`, `custom`)
- `year` : unsignedSmallInteger, nullable
- `month` : unsignedTinyInteger, nullable
- Unique Constraint Bulanan: `unique(category_id, year, month)`

#### `debts` (Pinjaman & Piutang)
- `id` : unsignedBigInteger, primary key
- `created_at`, `updated_at` : timestamps
- `deleted_at` : timestamp, nullable
- `due_date` : timestamp, nullable, index
- `account_id` : foreignId -> `accounts.id` (cascade on delete)
- `wallet_id` : foreignId -> `wallets.id` (restrict on delete)
- `initial_transaction_id` : foreignId -> `transactions.id`, nullable
- `counterparty_name` : string(255), index
- `type` : string(20), index (`payable`, `receivable`)
- `initial_amount` : decimal(24, 2)
- `paid_amount` : decimal(24, 2), default(0.00)
- `note` : text, nullable
- Composite Index: `index(account_id, type)`

#### `debt_payments` (Cicilan / Pelunasan Hutang-Piutang)
- `id` : unsignedBigInteger, primary key
- `created_at`, `updated_at` : timestamps
- `paid_at` : timestamp, index
- `debt_id` : foreignId -> `debts.id` (cascade on delete)
- `transaction_id` : foreignId -> `transactions.id` (restrict on delete)
- `amount` : decimal(24, 2)

---

### 3.5 Pendukung

#### `attachments` (Berkas Lampiran Polymorphic)
- `id` : unsignedBigInteger, primary key
- `created_at`, `updated_at` : timestamps
- `deleted_at` : timestamp, nullable
- `attachable_type` : string(100), index (menggunakan `Relation::enforceMorphMap`)
- `attachable_id` : unsignedBigInteger, index
- `file_path` : string(500)
- `mime_type` : string(100)
- `size` : unsignedBigInteger
- Composite Index: `index(attachable_type, attachable_id)`

---

## 4. Financial Engine & Business Logic

### 4.1 Pembaruan Saldo Atomik & Concurrency Guard
Setiap aksi yang memengaruhi saldo dompet wajib dieksekusi di dalam `DB::transaction()` dengan pessimistic lock:
```php
$wallet = Wallet::where('id', $walletId)->lockForUpdate()->first();
$newBalance = $wallet->current_balance + ($direction * $amount);

if ($newBalance < 0 && ! $wallet->allow_minus) {
    throw new InsufficientBalanceException("Saldo dompet {$wallet->name} tidak mencukupi.");
}

$wallet->update(['current_balance' => $newBalance]);
```

### 4.2 Alur Transaksi Transfer & Biaya Admin
1. Mengunci kedua dompet asal dan tujuan secara berurutan berdasarkan ID untuk mencegah deadlocks:
   ```php
   $wallets = Wallet::whereIn('id', [$fromId, $toId])->orderBy('id')->lockForUpdate()->get();
   ```
2. Mengurangi saldo `from_wallet` sebesar `amount + fee_amount`.
3. Menambah saldo `to_wallet` sebesar `amount`.
4. Membuat 1 row di `transfers`.
5. Membuat 2 row mutasi transfer di `transactions`:
   - Outgoing: `direction = -1`, `type = 'transfer'`, `transfer_id = $transfer->id`, `category_id = $sysCatTransferOut->id`.
   - Incoming: `direction = 1`, `type = 'transfer'`, `transfer_id = $transfer->id`, `category_id = $sysCatTransferIn->id`.
6. Jika `fee_amount > 0`:
   - Membuat 1 row mutasi fee di `transactions`:
     `direction = -1`, `type = 'expense'`, `wallet_id = $fromId`, `category_id = $sysCatTransferFee->id`.

### 4.3 Alur Reversal Transaksi (Edit & Delete)
- **Saat Transaksi Dihapus**:
  - Saldo dompet dibalikkan secara otomatis:
    `current_balance -= (direction * amount)`.
- **Saat Transaksi Diubah**:
  - Saldo lama dibalikkan dari dompet lama, lalu saldo baru diterapkan ke dompet baru:
    `old_wallet->current_balance -= (old_direction * old_amount)`
    `new_wallet->current_balance += (new_direction * new_amount)`.

### 4.4 Rekonsiliasi Saldo (Self-Healing Routine)
Sistem menyediakan perintah Artisan dan tombol aksi di panel:
`php artisan wallet:reconcile {account_id?}`
Perintah ini menghitung ulang saldo riil berdasarkan akumulasi seluruh transaksi kas:
`current_balance = SUM(direction * amount)` dari tabel `transactions` dan menyinkronkan kembali jika terjadi anomali.

---

## 5. Multi-Tenancy & Authorization

### 5.1 Filament Multi-Tenancy Integration
- **Model Tenant**: `App\Models\Account` (route key: `slug`).
- **Registrasi Akun Baru**: `RegisterAccount` (`Filament\Pages\Tenancy\RegisterTenant`).
  - Form memuat input nama akun dan select dropdown searchable pilihan mata uang yang bersumber dari master data negara (`Country::getCurrencyOptions()`, default: `IDR`, 154 opsi mata uang unik global).
  - Saat akun dibuat, buat record `account_member` untuk pembuat akun:
    `role = 'owner'`, `status = 'active'`, `confirmed_at = now()`.
  - Otomatis seed kategori sistem default (`Biaya Admin Transfer`, `Transfer Masuk`, `Transfer Keluar`).
- **Klaim Undangan Member (`/invitations/{token}`)**:
  - Jika belum memiliki akun: Form registrasi dengan email terkunci. Setelah submit, otomatis verifikasi membership dan login ke tenant.
  - Jika sudah login dan email cocok: Tombol satu klik "Terima Undangan".

### 5.2 Otorisasi Berbasis Role (Policies)
Setiap Resource (`Transaction`, `Wallet`, `Category`, `Budget`, `Debt`) diproteksi oleh Laravel Policy:
- **`owner`**: Hak penuh (CRUD, undang/hapus member, hapus akun).
- **`member`**: Hak transaksi & operasional penuh (CRUD transaksi, dompet, kategori, anggaran, hutang). Tidak bisa menghapus akun atau mengelola member.
- **`viewer`**: Read-only (hanya melihat dashboard, tabel, dan laporan tanpa tombol Create/Edit/Delete).

---

## 6. Performance & Mobile Guidelines

1. **Polymorphic Optimization**:
   - Menerapkan `Relation::enforceMorphMap([ 'transaction' => Transaction::class, 'debt' => Debt::class ])` di `AppServiceProvider`.
2. **Mobile Navigation & SPA Compatibility (`hammadzafar05/mobile-bottom-nav`)**:
   - **Keputusan Arsitektur**: Menggunakan plugin mandiri `hammadzafar05/mobile-bottom-nav` dan secara tegas **TIDAK MENGGUNAKAN** `hammadzafar05/filament-mobile-preset`.
   - **Rasional Teknis**:
     * Package `filament-mobile-preset` menginjeksi tag `<style data-navigate-track>` pada render hook `PanelsRenderHook::HEAD_END` dan `BODY_END`. Dalam arsitektur Livewire, `data-navigate-track` hanya valid untuk file eksternal dengan URL (`<link href>` / `<script src>`). Pada inline `<style>`, `el.href` bernilai `undefined`, memicu kegagalan internal asset tracker Livewire saat berpindah halaman.
     * Kegagalan tersebut membekukan lifecycle navigasi Livewire SPA (`wire:navigate`), sehingga event `livewire:navigated` tidak pernah ter-trigger, Alpine.js gagal me-rehydrate body baru (mengakibatkan container utama `.fi-main-ctn` terkunci pada status default `@apply opacity-0`), dark mode hilang, dan progress bar macet di ~30%.
     * Sebaliknya, plugin `hammadzafar05/mobile-bottom-nav` bekerja secara bersih dan terisolasi untuk merender bottom navigation bar responsif (< 1024px) yang mengekstrak item dari Filament Navigation Registry, 100% kompatibel dan mulus berdampingan dengan `Filament Breezy` dan Filament SPA Mode (`->spa()`).
3. **Dashboard Query Consolidation**:
   - Menggunakan query tunggal terkonsolidasi dengan agregasi SQL murni (`selectRaw`) yang memanfaatkan indeks komposit `(account_id, happened_at)`:
     ```sql
     SELECT 
       SUM(CASE WHEN direction = 1 AND (type != 'transfer' OR :include_transfers) THEN amount ELSE 0 END) AS total_income,
       SUM(CASE WHEN direction = -1 AND (type != 'transfer' OR :include_transfers) THEN amount ELSE 0 END) AS total_expense
     FROM transactions 
     WHERE account_id = :account_id AND happened_at BETWEEN :start AND :end
     ```

---

## 7. Testing & Verification Strategy

Pengujian komprehensif menggunakan Pest PHP (`tests/Feature/`):
1. **Tenancy Isolation Tests**:
   - Memastikan user tidak dapat mengakses resource atau melihat dropdown data milik tenant lain.
   - Memastikan role `viewer` ditolak saat mencoba membuat transaksi (Policy check).
2. **Saldo Concurrency & Locking Tests**:
   - Memverifikasi transaksi berhasil memodifikasi saldo secara presisi.
   - Memverifikasi pencegahan saldo minus ketika `allow_minus = false`.
3. **Transfer & Fee Lifecycle Tests**:
   - Memverifikasi 1 aksi transfer menghasilkan row transfer, 2 transaksi buku besar, dan 1 transaksi fee (jika ada fee).
   - Memverifikasi biaya admin memotong dompet asal.
4. **Hutang-Piutang Lifecycle Tests**:
   - Memverifikasi pencatatan hutang menambah saldo dompet kas dan membuat `initial_transaction_id`.
   - Memverifikasi pencatatan cicilan mengurangi sisa pokok hutang dan memotong kas.
5. **Reconciliation Tests**:
   - Memverifikasi artisan command `wallet:reconcile` berhasil memperbaiki saldo dompet yang diubah tidak sengaja.
