# SupplyPayment Model Tasks - Al-Hiaa Real Estate Management System
**Target Stack:** Laravel 12 & Filament 4

## المبادئ الأساسية للتحسين
- ✅ استخدام Filament's conditional fields مع منطق شرطي معقد
- ✅ تبسيط Supply payment workflow مع built-in components
- ✅ استخدام Toggle مع تسميات عربية مخصصة
- ✅ استخدام Placeholder للرسائل الإعلامية

## 1. نموذج دفعة التوريد المبسط

### 1.1 SupplyPayment Model
```php
class SupplyPayment extends Model
{
    protected $fillable = [
        'title', 'property_contract_id', 'amount', 'status',
        'due_date', 'supply_date', 'acknowledgment_commitment',
        'gross_amount', 'deductions', 'net_amount',
        'bank_transfer_reference', 'approval_status', 'approved_by'
    ];
    
    protected $casts = [
        'due_date' => 'date',
        'supply_date' => 'date',
        'acknowledgment_commitment' => 'boolean',
        'amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'deductions' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'status' => SupplyPaymentStatus::class,
    ];
    
    // العلاقات الأساسية
    public function propertyContract(): BelongsTo
    {
        return $this->belongsTo(PropertyContract::class);
    }
    
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
    
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    
    // دوال مبسطة
    public function calculateNetAmount(): float
    {
        return $this->gross_amount - $this->deductions;
    }
    
    public function isReadyForSupply(): bool
    {
        return $this->status === SupplyPaymentStatus::WORTH_COLLECTING 
            && $this->due_date <= now();
    }
    
    public function markAsSupplied(): void
    {
        $this->update([
            'status' => SupplyPaymentStatus::COLLECTED,
            'supply_date' => now(),
        ]);
    }
    
    public function requiresApproval(): bool
    {
        return $this->amount > 10000; // مثال: المبالغ الكبيرة تحتاج موافقة
    }
}
```

### 1.2 Supply Payment Status Enum
```php
enum SupplyPaymentStatus: string
{
    case PENDING = 'pending';
    case WORTH_COLLECTING = 'worth_collecting';
    case COLLECTED = 'collected';
    
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'قيد الانتظار',
            self::WORTH_COLLECTING => 'تستحق التوريد',
            self::COLLECTED => 'تم التوريد',
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::PENDING => 'info',
            self::WORTH_COLLECTING => 'warning',
            self::COLLECTED => 'success',
        };
    }
}
```

## 2. Filament Resource مع الحقول الشرطية المعقدة

