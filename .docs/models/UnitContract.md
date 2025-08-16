# UnitContract Model Tasks - Al-Hiaa Real Estate Management System
**Target Stack:** Laravel 12 & Filament 4

## المبادئ الأساسية للتحسين
- ✅ استخدام Filament's built-in Form components بدلاً من ACF معقد
- ✅ تبسيط Contract management مع dynamic field relationships
- ✅ استخدام Filament's reactive forms للربط بين العقار والوحدة والمستأجر
- ✅ استخدام Laravel's Enums للحالات والجداول

## 1. نموذج عقد الوحدة المبسط

### 1.1 UnitContract Model
```php
class UnitContract extends Model
{
    protected $fillable = [
        'name', 'contract_status', 'unit_id', 'property_id', 'tenant_id',
        'start_date', 'duration_months', 'rent_amount', 'payment_schedule',
        'security_deposit', 'contract_document', 'notes',
        'utilities_included', 'grace_period', 'late_fee_rate'
    ];
    
    protected $casts = [
        'start_date' => 'date',
        'rent_amount' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'late_fee_rate' => 'decimal:2',
        'contract_status' => ContractStatusEnum::class,
        'payment_schedule' => PaymentScheduleEnum::class,
        'utilities_included' => 'boolean',
    ];
    
    // العلاقات الأساسية
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
    
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
    
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }
    
    public function collectionPayments(): HasMany
    {
        return $this->hasMany(CollectionPayment::class);
    }
    
    // دوال مبسطة
    public function getEndDate(): Carbon
    {
        return $this->start_date->addMonths($this->duration_months);
    }
    
    public function isActive(): bool
    {
        return $this->contract_status === ContractStatusEnum::ACTIVE 
            && $this->getEndDate()->isFuture();
    }
    
    public function calculateMonthlyPayment(): float
    {
        return $this->rent_amount;
    }
}
```

### 1.2 Contract Status Enum
```php
enum ContractStatusEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DRAFT = 'draft';
    case EXPIRED = 'expired';
    case TERMINATED = 'terminated';
    
    public function getLabel(): string
    {
        return match($this) {
            self::ACTIVE => 'نشط',
            self::INACTIVE => 'غير نشط',
            self::DRAFT => 'مسودة',
            self::EXPIRED => 'منتهي',
            self::TERMINATED => 'مفسوخ',
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'warning',
            self::DRAFT => 'info',
            self::EXPIRED => 'danger',
            self::TERMINATED => 'secondary',
        };
    }
}
```

## 2. Filament Resource لعقود الوحدات

### 2.1 UnitContract Resource مع Filament 4 Syntax
```php
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;

public static function form(Schema $schema): Schema
{
    return $schema->schema([
        Section::make('بيانات العقد الأساسية')
            ->schema([
                TextInput::make('name')
                    ->label('اسم العقد')
                    ->required()
                    ->columnSpanFull(),
                    
                Grid::make(2)->schema([
                    Toggle::make('contract_status')
                        ->label('حالة العقد')
                        ->onLabel('نشط')
                        ->offLabel('غير نشط')
                        ->default(true)
                        ->columnSpan(1),
                        
                    Select::make('property_id')
                        ->label('العقار')
                        ->relationship('property', 'name')
                        ->searchable()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $state) {
                            // إعادة تعيين الوحدة عند تغيير العقار
                            $set('unit_id', null);
                        })
                        ->columnSpan(1),
                ]),
                
                Grid::make(2)->schema([
                    Select::make('unit_id')
                        ->label('الوحدة')
                        ->options(function (callable $get) {
                            $propertyId = $get('property_id');
                            if (!$propertyId) return [];
                            
                            return Unit::where('property_id', $propertyId)
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->required()
                        ->reactive()
                        ->columnSpan(1),
                        
                    Select::make('tenant_id')
                        ->label('المستأجر')
                        ->relationship('tenant', 'name')
                        ->searchable()
                        ->required()
                        ->options(function () {
                            return User::whereHas('roles', function ($q) {
                                $q->where('name', 'tenant');
                            })->pluck('name', 'id');
                        })
                        ->columnSpan(1),
                ]),
            ]),
            
        Section::make('تفاصيل العقد')
            ->schema([
                Grid::make(2)->schema([
                    DatePicker::make('start_date')
                        ->label('تاريخ البداية')
                        ->required()
                        ->displayFormat('d/m/Y') // كما هو مطلوب في ACF
                        ->format('Y-m-d') // للحفظ في قاعدة البيانات
                        ->columnSpan(1),
                        
                    TextInput::make('duration_months')
                        ->label('مدة العقد (بالشهر)')
                        ->numeric()
                        ->default(12)
                        ->required()
                        ->minValue(1)
                        ->columnSpan(1),
                ]),
                
                Grid::make(2)->schema([
                    TextInput::make('rent_amount')
                        ->label('قيمة الإيجار')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->prefix('ر.س')
                        ->columnSpan(1),
                        
                    Select::make('payment_schedule')
                        ->label('جدولة الدفع')
                        ->options([
                            'month' => 'شهري',
                            'three_month' => 'ربع سنوي',
                            'six_month' => 'نصف سنوي',
                            'year' => 'سنوي',
                        ])
                        ->required()
                        ->columnSpan(1),
                ]),
            ]),
            
        Section::make('تفاصيل إضافية')
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('security_deposit')
                        ->label('التأمين')
                        ->numeric()
                        ->prefix('ر.س')
                        ->columnSpan(1),
                        
                    TextInput::make('grace_period')
                        ->label('فترة السماح (أيام)')
                        ->numeric()
                        ->default(5)
                        ->columnSpan(1),
                ]),
                
                Toggle::make('utilities_included')
                    ->label('المرافق مشمولة')
                    ->columnSpanFull(),
                    
                FileUpload::make('contract_document')
                    ->label('مستند العقد')
                    ->acceptedFileTypes(['application/pdf'])
                    ->directory('contracts/units')
                    ->columnSpanFull(),
                    
                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3)
                    ->columnSpanFull(),
            ]),
    ]);
}
```

