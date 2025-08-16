# Expense Model Tasks - Al-Hiaa Real Estate Management System
**Target Stack:** Laravel 12 & Filament 4

## المبادئ الأساسية للتحسين
- ✅ استخدام Filament's conditional fields للمصاريف العامة والخاصة
- ✅ تبسيط Expense management مع Property-Unit relationship  
- ✅ استخدام Filament's file upload للفواتير المتعددة
- ✅ دعم أنواع مصاريف متعددة (صيانة، رسوم حكومية، مرافق، وغيرها)

## 1. نموذج مصاريف العقار المبسط

### 1.1 Expense Model
```php
class Expense extends Model
{
    protected $fillable = [
        'title', 'expense_type', 'property_id', 'unit_id', 
        'amount', 'expense_date', 'description', 'category',
        'invoice_file', 'receipt_file', 'payment_method',
        'status', 'paid_to', 'payment_date'
    ];
    
    protected $casts = [
        'maintenance_date' => 'date',
        'completion_date' => 'date',
        'total_cost' => 'decimal:2',
        'maintenance_type' => MaintenanceType::class,
        'status' => MaintenanceStatus::class,
        'priority' => MaintenancePriority::class,
    ];
    
    // العلاقات الأساسية
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
    
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
    
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    
    // دوال مبسطة
    public function isGeneralMaintenance(): bool
    {
        return $this->maintenance_type === MaintenanceType::GENERAL;
    }
    
    public function isSpecialMaintenance(): bool
    {
        return $this->maintenance_type === MaintenanceType::SPECIAL;
    }
    
    public function getTotalInvoiceAmount(): float
    {
        return $this->total_cost;
    }
    
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => MaintenanceStatus::COMPLETED,
            'completion_date' => now(),
        ]);
    }
}
```

### 1.2 Maintenance Enums
```php
enum MaintenanceType: string
{
    case GENERAL = 'general_maintenance';
    case SPECIAL = 'special_maintenance';
    
    public function getLabel(): string
    {
        return match($this) {
            self::GENERAL => 'عملية عامة',
            self::SPECIAL => 'عملية خاصة',
        };
    }
}

enum MaintenanceStatus: string
{
    case REPORTED = 'reported';
    case SCHEDULED = 'scheduled';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    
    public function getLabel(): string
    {
        return match($this) {
            self::REPORTED => 'مبلغ عنها',
            self::SCHEDULED => 'مجدولة',
            self::IN_PROGRESS => 'قيد التنفيذ',
            self::COMPLETED => 'مكتملة',
            self::CANCELLED => 'ملغية',
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::REPORTED => 'info',
            self::SCHEDULED => 'warning',
            self::IN_PROGRESS => 'primary',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
        };
    }
}

enum MaintenancePriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';
    
    public function getLabel(): string
    {
        return match($this) {
            self::LOW => 'منخفضة',
            self::MEDIUM => 'متوسطة',
            self::HIGH => 'عالية',
            self::URGENT => 'عاجلة',
        };
    }
}
```

## 2. Filament Resource مع المنطق الشرطي

