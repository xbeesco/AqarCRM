<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyResource\Pages;
use App\Models\CollectionPayment;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\UnitContract;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static ?string $navigationLabel = 'العقارات';

    protected static ?string $modelLabel = 'عقار';

    protected static ?string $pluralModelLabel = 'العقارات';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('البيانات الأساسية')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label('اسم العقار')
                            ->required()
                            ->columnSpan(1),

                        Select::make('owner_id')
                            ->label('المالك')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('status_id')
                            ->label('حالة العقار')
                            ->options(PropertyStatus::where('is_active', true)->orderBy('sort_order')->pluck('name_ar', 'id'))
                            ->searchable()
                            ->required(),

                        Select::make('type_id')
                            ->label('نوع العقار')
                            ->options(PropertyType::all()->pluck('name_ar', 'id'))
                            ->searchable()
                            ->required(),
                    ]),
                ]),

            Section::make('الموقع والعنوان')
                ->schema([
                    Select::make('location_id')
                        ->label('الموقع')
                        ->options(\App\Models\Location::getHierarchicalOptions())
                        ->searchable()
                        ->allowHtml()
                        ->nullable(),

                    Grid::make(2)->schema([
                        TextInput::make('address')
                            ->label('رقم المبنى واسم الشارع')
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('postal_code')
                            ->label('الرمز البريدي')
                            ->numeric()
                            ->columnSpan(1),
                    ]),
                ]),

            Section::make('تفاصيل إضافية')
                ->columnSpanFull()
                ->schema([
                    Grid::make(4)->schema([
                        TextInput::make('parking_spots')
                            ->label('عدد المواقف')
                            ->numeric()
                            ->nullable(),

                        TextInput::make('elevators')
                            ->label('عدد المصاعد')
                            ->numeric()
                            ->nullable(),

                        TextInput::make('floors_count')
                            ->label('عدد الطوابق')
                            ->numeric()
                            ->nullable(),

                        TextInput::make('build_year')
                            ->label('سنة البناء')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(date('Y'))
                            ->nullable(),
                    ]),

                    Grid::make(2)->schema([
                        CheckboxList::make('features')
                            ->label('المميزات')
                            ->relationship('features', 'name_ar')
                            ->columns(4)
                            ->columnSpan(1),

                        Textarea::make('notes')
                            ->label('ملاحظات خاصة')
                            ->rows(6)
                            ->columnSpan(1),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['owner', 'location.parent.parent.parent'])
                    ->withCount('units as total_units');
            })
            ->columns([
                TextColumn::make('name')
                    ->label('اسم العقار')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('owner.name')
                    ->label('اسم المالك')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_units')
                    ->label('الوحدات')
                    ->default(0)
                    ->alignCenter(),

                TextColumn::make('location.name')
                    ->label('الموقع')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        if (! $record->location) {
                            return '-';
                        }

                        $path = [];
                        $current = $record->location;

                        while ($current) {
                            array_unshift($path, $current->name);
                            $current = $current->parent;
                        }

                        return implode(' > ', $path);
                    }),
            ])
            ->searchable()
            ->filters([])
            ->recordActions([
                ViewAction::make()
                    ->label('تقرير')
                    ->icon('heroicon-o-document-text'),
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25);
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
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
            'view' => Pages\ViewProperty::route('/{record}'),
        ];
    }

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
            'address',
            'postal_code',
            'parking_spots',
            'elevators',
            'build_year',
            'floors_count',
            'notes',
            'owner.name',
            'owner.email',
            'owner.phone',
            'location.name',
            'location.name_ar',
            'location.name_en',
            'propertyType.name_ar',
            'propertyType.name_en',
            'propertyStatus.name_ar',
            'propertyStatus.name_en',
            'created_at',
            'updated_at',
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['owner', 'location', 'propertyType', 'propertyStatus']);
    }

    public static function getGlobalSearchResults(string $search): Collection
    {
        $search = trim($search);

        // Normalize Arabic characters for better search
        $normalizedSearch = str_replace(['أ', 'إ', 'آ'], 'ا', $search);
        $normalizedSearch = str_replace(['ة'], 'ه', $normalizedSearch);

        return static::getGlobalSearchEloquentQuery()
            ->where(function (Builder $query) use ($search, $normalizedSearch) {
                $searchWithoutSpaces = str_replace(' ', '', $normalizedSearch);

                $query->where('name', 'LIKE', "%{$normalizedSearch}%")
                    ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%")
                    ->orWhere('address', 'LIKE', "%{$normalizedSearch}%")
                    ->orWhere('address', 'LIKE', "%{$searchWithoutSpaces}%")
                    ->orWhere('postal_code', 'LIKE', "%{$search}%")
                    ->orWhere('notes', 'LIKE', "%{$normalizedSearch}%");

                if (is_numeric($search)) {
                    $query->orWhere('parking_spots', 'LIKE', "%{$search}%")
                        ->orWhere('elevators', 'LIKE', "%{$search}%")
                        ->orWhere('build_year', 'LIKE', "%{$search}%")
                        ->orWhere('floors_count', 'LIKE', "%{$search}%")
                        ->orWhere('id', $search);
                }

                // Search in owner
                $query->orWhereHas('owner', function ($q) use ($search, $normalizedSearch) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%");
                });

                // Search in location
                $query->orWhereHas('location', function ($q) use ($search, $normalizedSearch) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name_ar', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name_en', 'LIKE', "%{$search}%");
                });

                // Search in property type
                $query->orWhereHas('propertyType', function ($q) use ($search, $normalizedSearch) {
                    $q->where('name_ar', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name_en', 'LIKE', "%{$search}%");
                });

                // Search in property status
                $query->orWhereHas('propertyStatus', function ($q) use ($search, $normalizedSearch) {
                    $q->where('name_ar', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name_en', 'LIKE', "%{$search}%");
                });

                // Search in dates (multiple formats)
                if (preg_match('/\d{4}/', $search)) {
                    $query->orWhereYear('created_at', $search)
                        ->orWhereYear('updated_at', $search);
                }

                if (preg_match('/\d{1,2}[-\/]\d{1,2}/', $search)) {
                    $dateParts = preg_split('/[-\/]/', $search);
                    if (count($dateParts) == 2) {
                        $query->orWhereMonth('created_at', $dateParts[1])
                            ->whereDay('created_at', $dateParts[0]);
                    }
                }
            })
            ->limit(50)
            ->get()
            ->map(function ($record) {
                return new GlobalSearchResult(
                    title: $record->name,
                    url: static::getUrl('edit', ['record' => $record]),
                    details: [
                        'المالك' => $record->owner?->name ?? 'غير محدد',
                        'الموقع' => $record->location?->name ?? 'غير محدد',
                        'النوع' => $record->propertyType?->name_ar ?? 'غير محدد',
                        'الحالة' => $record->propertyStatus?->name_ar ?? 'غير محدد',
                        'العنوان' => $record->address ?? 'غير محدد',
                    ],
                    actions: []
                );
            });
    }

    /**
     * Get property statistics for infolist display.
     */
    private static function getPropertyStatistics(Property $property): array
    {
        $totalUnits = $property->units()->count();
        $occupiedUnits = $property->units()->whereHas('activeContract')->count();
        $vacantUnits = $totalUnits - $occupiedUnits;
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 2) : 0;

        $monthlyRevenue = UnitContract::where('property_id', $property->id)
            ->active()
            ->sum('monthly_rent');

        $yearlyRevenue = CollectionPayment::where('property_id', $property->id)
            ->collectedPayments()
            ->whereYear('collection_date', Carbon::now()->year)
            ->sum('total_amount');

        $pendingPayments = CollectionPayment::where('property_id', $property->id)
            ->where('due_date_start', '<=', Carbon::now())
            ->whereNull('collection_date')
            ->sum('total_amount');

        return [
            'total_units' => $totalUnits,
            'occupied_units' => $occupiedUnits,
            'vacant_units' => $vacantUnits,
            'occupancy_rate' => $occupancyRate,
            'monthly_revenue' => $monthlyRevenue,
            'yearly_revenue' => $yearlyRevenue,
            'pending_payments' => $pendingPayments,
        ];
    }
}
