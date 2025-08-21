<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitResource\Pages;
use App\Models\Unit;
use App\Models\Property;
use App\Models\UnitStatus;
use App\Models\User;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $navigationLabel = 'الوحدات';

    protected static ?string $modelLabel = 'وحدة';

    protected static ?string $pluralModelLabel = 'الوحدات';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('المعلومات الأساسية / Basic Information')
                    ->schema([
                        Select::make('property_id')
                            ->label('العقار / Property')
                            ->options(Property::pluck('name', 'id'))
                            ->required()
                            ->reactive()
                            ->searchable(),
                        
                        TextInput::make('unit_number')
                            ->label('رقم الوحدة / Unit Number')
                            ->required()
                            ->maxLength(20)
                            ->placeholder('مثال: 101, A-5'),
                        
                        TextInput::make('floor_number')
                            ->label('الدور / Floor Number')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(200),
                        
                        Select::make('unit_type')
                            ->label('نوع الوحدة / Unit Type')
                            ->options(Unit::getUnitTypeOptions())
                            ->required()
                            ->default('apartment'),
                    ])->columns(2),

                Section::make('المواصفات / Specifications')
                    ->schema([
                        TextInput::make('area_sqm')
                            ->label('المساحة (م²) / Area (sqm)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10000)
                            ->step(0.01)
                            ->suffix('م²'),
                        
                        TextInput::make('rooms_count')
                            ->label('عدد الغرف / Rooms Count')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(20),
                        
                        TextInput::make('bathrooms_count')
                            ->label('عدد الحمامات / Bathrooms Count')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10),
                        
                        Select::make('direction')
                            ->label('الاتجاه / Direction')
                            ->options(Unit::getDirectionOptions())
                            ->nullable(),
                        
                        Select::make('view_type')
                            ->label('نوع الإطلالة / View Type')
                            ->options(Unit::getViewTypeOptions())
                            ->nullable(),
                    ])->columns(2),

                Section::make('المميزات / Features')
                    ->schema([
                        Toggle::make('furnished')
                            ->label('مفروش / Furnished')
                            ->default(false),
                        
                        Toggle::make('has_balcony')
                            ->label('يوجد بلكونة / Has Balcony')
                            ->default(false),
                        
                        Toggle::make('has_parking')
                            ->label('يوجد موقف سيارة / Has Parking')
                            ->default(false),
                        
                        Toggle::make('has_storage')
                            ->label('يوجد مخزن / Has Storage')
                            ->default(false),
                        
                        Toggle::make('has_maid_room')
                            ->label('يوجد غرفة خادمة / Has Maid Room')
                            ->default(false),
                    ])->columns(2),

                Section::make('التسعير / Pricing')
                    ->schema([
                        TextInput::make('rent_price')
                            ->label('قيمة الإيجار الشهري / Monthly Rent')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->prefix('SAR'),
                        
                        Select::make('unit_ranking')
                            ->label('التصنيف / Ranking')
                            ->options(Unit::getUnitRankingOptions())
                            ->nullable(),
                    ])->columns(2),

                Section::make('الحالة والإعدادات / Status & Settings')
                    ->schema([
                        Select::make('status_id')
                            ->label('حالة الوحدة / Unit Status')
                            ->options(UnitStatus::active()->pluck('name_ar', 'id'))
                            ->required(),
                        
                        Select::make('current_tenant_id')
                            ->label('المستأجر الحالي / Current Tenant')
                            ->options(User::whereHas('roles', function ($query) {
                                $query->where('name', 'tenant');
                            })->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                        
                        DatePicker::make('available_from')
                            ->label('متاح من / Available From')
                            ->format('Y-m-d')
                            ->displayFormat('d/m/Y')
                            ->nullable(),
                        
                        Toggle::make('is_active')
                            ->label('نشط / Active')
                            ->default(true),
                        
                        Textarea::make('notes')
                            ->label('ملاحظات / Notes')
                            ->maxLength(2000)
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('unit_code')
                    ->label('كود الوحدة / Unit Code')
                    ->searchable()
                    ->sortable()
                    ->width('120px')
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('property.name')
                    ->label('العقار / Property')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->color('primary'),
                
                Tables\Columns\TextColumn::make('unit_number')
                    ->label('رقم الوحدة / Unit Number')
                    ->searchable()
                    ->sortable()
                    ->width('100px')
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('floor_number')
                    ->label('الدور / Floor')
                    ->sortable()
                    ->width('80px')
                    ->alignCenter(),
                
                Tables\Columns\BadgeColumn::make('unit_type')
                    ->label('النوع / Type')
                    ->formatStateUsing(fn (string $state): string => Unit::getUnitTypeOptions()[$state] ?? $state)
                    ->colors([
                        'primary' => 'apartment',
                        'success' => 'studio',
                        'warning' => 'duplex',
                        'danger' => 'penthouse',
                        'secondary' => ['office', 'shop', 'warehouse'],
                    ])
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('rooms_bathrooms_display')
                    ->label('الغرف/الحمامات / Rooms/Baths')
                    ->width('120px')
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('area_display')
                    ->label('المساحة / Area')
                    ->sortable(['area_sqm'])
                    ->width('100px')
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('rent_display')
                    ->label('الإيجار الشهري / Monthly Rent')
                    ->sortable(['rent_price'])
                    ->width('130px')
                    ->alignRight(),
                
                Tables\Columns\BadgeColumn::make('status.name_ar')
                    ->label('الحالة / Status')
                    ->formatStateUsing(fn ($record) => $record->status?->name_ar)
                    ->colors([
                        'success' => fn ($record) => $record->status?->color === 'green',
                        'primary' => fn ($record) => $record->status?->color === 'blue',
                        'warning' => fn ($record) => $record->status?->color === 'yellow',
                        'danger' => fn ($record) => $record->status?->color === 'red',
                        'secondary' => fn ($record) => $record->status?->color === 'gray',
                    ])
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('currentTenant.name')
                    ->label('المستأجر الحالي / Current Tenant')
                    ->searchable()
                    ->limit(20)
                    ->url(fn (Unit $record) => $record->currentTenant 
                        ? route('filament.admin.resources.tenants.view', $record->currentTenant) 
                        : null)
                    ->color('primary')
                    ->placeholder('لا يوجد / None'),
                
                Tables\Columns\TextColumn::make('available_from')
                    ->label('متاح من / Available From')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('متاح الآن / Available Now'),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filters([
                SelectFilter::make('property')
                    ->label('العقار / Property')
                    ->relationship('property', 'name')
                    ->searchable()
                    ->multiple(),
                
                SelectFilter::make('status')
                    ->label('حالة الوحدة / Unit Status')
                    ->relationship('status', 'name_ar')
                    ->multiple(),
                
                SelectFilter::make('unit_type')
                    ->label('نوع الوحدة / Unit Type')
                    ->options(Unit::getUnitTypeOptions())
                    ->multiple(),
                
                Filter::make('price_range')
                    ->label('نطاق السعر / Price Range')
                    ->form([
                        TextInput::make('price_from')
                            ->numeric()
                            ->label('من / From')
                            ->prefix('SAR'),
                        TextInput::make('price_to')
                            ->numeric()
                            ->label('إلى / To')
                            ->prefix('SAR'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['price_from'],
                                fn (Builder $query, $price): Builder => $query->where('rent_price', '>=', $price)
                            )
                            ->when(
                                $data['price_to'],
                                fn (Builder $query, $price): Builder => $query->where('rent_price', '<=', $price)
                            );
                    }),
                
                SelectFilter::make('rooms_count')
                    ->label('عدد الغرف / Rooms Count')
                    ->options([
                        '0' => 'ستوديو / Studio',
                        '1' => 'غرفة واحدة / 1 Room',
                        '2' => 'غرفتان / 2 Rooms',
                        '3' => '3 غرف / 3 Rooms',
                        '4' => '4 غرف / 4 Rooms',
                        '5' => '5+ غرف / 5+ Rooms',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            function (Builder $query, $rooms) {
                                if ($rooms === '5') {
                                    return $query->where('rooms_count', '>=', 5);
                                }
                                return $query->where('rooms_count', $rooms);
                            }
                        );
                    }),
                
                Filter::make('area_range')
                    ->label('نطاق المساحة / Area Range')
                    ->form([
                        TextInput::make('area_from')
                            ->numeric()
                            ->label('من / From')
                            ->suffix('م²'),
                        TextInput::make('area_to')
                            ->numeric()
                            ->label('إلى / To')
                            ->suffix('م²'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['area_from'],
                                fn (Builder $query, $area): Builder => $query->where('area_sqm', '>=', $area)
                            )
                            ->when(
                                $data['area_to'],
                                fn (Builder $query, $area): Builder => $query->where('area_sqm', '<=', $area)
                            );
                    }),
                
                TernaryFilter::make('furnished')
                    ->label('مفروش / Furnished')
                    ->trueLabel('مفروش / Furnished')
                    ->falseLabel('غير مفروش / Unfurnished')
                    ->placeholder('الكل / All'),
                
                SelectFilter::make('tenant')
                    ->label('المستأجر / Tenant')
                    ->relationship('currentTenant', 'name')
                    ->searchable()
                    ->multiple(),
            ])
            ->actions([
                EditAction::make()
                    ->label('تعديل / Edit'),
            ])
            ->bulkActions([
                // Remove bulk actions as per requirements
            ])
            ->defaultSort('property_id', 'asc')
            ->defaultSort('unit_number', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnits::route('/'),
            'create' => Pages\CreateUnit::route('/create'),
            'view' => Pages\ViewUnit::route('/{record}'),
            'edit' => Pages\EditUnit::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchResults(string $search): array
    {
        return static::getModel()::query()
            ->with(['property', 'property.location'])
            ->where(function (Builder $query) use ($search) {
                $query->where('unit_number', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('property', function (Builder $query) use ($search) {
                          $query->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('property.location', function (Builder $query) use ($search) {
                          $query->where('name_ar', 'like', "%{$search}%")
                                ->orWhere('name_en', 'like', "%{$search}%");
                      });
            })
            ->limit(5)
            ->get()
            ->map(function (Unit $record) {
                $propertyName = $record->property?->name ?? 'غير محدد';
                $locationName = $record->property?->location?->name_ar ?? 'غير محدد';
                
                return GlobalSearchResult::make()
                    ->title("وحدة رقم: " . $record->unit_number)
                    ->details([
                        'العقار: ' . $propertyName,
                        'الموقع: ' . $locationName,
                        'السعر: ' . number_format($record->rent_price ?? 0, 2) . ' ر.س',
                        'الحالة: ' . ($record->status ?? 'غير محدد')
                    ])
                    ->actions([
                        Action::make('edit')
                            ->label('تحرير')
                            ->icon('heroicon-s-pencil')
                            ->url(static::getUrl('edit', ['record' => $record])),
                    ])
                    ->url(static::getUrl('view', ['record' => $record]));
            })
            ->toArray();
    }
}