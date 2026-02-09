<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitResource\Pages;
use App\Models\CollectionPayment;
use App\Models\Owner;
use App\Models\PropertyRepair;
use App\Models\Tenant;
use App\Models\Unit;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $navigationLabel = 'الوحدات';

    protected static ?string $modelLabel = 'وحدة';

    protected static ?string $pluralModelLabel = 'الوحدات';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('المعلومات الأساسية')
                ->columnSpanFull()
                ->columns(12)
                ->schema([
                    Select::make('property_id')
                        ->label('العقار')
                        ->relationship('property', 'name')
                        ->searchable()
                        ->required()
                        ->preload()
                        ->disabledOn('edit')
                        ->columnSpan(3),

                    Select::make('unit_type_id')
                        ->label('نوع الوحدة')
                        ->relationship('unitType', 'name_ar')
                        ->required()
                        ->native(false)
                        ->columnSpan(3),

                    TextInput::make('name')
                        ->label('اسم الوحدة')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(3),

                    Select::make('unit_category_id')
                        ->label('تصنيف الوحدة')
                        ->relationship('unitCategory', 'name_ar')
                        ->required()
                        ->native(false)
                        ->columnSpan(3),
                ]),

            Section::make('التفاصيل')
                ->columnSpan(1)
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('rent_price')
                            ->label(' الايجار الشهري الاستدلالي')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('ريال'),
                        TextInput::make('floor_number')
                            ->label('رقم الطابق')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->nullable(),
                        TextInput::make('area_sqm')
                            ->label('المساحة')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('م²')
                            ->nullable(),

                        TextInput::make('rooms_count')
                            ->label('عدد الغرف')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(20)
                            ->nullable(),

                        TextInput::make('bathrooms_count')
                            ->label('عدد دورات المياه')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->nullable(),

                        TextInput::make('balconies_count')
                            ->label('عدد الشرفات')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->nullable(),

                        Select::make('has_laundry_room')
                            ->label('غرفة غسيل')
                            ->options([
                                1 => 'نعم',
                                0 => 'لا',
                            ])
                            ->required()
                            ->default(0),

                        TextInput::make('electricity_account_number')
                            ->label('رقم حساب الكهرباء')
                            ->maxLength(255)
                            ->nullable(),

                        TextInput::make('water_expenses')
                            ->label('مصروف المياه')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('ريال')
                            ->nullable(),

                    ]),
                ]),

            Section::make('المميزات')
                ->columnSpan(1)
                ->schema([
                    CheckboxList::make('features')
                        ->label('مميزات الوحدة')
                        ->hiddenLabel()
                        ->relationship('features', 'name_ar')
                        ->columns(3),
                ]),

            Section::make('المخططات والملاحظات')
                ->columnSpanFull()
                ->schema([
                    Grid::make(2)->schema([
                        FileUpload::make('floor_plan_file')
                            ->label('مخطط الوحدة')
                            ->directory('unit--floor-plan-file')
                            ->nullable(),

                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->maxLength(65535)
                            ->rows(3),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الوحدة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('property.name')
                    ->label('العقار')
                    ->searchable()
                    ->sortable()
                    ->tooltip(function ($record) {
                        if (! $record->property || ! $record->property->location) {
                            return null;
                        }

                        // Build full path from current location to root
                        $path = [];
                        $current = $record->property->location;

                        while ($current) {
                            array_unshift($path, $current->name);
                            $current = $current->parent;
                        }

                        return 'الموقع: '.implode(' > ', $path);
                    }),

                TextColumn::make('occupancy_status')
                    ->label('حالة الإشغال')
                    ->badge()
                    ->color(fn ($state) => $state->color())
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->sortable(query: function (Builder $query, string $direction) {
                        return $query->leftJoin('unit_contracts', function ($join) {
                            $join->on('units.id', '=', 'unit_contracts.unit_id')
                                ->where('unit_contracts.contract_status', '=', 'active');
                        })
                            ->orderByRaw('CASE WHEN unit_contracts.id IS NOT NULL THEN 1 ELSE 0 END '.$direction);
                    }),

                TextColumn::make('unitType.name_ar')
                    ->label('نوع الوحدة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unitCategory.name_ar')
                    ->label('تصنيف الوحدة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('area_sqm')
                    ->label('المساحة')
                    ->suffix(' م²')
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->alignCenter(),

                TextColumn::make('rooms_count')
                    ->label('عدد الغرف')
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->alignCenter(),

                TextColumn::make('bathrooms_count')
                    ->label('دورات المياه')
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->alignCenter(),

                TextColumn::make('rent_price')
                    ->label('الإيجار الشهري')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state).' ريال' : '-')
                    ->searchable(query: function ($query, $search) {
                        // البحث بالإيجار الشهري أو السنوي
                        $monthlyRent = str_replace(',', '', $search);
                        $monthlyRent = floatval($monthlyRent);
                        $yearlyRent = $monthlyRent / 12;

                        return $query
                            ->orWhere('rent_price', 'like', '%'.$search.'%')
                            ->orWhere('rent_price', $monthlyRent)
                            ->orWhere('rent_price', $yearlyRent);
                    })
                    ->sortable()
                    ->alignEnd(),
            ])
            ->filters([
                SelectFilter::make('property')
                    ->label('العقار')
                    ->relationship('property', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),

                Filter::make('owner')
                    ->label('المالك')
                    ->form([
                        Select::make('owner_id')
                            ->label('المالك')
                            ->options(Owner::pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['owner_id'] ?? null,
                            fn (Builder $query, $value) => $query->whereHas('property', fn ($q) => $q->where('owner_id', $value))
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! ($data['owner_id'] ?? null)) {
                            return null;
                        }

                        $owner = Owner::find($data['owner_id']);

                        return $owner ? 'المالك: '.$owner->name : null;
                    }),

                Filter::make('tenant')
                    ->label('المستأجر')
                    ->form([
                        Select::make('tenant_id')
                            ->label('المستأجر')
                            ->options(Tenant::pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['tenant_id'] ?? null,
                            fn (Builder $query, $value) => $query->whereHas('activeContract', fn ($q) => $q->where('tenant_id', $value))
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! ($data['tenant_id'] ?? null)) {
                            return null;
                        }

                        $tenant = Tenant::find($data['tenant_id']);

                        return $tenant ? 'المستأجر: '.$tenant->name : null;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('تقرير')
                    ->icon('heroicon-o-document-text'),
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square'),
                ActionGroup::make([
                    Action::make('view_unit_contracts')
                        ->label('عقود الوحدة')
                        ->url(fn ($record) => UnitContractResource::getUrl('index').'?unit_id='.$record->id),
                    Action::make('view_collection_payments')
                        ->label('دفعات المستأجرين')
                        ->url(fn ($record) => CollectionPaymentResource::getUrl('index').'?property_id='.$record->property_id.'&unit_id='.$record->id),
                    Action::make('view_expenses')
                        ->label('النفقات')
                        ->url(fn ($record) => ExpenseResource::getUrl('index').'?property_id='.$record->property_id.'&unit_id='.$record->id),
                ])->label('عرض البيانات'),
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
            'index' => Pages\ListUnits::route('/'),
            'create' => Pages\CreateUnit::route('/create'),
            'view' => Pages\ViewUnit::route('/{record}'),
            'edit' => Pages\EditUnit::route('/{record}/edit'),
        ];
    }

    private static function getUnitStatistics(Unit $unit): array
    {
        // العقد النشط الحالي
        $activeContract = $unit->contracts()
            ->where('contract_status', 'active')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->with('tenant')
            ->first();

        // إجمالي الإيرادات من الوحدة
        $totalRevenue = CollectionPayment::where('unit_id', $unit->id)
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', true);
            })
            ->sum('total_amount');

        // المستحقات غير المدفوعة
        $pendingPayments = CollectionPayment::where('unit_id', $unit->id)
            ->where('due_date_start', '<=', now())
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', false);
            })
            ->sum('total_amount');

        // تكاليف الصيانة للوحدة
        $maintenanceCosts = PropertyRepair::where('unit_id', $unit->id)
            ->where('status', 'completed')
            ->sum('total_cost');

        // عدد العقود السابقة
        $previousContracts = $unit->contracts()
            ->where('contract_status', 'completed')
            ->count();

        // متوسط مدة الإيجار
        $avgContractDuration = $unit->contracts()
            ->whereIn('contract_status', ['active', 'completed'])
            ->selectRaw('AVG(DATEDIFF(end_date, start_date)) as avg_days')
            ->value('avg_days');

        $avgContractMonths = $avgContractDuration ? round($avgContractDuration / 30) : 0;

        return [
            'property_name' => $unit->property->name,
            'floor_number' => $unit->floor_number,
            'rooms_count' => $unit->rooms_count,
            'area_sqm' => $unit->area_sqm,
            'rent_price' => $unit->rent_price,
            'is_occupied' => $activeContract !== null,
            'current_tenant' => $activeContract ? $activeContract->tenant->name : null,
            'contract_start' => $activeContract ? $activeContract->start_date : null,
            'contract_end' => $activeContract ? $activeContract->end_date : null,
            'total_revenue' => $totalRevenue,
            'pending_payments' => $pendingPayments,
            'maintenance_costs' => $maintenanceCosts,
            'net_income' => $totalRevenue - $maintenanceCosts,
            'previous_contracts' => $previousContracts,
            'avg_contract_months' => $avgContractMonths,
        ];
    }
}