### 2.1 SupplyPayment Resource مع Filament 4 Syntax
```php
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;

public static function form(Schema $schema): Schema
{
    return $schema->schema([
        Section::make('بيانات الدفعة الأساسية')
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('title')
                        ->label('اسم دفعة التوريد')
                        ->required()
                        ->columnSpan(1),
                        
                    Select::make('property_contract_id')
                        ->label('العقد')
                        ->relationship('propertyContract', 'title')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn (?Model $record) => $record !== null) // Read-only بعد الإنشاء
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $state) {
                            // تحديث معلومات إضافية عند اختيار العقد
                            $contract = PropertyContract::find($state);
                            if ($contract) {
                                $set('property_id', $contract->property_id);
                                $set('owner_id', $contract->owner_id);
                            }
                        })
                        ->columnSpan(1),
                ]),
                
                Grid::make(2)->schema([
                    TextInput::make('amount')
                        ->label('القيمة المالية')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->prefix('ر.س')
                        ->columnSpan(1),
                        
                    Radio::make('status')
                        ->label('حالة التوريد')
                        ->required()
                        ->options(SupplyPaymentStatus::class)
                        ->inline()
                        ->reactive() // مفتاح الحقول الشرطية
                        ->columnSpan(1),
                ]),
            ]),
            
        Section::make('تواريخ الدفعة')
            ->schema([
                Grid::make(2)->schema([
                    DatePicker::make('due_date')
                        ->label('تاريخ الاستحقاق')
                        ->required()
                        ->displayFormat('Y-m-d')
                        ->disabled() // Read-only كما هو مطلوب في النظام القديم
                        ->visible(fn (Get $get) => in_array($get('status'), [
                            SupplyPaymentStatus::PENDING->value,
                            SupplyPaymentStatus::WORTH_COLLECTING->value
                        ]))
                        ->columnSpan(1),
                        
                    DatePicker::make('supply_date')
                        ->label('تاريخ التوريد')
                        ->displayFormat('Y-m-d')
                        ->visible(fn (Get $get) => $get('status') === SupplyPaymentStatus::COLLECTED->value)
                        ->required(fn (Get $get) => $get('status') === SupplyPaymentStatus::COLLECTED->value)
                        ->columnSpan(1),
                ]),
            ]),
            
        Section::make('إقرار ما بعد التوريد')
            ->schema([
                Placeholder::make('acknowledgment_message')
                    ->label('إقرار ما بعد التوريد')
                    ->content('تم تسليم المبلغ المستحق للمالك حسب الاتفاقية')
                    ->visible(fn (Get $get) => $get('status') === SupplyPaymentStatus::COLLECTED->value),
                    
                Toggle::make('acknowledgment_commitment')
                    ->label('إقرار')
                    ->onLabel('موافق')
                    ->offLabel('غير موافق')
                    ->visible(fn (Get $get) => $get('status') === SupplyPaymentStatus::COLLECTED->value)
                    ->required(fn (Get $get) => $get('status') === SupplyPaymentStatus::COLLECTED->value)
                    ->columnSpanFull(),
            ]),
            
        Section::make('التفاصيل المالية المتقدمة')
            ->schema([
                Grid::make(3)->schema([
                    TextInput::make('gross_amount')
                        ->label('المبلغ الإجمالي')
                        ->numeric()
                        ->prefix('ر.س')
                        ->columnSpan(1),
                        
                    TextInput::make('deductions')
                        ->label('الخصومات')
                        ->numeric()
                        ->prefix('ر.س')
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, callable $get, $state) {
                            // حساب المبلغ الصافي تلقائياً
                            $gross = $get('gross_amount') ?? 0;
                            $net = $gross - ($state ?? 0);
                            $set('net_amount', $net);
                        })
                        ->columnSpan(1),
                        
                    TextInput::make('net_amount')
                        ->label('المبلغ الصافي')
                        ->numeric()
                        ->prefix('ر.س')
                        ->disabled()
                        ->columnSpan(1),
                ]),
                
                TextInput::make('bank_transfer_reference')
                    ->label('مرجع التحويل البنكي')
                    ->visible(fn (Get $get) => $get('status') === SupplyPaymentStatus::COLLECTED->value)
                    ->columnSpanFull(),
            ]),
    ]);
}
```

