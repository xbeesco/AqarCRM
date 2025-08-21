<?php

namespace App\Filament\Resources\PropertyFeatures;

use App\Filament\Resources\PropertyFeatures\Pages\ManagePropertyFeatures;
use App\Models\PropertyFeature;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PropertyFeatureResource extends Resource
{
    protected static ?string $model = PropertyFeature::class;

    protected static ?string $recordTitleAttribute = 'name_ar';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->required()
                    ->maxLength(255),
                TextInput::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name_ar')
            ->columns([
                TextColumn::make('name_ar')
                    ->label('الاسم بالعربية'),
                TextColumn::make('name_en')
                    ->label('الاسم بالإنجليزية'),
                TextColumn::make('category')
                    ->label('الفئة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'basics' => 'أساسيات',
                        'amenities' => 'مرافق',
                        'security' => 'أمان',
                        'extras' => 'إضافات',
                        default => $state
                    }),
            ])
            ->searchable(false)
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([])
            ->bulkActions([])
            ->toggleColumnsTriggerAction(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePropertyFeatures::route('/'),
        ];
    }
}
