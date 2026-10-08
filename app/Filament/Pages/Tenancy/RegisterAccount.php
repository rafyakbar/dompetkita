<?php

declare(strict_types=1);

namespace App\Filament\Pages\Tenancy;

use App\Enums\AccountRole;
use App\Enums\MemberStatus;
use App\Models\Account;
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
                TextInput::make('currency_code')
                    ->label('Mata Uang')
                    ->default('IDR')
                    ->disabled()
                    ->dehydrated(),
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