### 2.1 PropertyRepair Resource مع Filament 4 Syntax
```php
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Get;

public static function form(Schema $schema): Schema
{
    return $schema->schema([
        Section::make('بيانات الصيانة')
            ->schema([
                TextInput::make('title')
                    ->label('اسم الصيانة')
                    ->required()
                    ->columnSpanFull(),
                    
                Grid::make(2)->schema([
                    Select::make('maintenance_type')
                        ->label('نوع الصيانة')
                        ->options(MaintenanceType::class)
                        ->required()
                        ->reactive() // مفتاح الحقول الشرطية
                        ->columnSpan(1),
                        
                    Select::make('priority')
                        ->label('الأولوية')
                        ->options(MaintenancePriority::class)
                        ->default(MaintenancePriority::MEDIUM)
                        ->required()
                        ->columnSpan(1),
                ]),
                
                Grid::make(2)->schema([
                    Select::make('property_id')
                        ->label('صيانة العقار')
                        ->relationship('property', 'name')
                        ->searchable()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $state) {
                            // إعادة تعيين الوحدة عند تغيير العقار
                            $set('unit_id', null);
                        })
                        ->columnSpan(1),
                        
                    Select::make('unit_id')
                        ->label('صيانة الوحدة')
                        ->options(function (callable $get) {
                            $propertyId = $get('property_id');
                            if (!$propertyId) return [];
                            
                            return Unit::where('property_id', $propertyId)
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->visible(fn (Get $get) => $get('maintenance_type') === MaintenanceType::SPECIAL->value)
                        ->required(fn (Get $get) => $get('maintenance_type') === MaintenanceType::SPECIAL->value)
                        ->columnSpan(1),
                ]),
            ]),
            
        Section::make('التكلفة والتواريخ')
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('total_cost')
                        ->label('إجمالي تكلفة الصيانة')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->prefix('ر.س')
                        ->columnSpan(1),
                        
                    DatePicker::make('maintenance_date')
                        ->label('تاريخ الصيانة')
                        ->displayFormat('d/m/Y') // كما هو مطلوب في ACF
                        ->columnSpan(1),
                ]),
                
                Grid::make(2)->schema([
                    Select::make('assigned_to')
                        ->label('مسند إلى')
                        ->relationship('assignedTo', 'name')
                        ->searchable()
                        ->columnSpan(1),
                        
                    Select::make('status')
                        ->label('حالة الصيانة')
                        ->options(MaintenanceStatus::class)
                        ->default(MaintenanceStatus::REPORTED)
                        ->required()
                        ->columnSpan(1),
                ]),
            ]),
            
        Section::make('الفواتير والوثائق')
            ->schema([
                Grid::make(2)->schema([
                    FileUpload::make('purchase_invoice_file')
                        ->label('ملف صورة فاتورة المشتريات')
                        ->acceptedFileTypes(['application/pdf'])
                        ->directory('maintenance/invoices')
                        ->columnSpan(1),
                        
                    FileUpload::make('labor_invoice_file')
                        ->label('ملف صورة فاتورة عمل اليد')
                        ->acceptedFileTypes(['application/pdf'])
                        ->directory('maintenance/invoices')
                        ->columnSpan(1),
                ]),
            ]),
            
        Section::make('وصف الصيانة')
            ->schema([
                Textarea::make('description')
                    ->label('وصف الصيانة')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
            ]),
    ]);
}
```