### 2.2 UnitContract Table مع فلتر متقدم
```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('name')
                ->label('اسم العقد')
                ->searchable()
                ->sortable(),
                
            TextColumn::make('property.name')
                ->label('العقار')
                ->searchable(),
                
            TextColumn::make('unit.name')
                ->label('الوحدة')
                ->searchable(),
                
            TextColumn::make('tenant.name')
                ->label('المستأجر')
                ->searchable(),
                
            TextColumn::make('rent_amount')
                ->label('قيمة الإيجار')
                ->money('SAR')
                ->sortable(),
                
            BadgeColumn::make('contract_status')
                ->label('الحالة')
                ->colors([
                    'success' => 'active',
                    'warning' => 'inactive',
                    'danger' => 'expired',
                ]),
                
            TextColumn::make('start_date')
                ->label('تاريخ البداية')
                ->date('d/m/Y'),
                
            TextColumn::make('end_date')
                ->label('تاريخ الانتهاء')
                ->getStateUsing(fn ($record) => $record->getEndDate()->format('d/m/Y')),
        ])
        ->filters([
            // فلتر البحث المتقدم (مطابق لـ ACF group_6347b8bf21a23.json)
            Filter::make('contract_search')
                ->form([
                    Grid::make(3)->schema([
                        TextInput::make('search_name')
                            ->label('اسم العقد')
                            ->placeholder('البحث على اسم العقد'),
                            
                        Select::make('unit_id')
                            ->label('الوحدة')
                            ->relationship('unit', 'name')
                            ->searchable()
                            ->columnSpan(1),
                            
                        Select::make('property_id')
                            ->label('العقار')
                            ->relationship('property', 'name')
                            ->searchable()
                            ->columnSpan(1),
                    ]),
                    
                    Grid::make(3)->schema([
                        Select::make('tenant_id')
                            ->label('المستأجر')
                            ->options(function () {
                                return User::whereHas('roles', function ($q) {
                                    $q->where('name', 'tenant');
                                })->pluck('name', 'id');
                            })
                            ->searchable()
                            ->columnSpan(1),
                            
                        TextInput::make('contract_price')
                            ->label('السعر')
                            ->numeric()
                            ->columnSpan(1),
                            
                        DatePicker::make('end_date')
                            ->label('تاريخ الانتهاء')
                            ->displayFormat('d/m/Y')
                            ->columnSpan(1),
                    ]),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['search_name'], fn ($q, $search) => 
                            $q->where('name', 'like', "%{$search}%"))
                        ->when($data['unit_id'], fn ($q, $unitId) => 
                            $q->where('unit_id', $unitId))
                        ->when($data['property_id'], fn ($q, $propertyId) => 
                            $q->where('property_id', $propertyId))
                        ->when($data['tenant_id'], fn ($q, $tenantId) => 
                            $q->where('tenant_id', $tenantId))
                        ->when($data['contract_price'], fn ($q, $price) => 
                            $q->where('rent_amount', '>=', $price))
                        ->when($data['end_date'], function ($q, $endDate) {
                            $q->whereRaw('DATE_ADD(start_date, INTERVAL duration_months MONTH) <= ?', [$endDate]);
                        });
                }),
                
            SelectFilter::make('contract_status')
                ->label('حالة العقد')
                ->options([
                    'active' => 'نشط',
                    'inactive' => 'غير نشط',
                    'expired' => 'منتهي',
                ]),
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            
            // Action لتحميل العقد
            Tables\Actions\Action::make('download_contract')
                ->label('تحميل العقد')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn ($record) => Storage::url($record->contract_document))
                ->openUrlInNewTab()
                ->visible(fn ($record) => filled($record->contract_document)),
        ]);
}
```