### 2.2 SupplyPayment Table مع Actions متقدمة
```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('title')
                ->label('اسم الدفعة')
                ->searchable()
                ->sortable(),
                
            TextColumn::make('propertyContract.title')
                ->label('العقد')
                ->searchable(),
                
            TextColumn::make('propertyContract.property.name')
                ->label('العقار')
                ->searchable(),
                
            TextColumn::make('amount')
                ->label('المبلغ')
                ->money('SAR')
                ->sortable(),
                
            TextColumn::make('net_amount')
                ->label('المبلغ الصافي')
                ->money('SAR')
                ->sortable(),
                
            BadgeColumn::make('status')
                ->label('الحالة')
                ->colors([
                    'info' => SupplyPaymentStatus::PENDING->value,
                    'warning' => SupplyPaymentStatus::WORTH_COLLECTING->value,
                    'success' => SupplyPaymentStatus::COLLECTED->value,
                ]),
                
            TextColumn::make('due_date')
                ->label('تاريخ الاستحقاق')
                ->date('Y-m-d'),
                
            TextColumn::make('supply_date')
                ->label('تاريخ التوريد')
                ->date('Y-m-d')
                ->placeholder('لم يتم التوريد'),
                
            IconColumn::make('acknowledgment_commitment')
                ->label('الإقرار')
                ->boolean()
                ->trueIcon('heroicon-o-check-circle')
                ->falseIcon('heroicon-o-x-circle'),
        ])
        ->filters([
            SelectFilter::make('status')
                ->label('حالة التوريد')
                ->options(SupplyPaymentStatus::class),
                
            Filter::make('ready_for_supply')
                ->label('جاهزة للتوريد')
                ->query(fn (Builder $query): Builder => 
                    $query->where('status', SupplyPaymentStatus::WORTH_COLLECTING)
                        ->where('due_date', '<=', now())
                ),
                
            SelectFilter::make('property_contract')
                ->label('العقد')
                ->relationship('propertyContract', 'title'),
                
            DateFilter::make('due_date')
                ->label('تاريخ الاستحقاق'),
        ])
        ->actions([
            // Action للتوريد السريع
            Tables\Actions\Action::make('supply')
                ->label('توريد')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn ($record) => $record->isReadyForSupply())
                ->form([
                    TextInput::make('bank_transfer_reference')
                        ->label('مرجع التحويل البنكي')
                        ->required(),
                    Toggle::make('acknowledgment_commitment')
                        ->label('إقرار التوريد')
                        ->onLabel('موافق')
                        ->offLabel('غير موافق')
                        ->required(),
                ])
                ->action(function ($record, array $data) {
                    $record->update([
                        'status' => SupplyPaymentStatus::COLLECTED,
                        'supply_date' => now(),
                        'bank_transfer_reference' => $data['bank_transfer_reference'],
                        'acknowledgment_commitment' => $data['acknowledgment_commitment'],
                    ]);
                    
                    Notification::make()
                        ->title('تم توريد الدفعة بنجاح')
                        ->success()
                        ->send();
                }),
                
            // Action لإنشاء كشف حساب
            Tables\Actions\Action::make('generate_statement')
                ->label('كشف حساب')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->action(function ($record) {
                    // منطق إنشاء كشف الحساب
                    return response()->streamDownload(function () use ($record) {
                        echo $this->generateStatement($record);
                    }, "statement-{$record->id}.pdf");
                }),
                
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([
            // Bulk Action للتوريد المتعدد
            Tables\Actions\BulkAction::make('bulk_supply')
                ->label('توريد متعدد')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->action(function (Collection $records) {
                    $supplied = 0;
                    
                    $records->each(function ($record) use (&$supplied) {
                        if ($record->isReadyForSupply()) {
                            $record->markAsSupplied();
                            $supplied++;
                        }
                    });
                    
                    Notification::make()
                        ->title("تم توريد {$supplied} دفعة")
                        ->success()
                        ->send();
                }),
        ]);
}
```

## 3. تبسيط Supply Workflow

### 3.1 إزالة Services المعقدة
- **❌ إزالة**: SupplyService معقد
- **❌ إزالة**: OwnerPaymentCalculator معقد
- **❌ إزالة**: DeductionProcessor معقد
- **❌ إزالة**: BankTransferService معقد
- **✅ بدلاً منها**: Model methods بسيطة

### 3.2 تبسيط Deduction Calculation
```php
// في SupplyPayment Model
public function calculateDeductions(): float
{
    $totalDeductions = 0;
    
    // خصم نسبة الإدارة
    $managementFee = $this->gross_amount * ($this->propertyContract->management_percentage / 100);
    $totalDeductions += $managementFee;
    
    // خصم تكاليف الصيانة
    $maintenanceCosts = $this->getMonthlyMaintenanceCosts();
    $totalDeductions += $maintenanceCosts;
    
    return $totalDeductions;
}

private function getMonthlyMaintenanceCosts(): float
{
    return PropertyRepair::where('property_id', $this->property_id)
        ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
        ->sum('cost');
}
```