### 2.2 PropertyRepair Table مع Advanced Filtering
```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('title')
                ->label('اسم الصيانة')
                ->searchable()
                ->sortable(),
                
            BadgeColumn::make('maintenance_type')
                ->label('النوع')
                ->colors([
                    'info' => MaintenanceType::GENERAL->value,
                    'warning' => MaintenanceType::SPECIAL->value,
                ]),
                
            TextColumn::make('property.name')
                ->label('العقار')
                ->searchable(),
                
            TextColumn::make('unit.name')
                ->label('الوحدة')
                ->placeholder('عامة'),
                
            TextColumn::make('total_cost')
                ->label('التكلفة')
                ->money('SAR')
                ->sortable(),
                
            BadgeColumn::make('priority')
                ->label('الأولوية')
                ->colors([
                    'secondary' => MaintenancePriority::LOW->value,
                    'warning' => MaintenancePriority::MEDIUM->value,
                    'danger' => MaintenancePriority::HIGH->value,
                    'primary' => MaintenancePriority::URGENT->value,
                ]),
                
            BadgeColumn::make('status')
                ->label('الحالة')
                ->colors([
                    'info' => MaintenanceStatus::REPORTED->value,
                    'warning' => MaintenanceStatus::SCHEDULED->value,
                    'primary' => MaintenanceStatus::IN_PROGRESS->value,
                    'success' => MaintenanceStatus::COMPLETED->value,
                    'danger' => MaintenanceStatus::CANCELLED->value,
                ]),
                
            TextColumn::make('maintenance_date')
                ->label('التاريخ')
                ->date('d/m/Y'),
                
            TextColumn::make('assignedTo.name')
                ->label('مسند إلى'),
        ])
        ->filters([
            SelectFilter::make('maintenance_type')
                ->label('نوع الصيانة')
                ->options(MaintenanceType::class),
                
            SelectFilter::make('status')
                ->label('الحالة')
                ->options(MaintenanceStatus::class),
                
            SelectFilter::make('priority')
                ->label('الأولوية')
                ->options(MaintenancePriority::class),
                
            SelectFilter::make('property')
                ->label('العقار')
                ->relationship('property', 'name'),
                
            Filter::make('cost_range')
                ->form([
                    Grid::make(2)->schema([
                        TextInput::make('cost_from')
                            ->label('التكلفة من')
                            ->numeric(),
                        TextInput::make('cost_to')
                            ->label('التكلفة إلى')
                            ->numeric(),
                    ])
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['cost_from'], fn ($q, $cost) => $q->where('total_cost', '>=', $cost))
                        ->when($data['cost_to'], fn ($q, $cost) => $q->where('total_cost', '<=', $cost));
                }),
                
            DateFilter::make('maintenance_date')
                ->label('تاريخ الصيانة'),
        ])
        ->actions([
            // Action لتحديث الحالة
            Tables\Actions\Action::make('update_status')
                ->label('تحديث الحالة')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->form([
                    Select::make('status')
                        ->label('الحالة الجديدة')
                        ->options(MaintenanceStatus::class)
                        ->required(),
                ])
                ->action(function ($record, array $data) {
                    $record->update(['status' => $data['status']]);
                    
                    if ($data['status'] === MaintenanceStatus::COMPLETED->value) {
                        $record->markAsCompleted();
                    }
                    
                    Notification::make()
                        ->title('تم تحديث حالة الصيانة')
                        ->success()
                        ->send();
                }),
                
            // Action لتحميل الفواتير
            Tables\Actions\Action::make('download_invoices')
                ->label('تحميل الفواتير')
                ->icon('heroicon-o-document-arrow-down')
                ->color('secondary')
                ->visible(fn ($record) => $record->purchase_invoice_file || $record->labor_invoice_file)
                ->action(function ($record) {
                    // منطق تحميل الفواتير
                    $files = collect([
                        $record->purchase_invoice_file,
                        $record->labor_invoice_file
                    ])->filter();
                    
                    // إنشاء ZIP file مع الفواتير
                    return $this->downloadMultipleFiles($files, "invoices-{$record->id}.zip");
                }),
                
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([
            // Bulk Action لتحديث الحالة
            Tables\Actions\BulkAction::make('bulk_update_status')
                ->label('تحديث حالة متعددة')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->form([
                    Select::make('status')
                        ->label('الحالة الجديدة')
                        ->options(MaintenanceStatus::class)
                        ->required(),
                ])
                ->action(function (Collection $records, array $data) {
                    $records->each(function ($record) use ($data) {
                        $record->update(['status' => $data['status']]);
                    });
                    
                    Notification::make()
                        ->title('تم تحديث حالة الصيانات المحددة')
                        ->success()
                        ->send();
                }),
        ]);
}
```

## 3. تبسيط Maintenance Workflow

### 3.1 إزالة Services المعقدة
- **❌ إزالة**: MaintenanceService معقد
- **❌ إزالة**: MaintenanceScheduler معقد
- **❌ إزالة**: VendorManagement معقد
- **❌ إزالة**: WarrantyClaimProcessor معقد
- **✅ بدلاً منها**: Model methods بسيطة

