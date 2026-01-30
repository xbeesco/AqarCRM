<?php

namespace App\Filament\Resources\Properties;

use App\Filament\Resources\Properties\Schemas\PropertyForm;
use App\Filament\Resources\Properties\Tables\PropertiesTable;
use App\Models\Property;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'العقارات';

    protected static ?string $modelLabel = 'عقار';

    protected static ?string $pluralModelLabel = 'العقارات';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PropertyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PropertiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
            'view' => Pages\ViewProperty::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'owner',
            'propertyType',
            'propertyStatus',
            'location',
        ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name', 'address', 'postal_code', 'notes',
            'owner.name', 'owner.phone',
            'location.name',
            'propertyType.name_ar',
            'propertyStatus.name_ar',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['owner', 'location', 'propertyType', 'propertyStatus']);
    }

    public static function getGlobalSearchResults(string $search): Collection
    {
        $search = trim($search);

        $normalizedSearch = str_replace(['أ', 'إ', 'آ'], 'ا', $search);
        $normalizedSearch = str_replace(['ة'], 'ه', $normalizedSearch);
        $normalizedSearch = str_replace(['ى'], 'ي', $normalizedSearch);
        $searchWithoutSpaces = str_replace(' ', '', $normalizedSearch);

        return static::getGlobalSearchEloquentQuery()
            ->where(function (Builder $query) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('name', 'LIKE', "%{$normalizedSearch}%")
                    ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%")
                    ->orWhere('address', 'LIKE', "%{$normalizedSearch}%")
                    ->orWhere('postal_code', 'LIKE', "%{$search}%")
                    ->orWhere('notes', 'LIKE', "%{$normalizedSearch}%");

                if (is_numeric($search)) {
                    $query->orWhere('id', $search)
                        ->orWhere('floors_count', $search)
                        ->orWhere('build_year', 'LIKE', "%{$search}%");
                }

                $query->orWhereHas('owner', function ($q) use ($normalizedSearch, $search) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%");
                });

                $query->orWhereHas('location', function ($q) use ($normalizedSearch) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%");
                });

                $query->orWhereHas('propertyType', function ($q) use ($normalizedSearch) {
                    $q->where('name_ar', 'LIKE', "%{$normalizedSearch}%");
                });

                $query->orWhereHas('propertyStatus', function ($q) use ($normalizedSearch) {
                    $q->where('name_ar', 'LIKE', "%{$normalizedSearch}%");
                });
            })
            ->limit(50)
            ->get()
            ->map(function ($record) {
                return new GlobalSearchResult(
                    title: $record->name,
                    url: static::getUrl('view', ['record' => $record]),
                    details: [
                        'المالك' => $record->owner?->name ?? 'غير محدد',
                        'النوع' => $record->propertyType?->name_ar ?? 'غير محدد',
                        'الحالة' => $record->propertyStatus?->name_ar ?? 'غير محدد',
                        'الموقع' => $record->location?->name ?? 'غير محدد',
                        'العنوان' => $record->address ?? 'غير محدد',
                    ]
                );
            });
    }
}
