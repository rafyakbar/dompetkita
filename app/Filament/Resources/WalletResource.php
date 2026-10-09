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
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Guava\IconPicker\Forms\Components\IconPicker;
use Guava\IconPicker\Tables\Columns\IconColumn as GuavaIconColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?string $modelLabel = 'Dompet';

    protected static ?string $pluralModelLabel = 'Dompet';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                IconPicker::make('icon')
                    ->label('Icon')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'lg' => 3,
                        '2xl' => 5,
                    ]),
                TextInput::make('name')
                    ->label('Nama Dompet')
                    ->required()
                    ->maxLength(255)
                    ->scopedUnique(),
                TextInput::make('initial_balance')
                    ->label('Saldo Awal')
                    ->numeric()
                    ->default(0)
                    ->visibleOn('create')
                    ->dehydrated(true),
                ColorPicker::make('color')
                    ->label('Warna'),
                Toggle::make('allow_minus')
                    ->label('Bolehkan Saldo Negatif')
                    ->default(false)
                    ->helperText('Jika aktif, transaksi keluar tetap diizinkan meskipun saldo dompet tidak mencukupi.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $resolveColor = fn (?Wallet $record) => filled($record?->color)
            ? (str_starts_with($record->color, '#') ? Color::hex($record->color) : $record->color)
            : null;

        return $table
            ->columns([
                GuavaIconColumn::make('icon')
                    ->label('Icon')
                    ->color($resolveColor),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color($resolveColor),
                TextColumn::make('current_balance')
                    ->label('Saldo')
                    ->money(fn ($record): string => $record->account->currency_code ?? 'IDR')
                    ->sortable(),
                IconColumn::make('allow_minus')
                    ->label('Minus')
                    ->boolean()
                    ->sortable(),
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
