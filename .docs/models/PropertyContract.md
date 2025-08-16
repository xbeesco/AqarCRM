# PropertyContract Model Tasks - Al-Hiaa Real Estate Management System
**Target Stack:** Laravel 12 & Filament 4

## المبادئ الأساسية للتحسين
- ✅ استخدام Filament's built-in Form components بدلاً من ACF custom fields
- ✅ تبسيط Contract management مع built-in validation
- ✅ استخدام Laravel's file upload بدلاً من معالجة معقدة
- ✅ استخدام Filament's date handling مع proper localization

## 1. نموذج عقد المالك المبسط

### 1.1 PropertyContract Model
```php
class PropertyContract extends Model
{
    protected $fillable = [
        'title', 'property_id', 'owner_id', 'start_date', 
        'duration_months', 'management_percentage', 'payment_schedule',
        'contract_file_path', 'notes', 'status'
    ];
    
    protected $casts = [
        'start_date' => 'date',
        'management_percentage' => 'decimal:2',
        'payment_schedule' => PaymentScheduleEnum::class,
        'status' => ContractStatusEnum::class,
    ];
    
    // العلاقات الأساسية
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
    
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    
    public function supplyPayments(): HasMany
    {
        return $this->hasMany(SupplyPayment::class);
    }
    
    // دوال مبسطة
    public function getEndDate(): Carbon
    {
        return $this->start_date->addMonths($this->duration_months);
    }
    
    public function isActive(): bool
    {
        return $this->status === ContractStatusEnum::ACTIVE 
            && $this->getEndDate()->isFuture();
    }
    
    public function calculateMonthlyCommission(float $totalRevenue): float
    {
        return $totalRevenue * ($this->management_percentage / 100);
    }
}
```

### 1.2 استخدام Enums بدلاً من Strings
```php
enum PaymentScheduleEnum: string
{
    case MONTHLY = 'month';
    case QUARTERLY = 'three_month';
    case SEMI_ANNUAL = 'six_month';
    case ANNUAL = 'year';
    
    public function getLabel(): string
    {
        return match($this) {
            self::MONTHLY => 'شهريا',
            self::QUARTERLY => 'ربع سنوي',
            self::SEMI_ANNUAL => 'نصف سنوي',
            self::ANNUAL => 'سنوي',
        };
    }
}
```

## 2. Filament Resource لعقود الملاك

### 2.1 PropertyContract Resource مع Filament 4 Syntax
```php
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;

public static function form(Schema $schema): Schema
{
    return $schema->schema([
        Section::make('بيانات العقد الأساسية')
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('title')
                        ->label('اسم العقد')
                        ->required()
                        ->columnSpan(1),
                        
                    Select::make('property_id')
                        ->label('العقار')
                        ->relationship('property', 'name')
                        ->searchable()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $state) {
                            // تحديث المالك تلقائياً بناءً على العقار
                            $property = Property::find($state);
                            if ($property) {
                                $set('owner_id', $property->owner_id);
                            }
                        })
                        ->columnSpan(1),
                ]),
                
                Grid::make(2)->schema([
                    DatePicker::make('start_date')
                        ->label('تاريخ إنشاء العقد')
                        ->required()
                        ->displayFormat('Y-m-d') // كما هو مطلوب في ACF
                        ->columnSpan(1),
                        
                    TextInput::make('duration_months')
                        ->label('مدة التعاقد بالشهر')
                        ->numeric()
                        ->default(12)
                        ->required()
                        ->minValue(1)
                        ->maxValue(120)
                        ->columnSpan(1),
                ]),
                
                Grid::make(2)->schema([
                    Select::make('owner_id')
                        ->label('المالك')
                        ->relationship('owner', 'name')
                        ->searchable()
                        ->required()
                        ->disabled(fn (callable $get) => filled($get('property_id'))) // readonly إذا تم اختيار العقار
                        ->columnSpan(1),
                        
                    TextInput::make('management_percentage')
                        ->label('النسبة المتفق عليها (%)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(100)
                        ->required()
                        ->suffix('%')
                        ->columnSpan(1),
                ]),
                
                Grid::make(2)->schema([
                    Select::make('payment_schedule')
                        ->label('سداد الدفعات')
                        ->options(PaymentScheduleEnum::class)
                        ->required()
                        ->columnSpan(1),
                        
                    FileUpload::make('contract_file_path')
                        ->label('ملف صورة العقد')
                        ->acceptedFileTypes(['application/pdf'])
                        ->required()
                        ->directory('contracts/property')
                        ->columnSpan(1),
                ]),
                
                Textarea::make('notes')
                    ->label('ملاحظات أخرى')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
    ]);
}
```