### 3.2 تبسيط Cost Calculation
```php
// في PropertyRepair Model
public function calculateImpactOnSupplyPayment(): void
{
    // تأثير تكلفة الصيانة على دفعات التوريد
    $currentMonth = now()->format('Y-m');
    
    // البحث عن دفعة التوريد للشهر الحالي
    $supplyPayment = SupplyPayment::where('property_contract_id', $this->property->activeContract->id)
        ->whereYear('due_date', now()->year)
        ->whereMonth('due_date', now()->month)
        ->first();
        
    if ($supplyPayment && $supplyPayment->status !== SupplyPaymentStatus::COLLECTED) {
        // إضافة تكلفة الصيانة للخصومات
        $newDeductions = $supplyPayment->deductions + $this->total_cost;
        $newNetAmount = $supplyPayment->gross_amount - $newDeductions;
        
        $supplyPayment->update([
            'deductions' => $newDeductions,
            'net_amount' => $newNetAmount,
        ]);
    }
}
```

### 3.3 استخدام Model Events
```php
// في PropertyRepair Model
protected static function booted()
{
    static::created(function ($repair) {
        // تحديث تكلفة الصيانة في دفعات التوريد
        $repair->calculateImpactOnSupplyPayment();
        
        // إشعار المالك بالصيانة الجديدة
        if ($repair->property->owner) {
            Notification::make()
                ->title('صيانة جديدة لعقارك')
                ->info()
                ->sendToDatabase($repair->property->owner);
        }
    });
    
    static::updated(function ($repair) {
        if ($repair->wasChanged('status') && $repair->status === MaintenanceStatus::COMPLETED) {
            // إشعار إكمال الصيانة
            Notification::make()
                ->title('تم إكمال الصيانة')
                ->success()
                ->send();
        }
    });
}
```

## 4. التبسيطات المطبقة

### 4.1 إزالة Over-Engineering
- **❌ إزالة**: MaintenanceService مع workflow معقد
- **❌ إزالة**: VendorAssignment معقد
- **❌ إزالة**: PreventiveMaintenanceScheduler
- **❌ إزالة**: MaintenanceApprovalWorkflow
- **✅ بدلاً منها**: Model methods بسيطة و Filament actions

### 4.2 استخدام Filament's Advanced Features
- **Conditional Unit Field**: إظهار الوحدة فقط للصيانة الخاصة
- **Dynamic Unit Loading**: تحديث الوحدات عند اختيار العقار
- **Multiple File Uploads**: رفع فواتير متعددة
- **Date Localization**: تاريخ بصيغة عربية

### 4.3 تبسيط File Management
```php
// في PropertyRepair Model
public function getInvoiceFiles(): Collection
{
    return collect([
        'purchase' => $this->purchase_invoice_file,
        'labor' => $this->labor_invoice_file,
    ])->filter();
}

public function getTotalInvoiceCount(): int
{
    return $this->getInvoiceFiles()->count();
}
```

## 5. Migration من WordPress

### 5.1 PropertyRepair Import مبسط
```php
class PropertyRepairImportService
{
    public function importPropertyRepairs(): void
    {
        $wpRepairs = $this->getWordPressRepairs();
        
        foreach ($wpRepairs as $wpRepair) {
            PropertyRepair::create([
                'title' => $wpRepair->post_title,
                'maintenance_type' => $this->mapMaintenanceType($wpRepair->type),
                'property_id' => $this->mapProperty($wpRepair->property_id),
                'unit_id' => $this->mapUnit($wpRepair->unit_id),
                'total_cost' => $wpRepair->total_cost,
                'maintenance_date' => Carbon::parse($wpRepair->maintenance_date),
                'description' => $wpRepair->post_content,
                'purchase_invoice_file' => $this->migrateFile($wpRepair->purchase_invoice),
                'labor_invoice_file' => $this->migrateFile($wpRepair->labor_invoice),
                'status' => MaintenanceStatus::COMPLETED, // افتراض أن الصيانات القديمة مكتملة
            ]);
        }
    }
    
    private function mapMaintenanceType($wpType): MaintenanceType
    {
        return match ($wpType) {
            'special_maintenance' => MaintenanceType::SPECIAL,
            default => MaintenanceType::GENERAL,
        };
    }
}
```