## 4. التبسيطات المطبقة

### 4.1 إزالة Over-Engineering
- **❌ إزالة**: SupplyService مع calculations معقدة
- **❌ إزالة**: ApprovalWorkflow معقد
- **❌ إزالة**: BankTransferProcessor
- **❌ إزالة**: SupplyStatementGenerator معقد
- **✅ بدلاً منها**: Model methods بسيطة و Filament actions

### 4.2 استخدام Filament's Advanced Features
- **Conditional Fields**: حقول مختلفة حسب الحالة
- **Read-only Fields**: حقول للقراءة فقط بعد الإنشاء
- **Custom Arabic Toggle**: Toggle مع تسميات عربية
- **Placeholder Messages**: رسائل إعلامية
- **Reactive Calculations**: حساب المبلغ الصافي تلقائياً

### 4.3 تبسيط Approval Process
```php
// في SupplyPayment Model
public function submitForApproval(): void
{
    if ($this->requiresApproval()) {
        $this->update(['approval_status' => 'pending']);
        
        // إشعار المدير
        Notification::make()
            ->title('دفعة تحتاج موافقة')
            ->info()
            ->sendToDatabase(User::role('manager')->get());
    } else {
        $this->update(['approval_status' => 'auto_approved']);
    }
}
```

## 5. Migration من WordPress

### 5.1 SupplyPayment Import مبسط
```php
class SupplyPaymentImportService
{
    public function importSupplyPayments(): void
    {
        $wpPayments = $this->getWordPressSupplyPayments();
        
        foreach ($wpPayments as $wpPayment) {
            SupplyPayment::create([
                'title' => $wpPayment->post_title,
                'property_contract_id' => $this->mapContract($wpPayment->contract_id),
                'amount' => $wpPayment->amount,
                'status' => $this->mapStatus($wpPayment->status),
                'due_date' => Carbon::parse($wpPayment->due_date),
                'supply_date' => $wpPayment->supply_date ? Carbon::parse($wpPayment->supply_date) : null,
                'acknowledgment_commitment' => $wpPayment->acknowledgment === 'موافق',
                'gross_amount' => $wpPayment->gross_amount,
                'deductions' => $wpPayment->deductions,
                'net_amount' => $wpPayment->net_amount,
            ]);
        }
    }
    
    private function mapStatus($wpStatus): SupplyPaymentStatus
    {
        return match ($wpStatus) {
            'collected' => SupplyPaymentStatus::COLLECTED,
            'worth_collecting' => SupplyPaymentStatus::WORTH_COLLECTING,
            default => SupplyPaymentStatus::PENDING,
        };
    }
}
```

## 6. الاختبارات المبسطة

### 6.1 اختبارات أساسية فقط
```php
class SupplyPaymentModelTest extends TestCase
{
    public function test_payment_belongs_to_property_contract()
    {
        $contract = PropertyContract::factory()->create();
        $payment = SupplyPayment::factory()->create([
            'property_contract_id' => $contract->id
        ]);
        
        $this->assertEquals($contract->id, $payment->propertyContract->id);
    }
    
    public function test_net_amount_calculation()
    {
        $payment = SupplyPayment::factory()->create([
            'gross_amount' => 5000,
            'deductions' => 500,
        ]);
        
        $this->assertEquals(4500, $payment->calculateNetAmount());
    }
    
    public function test_supply_workflow()
    {
        $payment = SupplyPayment::factory()->create([
            'status' => SupplyPaymentStatus::WORTH_COLLECTING,
            'due_date' => now()->subDay(),
        ]);
        
        $this->assertTrue($payment->isReadyForSupply());
        
        $payment->markAsSupplied();
        
        $this->assertEquals(SupplyPaymentStatus::COLLECTED, $payment->status);
        $this->assertNotNull($payment->supply_date);
    }
}
```