## 3. تبسيط إدارة دفعات التحصيل

### 3.1 إنشاء دفعات التحصيل تلقائياً
```php
// في UnitContract Model
protected static function booted()
{
    static::created(function ($contract) {
        if ($contract->contract_status === ContractStatusEnum::ACTIVE) {
            $contract->generateCollectionPayments();
            $contract->assignTenantToUnit();
        }
    });
    
    static::updated(function ($contract) {
        if ($contract->wasChanged('contract_status') && $contract->contract_status === ContractStatusEnum::ACTIVE) {
            $contract->generateCollectionPayments();
            $contract->assignTenantToUnit();
        }
    });
}

public function generateCollectionPayments(): void
{
    // حذف الدفعات الموجودة إذا كانت pending
    $this->collectionPayments()->where('status', 'pending')->delete();
    
    $startDate = $this->start_date;
    $endDate = $this->getEndDate();
    
    $interval = match ($this->payment_schedule) {
        PaymentScheduleEnum::MONTHLY => 1,
        PaymentScheduleEnum::QUARTERLY => 3,
        PaymentScheduleEnum::SEMI_ANNUAL => 6,
        PaymentScheduleEnum::ANNUAL => 12,
    };
    
    $currentDate = $startDate->copy();
    $paymentNumber = 1;
    
    while ($currentDate->lessThan($endDate)) {
        CollectionPayment::create([
            'unit_contract_id' => $this->id,
            'title' => "دفعة رقم {$paymentNumber} - {$this->name}",
            'amount' => $this->rent_amount,
            'start_date' => $currentDate->copy(),
            'end_date' => $currentDate->copy()->addMonths($interval)->subDay(),
            'status' => CollectionPaymentStatus::PENDING,
        ]);
        
        $currentDate->addMonths($interval);
        $paymentNumber++;
    }
}

public function assignTenantToUnit(): void
{
    // تعيين المستأجر للوحدة
    $this->unit->update([
        'tenant_id' => $this->tenant_id,
        'status' => 'occupied',
    ]);
}
```

## 4. التبسيطات المطبقة

### 4.1 إزالة Over-Engineering
- **❌ إزالة**: UnitContractService معقد
- **❌ إزالة**: ContractManagementService
- **❌ إزالة**: TenantAssignmentService
- **✅ بدلاً منها**: دوال مباشرة في Model

### 4.2 استخدام Filament's Reactive Forms
- **Dynamic Unit Loading**: تحديث الوحدات عند اختيار العقار
- **Tenant Role Filtering**: عرض المستأجرين فقط
- **Property-Unit Relationship**: ربط تلقائي بين العقار والوحدة

### 4.3 تبسيط Validation
```php
// في Form
TextInput::make('rent_amount')
    ->numeric()
    ->required()
    ->minValue(1)
    ->rule('regex:/^\d+(\.\d{1,2})?$/') // للتأكد من صحة المبلغ
```

## 5. Migration من WordPress