## 6. الاختبارات المبسطة

### 6.1 اختبارات أساسية فقط
```php
class PropertyRepairModelTest extends TestCase
{
    public function test_repair_belongs_to_property()
    {
        $property = Property::factory()->create();
        $repair = PropertyRepair::factory()->create([
            'property_id' => $property->id
        ]);
        
        $this->assertEquals($property->id, $repair->property->id);
    }
    
    public function test_special_maintenance_requires_unit()
    {
        $repair = PropertyRepair::factory()->create([
            'maintenance_type' => MaintenanceType::SPECIAL,
            'unit_id' => null,
        ]);
        
        // يجب أن يفشل validation
        $this->assertFalse($repair->isValid());
    }
    
    public function test_maintenance_cost_impact_on_supply_payment()
    {
        $property = Property::factory()->create();
        $repair = PropertyRepair::factory()->create([
            'property_id' => $property->id,
            'total_cost' => 1000,
        ]);
        
        $repair->calculateImpactOnSupplyPayment();
        
        // التحقق من تأثير التكلفة على دفعة التوريد
        $supplyPayment = SupplyPayment::where('property_contract_id', $property->activeContract->id)
            ->first();
            
        $this->assertNotNull($supplyPayment);
        $this->assertEquals(1000, $supplyPayment->deductions);
    }
}
```

### 6.2 Feature Tests للمنطق الشرطي
```php
class PropertyRepairConditionalLogicTest extends TestCase
{
    public function test_unit_field_visibility_based_on_type()
    {
        // اختبار إظهار حقل الوحدة للصيانة الخاصة فقط
    }
    
    public function test_property_unit_relationship_filtering()
    {
        // اختبار فلترة الوحدات حسب العقار المختار
    }
    
    public function test_multiple_invoice_upload()
    {
        // اختبار رفع فواتير متعددة
    }
}
```

## 7. Reporting والإحصائيات

### 7.1 Maintenance Reports
```php
class MaintenanceReportWidget extends Widget
{
    protected static string $view = 'filament.widgets.maintenance-report';
    
    public function getMaintenanceStats()
    {
        return [
            'total_cost_month' => PropertyRepair::whereBetween('maintenance_date', 
                [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('total_cost'),
                
            'pending_count' => PropertyRepair::where('status', MaintenanceStatus::REPORTED)
                ->count(),
                
            'urgent_count' => PropertyRepair::where('priority', MaintenancePriority::URGENT)
                ->where('status', '!=', MaintenanceStatus::COMPLETED)
                ->count(),
        ];
    }
}
```

### 7.2 Cost Analysis
```php
// في PropertyRepair Model
public static function getMonthlyCostByProperty(): Collection
{
    return static::selectRaw('property_id, SUM(total_cost) as total_cost')
        ->whereBetween('maintenance_date', [now()->startOfMonth(), now()->endOfMonth()])
        ->groupBy('property_id')
        ->with('property')
        ->get();
}

public static function getMaintenanceTypeDistribution(): Collection
{
    return static::selectRaw('maintenance_type, COUNT(*) as count, SUM(total_cost) as total_cost')
        ->groupBy('maintenance_type')
        ->get();
}
```

## الملخص النهائي

تم تبسيط نظام صيانة العقارات ليستخدم:
1. **Filament's conditional fields** للصيانة العامة والخاصة
2. **Dynamic property-unit relationship** مع reactive forms
3. **Multiple file uploads** للفواتير المختلفة
4. **Laravel Enums** للأنواع والحالات والأولويات
5. **Model events** لتحديث دفعات التوريد تلقائياً
6. **Built-in Filament actions** للعمليات السريعة
7. **Simplified cost calculation** بدلاً من services معقدة

هذا النهج يحافظ على جميع الوظائف المطلوبة من ACF مع تبسيط كبير في التطبيق والصيانة.