### 2.2 PropertyContract Table
```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('title')
                ->label('اسم العقد')
                ->searchable()
                ->sortable(),
                
            TextColumn::make('property.name')
                ->label('العقار')
                ->searchable(),
                
            TextColumn::make('owner.name')
                ->label('المالك')
                ->searchable(),
                
            TextColumn::make('start_date')
                ->label('تاريخ البداية')
                ->date('Y-m-d'),
                
            TextColumn::make('duration_months')
                ->label('مدة العقد')
                ->suffix(' شهر'),
                
            TextColumn::make('management_percentage')
                ->label('نسبة الإدارة')
                ->suffix('%'),
                
            BadgeColumn::make('status')
                ->label('الحالة')
                ->colors([
                    'success' => ContractStatusEnum::ACTIVE->value,
                    'warning' => ContractStatusEnum::DRAFT->value,
                    'danger' => ContractStatusEnum::EXPIRED->value,
                ]),
                
            TextColumn::make('end_date')
                ->label('تاريخ الانتهاء')
                ->getStateUsing(fn ($record) => $record->getEndDate()->format('Y-m-d')),
        ])
        ->filters([
            SelectFilter::make('status')
                ->label('حالة العقد')
                ->options(ContractStatusEnum::class),
                
            SelectFilter::make('owner')
                ->label('المالك')
                ->relationship('owner', 'name'),
                
            SelectFilter::make('property')
                ->label('العقار')
                ->relationship('property', 'name'),
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            
            // Action لتجديد العقد
            Tables\Actions\Action::make('renew')
                ->label('تجديد العقد')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->visible(fn ($record) => $record->status === ContractStatusEnum::ACTIVE)
                ->action(function ($record) {
                    // منطق تجديد العقد
                    $record->update([
                        'start_date' => $record->getEndDate(),
                        'status' => ContractStatusEnum::ACTIVE,
                    ]);
                    
                    Notification::make()
                        ->title('تم تجديد العقد بنجاح')
                        ->success()
                        ->send();
                }),
                
            // Action لتحميل ملف العقد
            Tables\Actions\Action::make('download_contract')
                ->label('تحميل العقد')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn ($record) => Storage::url($record->contract_file_path))
                ->openUrlInNewTab(),
        ]);
}
```

## 3. تبسيط إدارة دفعات التوريد

### 3.1 إنشاء دفعات التوريد تلقائياً
```php
// في PropertyContract Model
protected static function booted()
{
    static::created(function ($contract) {
        if ($contract->status === ContractStatusEnum::ACTIVE) {
            $contract->generateSupplyPayments();
        }
    });
    
    static::updated(function ($contract) {
        if ($contract->wasChanged('status') && $contract->status === ContractStatusEnum::ACTIVE) {
            $contract->generateSupplyPayments();
        }
    });
}

public function generateSupplyPayments(): void
{
    $startDate = $this->start_date;
    $endDate = $this->getEndDate();
    
    $interval = match ($this->payment_schedule) {
        PaymentScheduleEnum::MONTHLY => 1,
        PaymentScheduleEnum::QUARTERLY => 3,
        PaymentScheduleEnum::SEMI_ANNUAL => 6,
        PaymentScheduleEnum::ANNUAL => 12,
    };
    
    $currentDate = $startDate->copy();
    
    while ($currentDate->lessThan($endDate)) {
        SupplyPayment::create([
            'property_contract_id' => $this->id,
            'due_date' => $currentDate->copy(),
            'status' => SupplyPaymentStatus::PENDING,
        ]);
        
        $currentDate->addMonths($interval);
    }
}
```

## 4. التبسيطات المطبقة

### 4.1 إزالة Over-Engineering
- **❌ إزالة**: PropertyContractService معقد
- **❌ إزالة**: ContractManagementService معقد
- **❌ إزالة**: PropertyOwnerContractService منفصل
- **✅ بدلاً منها**: دوال مباشرة في Model

### 4.2 استخدام Filament's Built-in Features
- **File Upload**: استخدام Filament's FileUpload مع PDF validation
- **Date Handling**: استخدام Filament's DatePicker مع format صحيح
- **Enum Support**: استخدام Laravel's Enums مع Filament
- **Reactive Forms**: تحديث المالك تلقائياً عند اختيار العقار

### 4.3 تبسيط Business Logic
```php
// بدلاً من خدمات معقدة، دوال مباشرة في Model
public function canBeRenewed(): bool
{
    return $this->status === ContractStatusEnum::ACTIVE 
        && $this->getEndDate()->diffInDays(now()) <= 30;
}

public function calculateTotalRevenue(): float
{
    return $this->property->units()
        ->whereNotNull('tenant_id')
        ->sum('rent_price');
}
```

