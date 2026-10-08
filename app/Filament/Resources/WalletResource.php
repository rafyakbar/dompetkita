<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\WalletResource\Pages;
use App\Models\Wallet;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

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
