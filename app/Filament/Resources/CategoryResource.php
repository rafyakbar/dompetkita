<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\CategoryStatus;
use App\Enums\CategoryType;
use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

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