### 5.1 UnitContract Import مبسط
```php
class UnitContractImportService
{
    public function importUnitContracts(): void
    {
        $wpContracts = $this->getWordPressUnitContracts();
        
        foreach ($wpContracts as $wpContract) {
            UnitContract::create([
                'name' => $wpContract->post_title,
                'contract_status' => $this->mapStatus($wpContract->status),
                'unit_id' => $this->mapUnit($wpContract->unit_id),
                'property_id' => $this->mapProperty($wpContract->property_id),
                'tenant_id' => $this->mapTenant($wpContract->tenant_id),
                'start_date' => Carbon::parse($wpContract->start_date),
                'duration_months' => $wpContract->duration ?? 12,
                'rent_amount' => $wpContract->rent_amount,
                'payment_schedule' => $this->mapPaymentSchedule($wpContract->payment_schedule),
                'contract_document' => $this->migrateFile($wpContract->contract_file),
                'notes' => $wpContract->notes,
            ]);
        }
    }
    
    private function mapStatus($wpStatus): ContractStatusEnum
    {
        return $wpStatus ? ContractStatusEnum::ACTIVE : ContractStatusEnum::INACTIVE;
    }
}
```

## 6. الاختبارات المبسطة

### 6.1 اختبارات أساسية فقط
```php
class UnitContractModelTest extends TestCase
{
    public function test_contract_relationships()
    {
        $unit = Unit::factory()->create();
        $tenant = User::factory()->create();
        $contract = UnitContract::factory()->create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
        ]);
        
        $this->assertEquals($unit->id, $contract->unit->id);
        $this->assertEquals($tenant->id, $contract->tenant->id);
    }
    
    public function test_collection_payments_generation()
    {
        $contract = UnitContract::factory()->create([
            'start_date' => '2024-01-01',
            'duration_months' => 12,
            'payment_schedule' => PaymentScheduleEnum::MONTHLY,
            'contract_status' => ContractStatusEnum::ACTIVE,
        ]);
        
        $this->assertCount(12, $contract->collectionPayments);
    }
    
    public function test_tenant_unit_assignment()
    {
        $unit = Unit::factory()->create(['tenant_id' => null]);
        $tenant = User::factory()->create();
        $contract = UnitContract::factory()->create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'contract_status' => ContractStatusEnum::ACTIVE,
        ]);
        
        $unit->refresh();
        $this->assertEquals($tenant->id, $unit->tenant_id);
    }
}
```

### 6.2 Feature Tests للفلتر المتقدم
```php
class UnitContractAdvancedFilterTest extends TestCase
{
    public function test_contract_name_search()
    {
        // اختبار البحث بالاسم
    }
    
    public function test_property_unit_relationship_filter()
    {
        // اختبار فلتر العقار والوحدة
    }
    
    public function test_tenant_role_filtering()
    {
        // اختبار عرض المستأجرين فقط
    }
}
```

## 7. إشعارات العقود

### 7.1 Contract Notifications مبسطة
```php
// Command للمهام المجدولة
class UnitContractNotificationsCommand extends Command
{
    public function handle()
    {
        // العقود منتهية الصلاحية
        $expiredContracts = UnitContract::where('contract_status', ContractStatusEnum::ACTIVE)
            ->whereRaw('DATE_ADD(start_date, INTERVAL duration_months MONTH) <= ?', [now()])
            ->get();
            
        foreach ($expiredContracts as $contract) {
            $contract->update(['contract_status' => ContractStatusEnum::EXPIRED]);
            
            // تحرير الوحدة
            $contract->unit->update([
                'tenant_id' => null,
                'status' => 'available',
            ]);
        }
        
        // إشعارات التجديد القريب
        $expiringSoon = UnitContract::where('contract_status', ContractStatusEnum::ACTIVE)
            ->whereRaw('DATE_ADD(start_date, INTERVAL duration_months MONTH) BETWEEN ? AND ?', 
                [now(), now()->addDays(30)])
            ->get();
            
        foreach ($expiringSoon as $contract) {
            // إرسال إشعار تجديد
            Notification::make()
                ->title("العقد {$contract->name} ينتهي قريباً")
                ->warning()
                ->send();
        }
    }
}
```

## الملخص النهائي

تم تبسيط نظام عقود الوحدات ليستخدم:
1. **Filament's reactive forms** للربط الديناميكي بين العقار والوحدة
2. **Advanced filtering** مطابق لمتطلبات ACF
3. **Automatic payment generation** عند تفعيل العقد
4. **Tenant-unit assignment** تلقائي
5. **Built-in validation** مع Filament components
6. **Laravel Enums** للحالات والجداول
7. **Scheduled commands** لإدارة انتهاء العقود

هذا النهج يحافظ على جميع الوظائف المعقدة المطلوبة من ACF مع تبسيط كبير في التطبيق.