### 6.2 Feature Tests للحقول الشرطية
```php
class SupplyPaymentConditionalFieldsTest extends TestCase
{
    public function test_conditional_field_visibility()
    {
        // اختبار إظهار حقول التوريد عند اختيار "تم التوريد"
        // اختبار إظهار حقل الاستحقاق للحالات المناسبة
    }
    
    public function test_readonly_fields_after_creation()
    {
        // اختبار أن حقل العقد يصبح read-only بعد الإنشاء
    }
    
    public function test_acknowledgment_toggle_arabic_labels()
    {
        // اختبار التسميات العربية للـ Toggle
    }
}
```

## 7. Scheduled Tasks والتقارير

### 7.1 إنشاء دفعات التوريد تلقائياً
```php
class GenerateSupplyPaymentsCommand extends Command
{
    public function handle()
    {
        $activeContracts = PropertyContract::where('status', 'active')->get();
        
        foreach ($activeContracts as $contract) {
            // التحقق من وجود دفعات للشهر الحالي
            $existingPayment = SupplyPayment::where('property_contract_id', $contract->id)
                ->whereBetween('due_date', [now()->startOfMonth(), now()->endOfMonth()])
                ->exists();
                
            if (!$existingPayment) {
                // حساب إجمالي الإيرادات للعقار
                $grossRevenue = $this->calculateMonthlyRevenue($contract->property);
                
                // حساب الخصومات
                $deductions = $this->calculateDeductions($contract);
                
                SupplyPayment::create([
                    'title' => "دفعة " . now()->format('Y-m') . " - " . $contract->property->name,
                    'property_contract_id' => $contract->id,
                    'gross_amount' => $grossRevenue,
                    'deductions' => $deductions,
                    'net_amount' => $grossRevenue - $deductions,
                    'due_date' => now()->day(10), // اليوم 10 من كل شهر
                    'status' => $grossRevenue > 0 ? SupplyPaymentStatus::WORTH_COLLECTING : SupplyPaymentStatus::PENDING,
                ]);
            }
        }
        
        $this->info('تم إنشاء دفعات التوريد للشهر الحالي');
    }
}
```

## 8. Widgets للمتابعة

### 8.1 Supply Payments Dashboard Widget
```php
class SupplyPaymentStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('جاهزة للتوريد', 
                SupplyPayment::where('status', SupplyPaymentStatus::WORTH_COLLECTING)
                    ->where('due_date', '<=', now())
                    ->count()
            )
            ->description('دفعات جاهزة للتوريد')
            ->descriptionIcon('heroicon-m-banknotes')
            ->color('warning'),
            
            Stat::make('المبلغ الإجمالي المستحق',
                'ر.س ' . number_format(
                    SupplyPayment::where('status', SupplyPaymentStatus::WORTH_COLLECTING)
                        ->sum('net_amount'), 2
                )
            )
            ->description('إجمالي المبالغ المستحقة')
            ->descriptionIcon('heroicon-m-currency-dollar')
            ->color('success'),
        ];
    }
}
```

## الملخص النهائي

تم تبسيط نظام دفعات التوريد ليستخدم:
1. **Filament's conditional fields** للمنطق الشرطي المعقد
2. **Toggle with Arabic labels** للإقرار
3. **Placeholder messages** للرسائل الإعلامية  
4. **Read-only fields** بعد الإنشاء
5. **Reactive calculations** للمبلغ الصافي
6. **Built-in Actions** للتوريد السريع
7. **Model methods** بدلاً من service classes معقدة
8. **Scheduled commands** لإنشاء الدفعات تلقائياً

هذا النهج يحافظ على كامل المنطق الشرطي والوظائف المعقدة المطلوبة من ACF مع تبسيط كبير في التطبيق.