## 5. Migration من WordPress

### 5.1 PropertyContract Import مبسط
```php
class PropertyContractImportService
{
    public function importPropertyContracts(): void
    {
        $wpContracts = $this->getWordPressPropertyContracts();
        
        foreach ($wpContracts as $wpContract) {
            PropertyContract::create([
                'title' => $wpContract->post_title,
                'property_id' => $this->mapProperty($wpContract->property_id),
                'owner_id' => $this->mapOwner($wpContract->owner_id),
                'start_date' => Carbon::parse($wpContract->start_date),
                'duration_months' => $wpContract->duration ?? 12,
                'management_percentage' => $wpContract->management_rate ?? 10,
                'payment_schedule' => $this->mapPaymentSchedule($wpContract->payment_schedule),
                'contract_file_path' => $this->migrateFile($wpContract->contract_file),
                'notes' => $wpContract->notes,
                'status' => $this->mapStatus($wpContract->status),
            ]);
        }
    }
    
    private function mapPaymentSchedule($wpSchedule): PaymentScheduleEnum
    {
        return match ($wpSchedule) {
            'شهريا' => PaymentScheduleEnum::MONTHLY,
            'ربع سنوي' => PaymentScheduleEnum::QUARTERLY,
            'نصف سنوي' => PaymentScheduleEnum::SEMI_ANNUAL,
            'سنوي' => PaymentScheduleEnum::ANNUAL,
            default => PaymentScheduleEnum::MONTHLY,
        };
    }
}
```

## 6. الاختبارات المبسطة

### 6.1 اختبارات أساسية فقط
```php
class PropertyContractModelTest extends TestCase
{
    public function test_contract_belongs_to_property_and_owner()
    {
        $property = Property::factory()->create();
        $owner = User::factory()->create();
        $contract = PropertyContract::factory()->create([
            'property_id' => $property->id,
            'owner_id' => $owner->id,
        ]);
        
        $this->assertEquals($property->id, $contract->property->id);
        $this->assertEquals($owner->id, $contract->owner->id);
    }
    
    public function test_end_date_calculation()
    {
        $contract = PropertyContract::factory()->create([
            'start_date' => '2024-01-01',
            'duration_months' => 12,
        ]);
        
        $this->assertEquals('2025-01-01', $contract->getEndDate()->format('Y-m-d'));
    }
    
    public function test_commission_calculation()
    {
        $contract = PropertyContract::factory()->create([
            'management_percentage' => 10,
        ]);
        
        $commission = $contract->calculateMonthlyCommission(5000);
        $this->assertEquals(500, $commission);
    }
}
```

### 6.2 Feature Tests للـ Filament Resource
```php
class PropertyContractResourceTest extends TestCase
{
    public function test_contract_creation_workflow()
    {
        // اختبار إنشاء عقد جديد عبر Filament
    }
    
    public function test_file_upload_validation()
    {
        // اختبار رفع ملف PDF فقط
    }
    
    public function test_automatic_owner_selection()
    {
        // اختبار تحديد المالك تلقائياً عند اختيار العقار
    }
}
```

## 7. حالات العقد والتجديد

### 7.1 Contract Lifecycle Management
```php
// Commands للمهام المجدولة
class CheckContractExpiryCommand extends Command
{
    public function handle()
    {
        // العقود المنتهية
        PropertyContract::where('status', ContractStatusEnum::ACTIVE)
            ->whereDate('end_date', '<=', now())
            ->update(['status' => ContractStatusEnum::EXPIRED]);
            
        // إشعارات التجديد (30 يوم قبل الانتهاء)
        $expiringSoon = PropertyContract::where('status', ContractStatusEnum::ACTIVE)
            ->whereBetween('end_date', [now(), now()->addDays(30)])
            ->get();
            
        foreach ($expiringSoon as $contract) {
            // إرسال إشعار التجديد
        }
    }
}
```

## الملخص النهائي

تم تبسيط نظام عقود الملاك ليستخدم:
1. **Filament's built-in components** لجميع حقول النموذج
2. **Laravel Enums** لحالات العقد وجداول الدفع
3. **Automatic file handling** مع Filament's FileUpload
4. **Reactive forms** لتحديث المالك تلقائياً
5. **Built-in validation** بدلاً من validation معقد
6. **Model methods** بدلاً من service classes معقدة
7. **Scheduled commands** لإدارة دورة حياة العقود

هذا النهج يحافظ على جميع الوظائف المطلوبة من ACF مع تبسيط كبير في التعقيد.