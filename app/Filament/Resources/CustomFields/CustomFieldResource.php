<?php

namespace App\Filament\Resources\CustomFields;

use App\Filament\Resources\CustomFields\Pages\CreateCustomField;
use App\Filament\Resources\CustomFields\Pages\EditCustomField;
use App\Filament\Resources\CustomFields\Pages\ListCustomFields;
use App\Filament\Resources\CustomFields\Schemas\CustomFieldForm;
use App\Filament\Resources\CustomFields\Tables\CustomFieldsTable;
use App\Models\CustomField;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomFieldResource extends Resource
{
    protected static ?string $model = CustomField::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $navigationLabel = 'الحقول المخصصة';

    protected static ?string $modelLabel = 'حقل مخصص';

    protected static ?string $pluralModelLabel = 'الحقول المخصصة';

    public static function form(Schema $schema): Schema
    {
        return CustomFieldForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomFieldsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomFields::route('/'),
            'create' => CreateCustomField::route('/create'),
            'edit' => EditCustomField::route('/{record}/edit'),
        ];
    }
}
