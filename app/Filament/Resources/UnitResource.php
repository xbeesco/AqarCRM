<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitResource\Pages;
use App\Models\Unit;
use App\Models\Property;
use App\Models\UnitType;
use App\Models\UnitCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use App\Models\CollectionPayment;
use App\Models\PropertyRepair;
use Carbon\Carbon;

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
                            ->label('سعر الايجار الاستدلالي')
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
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(5120) // 5MB
                            ->directory('unit-floor-plans')
                            ->preserveFilenames()
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
                    ->sortable(),
                
                TextColumn::make('unitType.name_ar')
                    ->label('النوع')
                    ->searchable(),
                
                TextColumn::make('unitCategory.name_ar')
                    ->label('التصنيف')
                    ->searchable(),
                
                TextColumn::make('rooms_count')
                    ->label('الغرف')
                    ->sortable(),
                
                TextColumn::make('bathrooms_count')
                    ->label('الحمامات')
                    ->sortable(),
                
                TextColumn::make('floor_number')
                    ->label('الطابق')
                    ->sortable(),
                
                TextColumn::make('area_sqm')
                    ->label('المساحة')
                    ->suffix(' م²')
                    ->sortable(),
                
                TextColumn::make('rent_price')
                    ->label('الإيجار')
                    ->money('SAR')
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make()
                    ->label(''),
                EditAction::make()
                    ->label(''),
            ])
            ->toolbarActions([]);
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