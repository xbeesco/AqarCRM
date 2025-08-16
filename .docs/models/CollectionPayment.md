# CollectionPayment Model Tasks - Al-Hiaa Real Estate Management System
**Target Stack:** Laravel 12 & Filament 4

## المبادئ الأساسية للتحسين
- ✅ استخدام Filament's conditional fields بدلاً من JavaScript معقد
- ✅ تبسيط Payment status management مع Laravel Enums
- ✅ استخدام Filament's reactive forms للحقول الشرطية
- ✅ تبسيط Payment workflow مع built-in actions

## 1. نموذج دفعة التحصيل المبسط

### 1.1 CollectionPayment Model
```php
class CollectionPayment extends Model
{
    protected $fillable = [
        'title', 'unit_contract_id', 'amount', 'status',
        'start_date', 'end_date', 'payment_date',
        'delay_reason', 'delay_duration', 'overdue_notes',
        'unit_id', 'owner_id', 'notification_date'
    ];
    
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date', 
        'payment_date' => 'date',
        'notification_date' => 'date',
        'amount' => 'decimal:2',
        'delay_duration' => 'integer',
        'status' => CollectionPaymentStatus::class,
    ];
    
    // العلاقات الأساسية
    public function unitContract(): BelongsTo
    {
        return $this->belongsTo(UnitContract::class);
    }
    
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
    
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    
    // دوال مبسطة
    public function isOverdue(): bool
    {
        return $this->status === CollectionPaymentStatus::OVERDUE 
            || ($this->end_date < now() && $this->status !== CollectionPaymentStatus::COLLECTED);
    }
    
    public function canBeCollected(): bool
    {
        return in_array($this->status, [
            CollectionPaymentStatus::WORTH_COLLECTING,
            CollectionPaymentStatus::DELAYED
        ]);
    }
    
    public function markAsCollected(): void
    {
        $this->update([
            'status' => CollectionPaymentStatus::COLLECTED,
            'payment_date' => now(),
        ]);
    }
}
```

### 1.2 Collection Payment Status Enum
```php
enum CollectionPaymentStatus: string
{
    case PENDING = 'pending';
    case WORTH_COLLECTING = 'worth_collecting';
    case COLLECTED = 'collected';
    case DELAYED = 'delayed';
    case OVERDUE = 'overdue';
    
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'قيد الانتظار',
            self::WORTH_COLLECTING => 'تستحق التحصيل',
            self::COLLECTED => 'تم التحصيل',
            self::DELAYED => 'المؤجلة',
            self::OVERDUE => 'تجاوزت المدة',
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::PENDING => 'info',
            self::WORTH_COLLECTING => 'warning',
            self::COLLECTED => 'success',
            self::DELAYED => 'secondary',
            self::OVERDUE => 'danger',
        };
    }
}
```

## 2. Filament Resource مع الحقول الشرطية

### 2.1 CollectionPayment Resource مع Filament 4 Syntax
```php
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;

public static function form(Schema $schema): Schema
{
    return $schema->schema([
        Section::make('بيانات الدفعة الأساسية')
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('title')
                        ->label('اسم دفعة التحصيل')
                        ->required()
                        ->columnSpan(1),
                        
                    Select::make('unit_contract_id')
                        ->label('العقد')
                        ->relationship('unitContract', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $state) {
                            // تحديث معلومات إضافية عند اختيار العقد
                            $contract = UnitContract::find($state);
                            if ($contract) {
                                $set('unit_id', $contract->unit_id);
                                $set('owner_id', $contract->property->owner_id);
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
                        ->label('حالة التحصيل')
                        ->required()
                        ->options(CollectionPaymentStatus::class)
                        ->inline()
                        ->reactive() // هذا هو المفتاح للحقول الشرطية
                        ->columnSpan(1),
                ]),
            ]),
            
        Section::make('تواريخ الدفعة')
            ->schema([
                Grid::make(2)->schema([
                    DatePicker::make('start_date')
                        ->label('بداية التاريخ')
                        ->required()
                        ->displayFormat('d/m/Y')
                        ->visible(fn (Get $get) => in_array($get('status'), [
                            CollectionPaymentStatus::COLLECTED->value,
                            CollectionPaymentStatus::WORTH_COLLECTING->value,
                            null
                        ]))
                        ->columnSpan(1),
                        
                    DatePicker::make('end_date')
                        ->label('إلى تاريخ')
                        ->required()
                        ->displayFormat('d/m/Y')
                        ->visible(fn (Get $get) => in_array($get('status'), [
                            CollectionPaymentStatus::COLLECTED->value,
                            CollectionPaymentStatus::WORTH_COLLECTING->value,
                            null
                        ]))
                        ->columnSpan(1),
                ]),
                
                DatePicker::make('payment_date')
                    ->label('تاريخ التحصيل')
                    ->displayFormat('Y-m-d')
                    ->visible(fn (Get $get) => $get('status') === CollectionPaymentStatus::COLLECTED->value)
                    ->required(fn (Get $get) => $get('status') === CollectionPaymentStatus::COLLECTED->value)
                    ->columnSpanFull(),
            ]),
            
        Section::make('تفاصيل التأجيل')
            ->schema([
                Grid::make(2)->schema([
                    Textarea::make('delay_reason')
                        ->label('سبب التأجيل')
                        ->required()
                        ->visible(fn (Get $get) => $get('status') === CollectionPaymentStatus::DELAYED->value)
                        ->rows(3)
                        ->columnSpan(1),
                        
                    TextInput::make('delay_duration')
                        ->label('مدة التأجيل بالأيام')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->visible(fn (Get $get) => $get('status') === CollectionPaymentStatus::DELAYED->value)
                        ->columnSpan(1),
                ]),
            ]),
            
        Section::make('ملاحظات المتأخرات')
            ->schema([
                Textarea::make('overdue_notes')
                    ->label('ملاحظات في حالة تجاوز مدة الدفع')
                    ->visible(fn (Get $get) => $get('status') === CollectionPaymentStatus::OVERDUE->value)
                    ->rows(3)
                    ->columnSpanFull(),
            ]),
    ]);
}
```

### 2.2 CollectionPayment Table مع Actions
```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('title')
                ->label('اسم الدفعة')
                ->searchable()
                ->sortable(),
                
            TextColumn::make('unitContract.name')
                ->label('العقد')
                ->searchable(),
                
            TextColumn::make('amount')
                ->label('المبلغ')
                ->money('SAR')
                ->sortable(),
                
            BadgeColumn::make('status')
                ->label('الحالة')
                ->colors([
                    'info' => CollectionPaymentStatus::PENDING->value,
                    'warning' => CollectionPaymentStatus::WORTH_COLLECTING->value,
                    'success' => CollectionPaymentStatus::COLLECTED->value,
                    'secondary' => CollectionPaymentStatus::DELAYED->value,
                    'danger' => CollectionPaymentStatus::OVERDUE->value,
                ]),
                
            TextColumn::make('start_date')
                ->label('من تاريخ')
                ->date('d/m/Y'),
                
            TextColumn::make('end_date')
                ->label('إلى تاريخ')
                ->date('d/m/Y'),
                
            TextColumn::make('payment_date')
                ->label('تاريخ التحصيل')
                ->date('Y-m-d')
                ->placeholder('لم يتم التحصيل'),
        ])
        ->filters([
            SelectFilter::make('status')
                ->label('حالة التحصيل')
                ->options(CollectionPaymentStatus::class),
                
            Filter::make('overdue')
                ->label('المتأخرات')
                ->query(fn (Builder $query): Builder => 
                    $query->where('status', CollectionPaymentStatus::OVERDUE)
                        ->orWhere(function ($q) {
                            $q->where('end_date', '<', now())
                              ->where('status', '!=', CollectionPaymentStatus::COLLECTED);
                        })
                ),
                
            DateFilter::make('payment_date')
                ->label('تاريخ التحصيل'),
        ])
        ->actions([
            // Action سريع لتحصيل الدفعة
            Tables\Actions\Action::make('collect')
                ->label('تحصيل')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => $record->canBeCollected())
                ->action(function ($record) {
                    $record->markAsCollected();
                    
                    Notification::make()
                        ->title('تم تحصيل الدفعة بنجاح')
                        ->success()
                        ->send();
                }),
                
            // Action لتأجيل الدفعة
            Tables\Actions\Action::make('delay')
                ->label('تأجيل')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->visible(fn ($record) => $record->status === CollectionPaymentStatus::WORTH_COLLECTING)
                ->form([
                    Textarea::make('delay_reason')
                        ->label('سبب التأجيل')
                        ->required(),
                    TextInput::make('delay_duration')
                        ->label('مدة التأجيل (أيام)')
                        ->numeric()
                        ->required(),
                ])
                ->action(function ($record, array $data) {
                    $record->update([
                        'status' => CollectionPaymentStatus::DELAYED,
                        'delay_reason' => $data['delay_reason'],
                        'delay_duration' => $data['delay_duration'],
                    ]);
                    
                    Notification::make()
                        ->title('تم تأجيل الدفعة')
                        ->warning()
                        ->send();
                }),
                
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([
            // Bulk Action للتحصيل المتعدد
            Tables\Actions\BulkAction::make('bulk_collect')
                ->label('تحصيل المحدد')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function (Collection $records) {
                    $records->each(function ($record) {
                        if ($record->canBeCollected()) {
                            $record->markAsCollected();
                        }
                    });
                    
                    Notification::make()
                        ->title('تم تحصيل الدفعات المحددة')
                        ->success()
                        ->send();
                }),
        ]);
}
```

## 3. تبسيط Payment Workflow

### 3.1 إزالة Services المعقدة
- **❌ إزالة**: CollectionService معقد
- **❌ إزالة**: PaymentCalculationService معقد
- **❌ إزالة**: FinancialService معقد
- **✅ بدلاً منها**: دوال مباشرة في Model

### 3.2 استخدام Filament Actions للعمليات
```php
// بدلاً من service classes معقدة، استخدام Filament actions
public static function getHeaderActions(): array
{
    return [
        Action::make('generate_payments')
            ->label('إنشاء دفعات جديدة')
            ->icon('heroicon-o-plus-circle')
            ->action(function () {
                // منطق إنشاء الدفعات
                $activeContracts = UnitContract::where('status', 'active')->get();
                
                foreach ($activeContracts as $contract) {
                    $contract->generateCollectionPayments();
                }
                
                Notification::make()
                    ->title('تم إنشاء دفعات التحصيل')
                    ->success()
                    ->send();
            }),
    ];
}
```

## 4. التبسيطات المطبقة

### 4.1 إزالة Over-Engineering
- **❌ إزالة**: CollectionService مع دوال معقدة
- **❌ إزالة**: PaymentProcessor معقد
- **❌ إزالة**: ReceiptGenerator معقد
- **❌ إزالة**: PaymentReconciliation معقد
- **✅ بدلاً منها**: Model methods بسيطة و Filament actions

### 4.2 استخدام Filament's Conditional Logic
- **Reactive Forms**: تغيير الحقول بناءً على الحالة
- **Conditional Validation**: validation مختلف حسب الحالة
- **Dynamic Visibility**: إظهار/إخفاء الحقول حسب السياق

### 4.3 تبسيط Status Management
```php
// بدلاً من state machine معقد
public function updateStatus(CollectionPaymentStatus $newStatus, array $data = []): void
{
    $this->update(array_merge([
        'status' => $newStatus,
    ], $data));
    
    // تنفيذ side effects بسيطة
    if ($newStatus === CollectionPaymentStatus::COLLECTED) {
        $this->payment_date = now();
        $this->save();
    }
}
```

## 5. Migration من WordPress

### 5.1 CollectionPayment Import مبسط
```php
class CollectionPaymentImportService
{
    public function importCollectionPayments(): void
    {
        $wpPayments = $this->getWordPressCollectionPayments();
        
        foreach ($wpPayments as $wpPayment) {
            CollectionPayment::create([
                'title' => $wpPayment->post_title,
                'unit_contract_id' => $this->mapContract($wpPayment->contract_id),
                'amount' => $wpPayment->amount,
                'status' => $this->mapStatus($wpPayment->status),
                'start_date' => Carbon::parse($wpPayment->start_date),
                'end_date' => Carbon::parse($wpPayment->end_date),
                'payment_date' => $wpPayment->payment_date ? Carbon::parse($wpPayment->payment_date) : null,
                'delay_reason' => $wpPayment->delay_reason,
                'delay_duration' => $wpPayment->delay_duration,
                'overdue_notes' => $wpPayment->overdue_notes,
            ]);
        }
    }
    
    private function mapStatus($wpStatus): CollectionPaymentStatus
    {
        return match ($wpStatus) {
            'collected' => CollectionPaymentStatus::COLLECTED,
            'worth_collecting' => CollectionPaymentStatus::WORTH_COLLECTING,
            'delayed' => CollectionPaymentStatus::DELAYED,
            'overdue' => CollectionPaymentStatus::OVERDUE,
            default => CollectionPaymentStatus::PENDING,
        };
    }
}
```

## 6. الاختبارات المبسطة

### 6.1 اختبارات أساسية فقط
```php
class CollectionPaymentModelTest extends TestCase
{
    public function test_payment_belongs_to_contract()
    {
        $contract = UnitContract::factory()->create();
        $payment = CollectionPayment::factory()->create([
            'unit_contract_id' => $contract->id
        ]);
        
        $this->assertEquals($contract->id, $payment->unitContract->id);
    }
    
    public function test_payment_status_workflow()
    {
        $payment = CollectionPayment::factory()->create([
            'status' => CollectionPaymentStatus::WORTH_COLLECTING
        ]);
        
        $this->assertTrue($payment->canBeCollected());
        
        $payment->markAsCollected();
        
        $this->assertEquals(CollectionPaymentStatus::COLLECTED, $payment->status);
        $this->assertNotNull($payment->payment_date);
    }
    
    public function test_overdue_detection()
    {
        $payment = CollectionPayment::factory()->create([
            'end_date' => now()->subDays(5),
            'status' => CollectionPaymentStatus::WORTH_COLLECTING
        ]);
        
        $this->assertTrue($payment->isOverdue());
    }
}
```

### 6.2 Feature Tests للحقول الشرطية
```php
class CollectionPaymentConditionalFieldsTest extends TestCase
{
    public function test_conditional_field_visibility()
    {
        // اختبار إظهار حقول التأجيل عند اختيار "مؤجل"
        // اختبار إظهار تاريخ التحصيل عند اختيار "تم التحصيل"
    }
    
    public function test_status_based_validation()
    {
        // اختبار validation مختلف حسب الحالة
    }
}
```

## 7. Scheduled Tasks للمتأخرات

### 7.1 تحديث المتأخرات تلقائياً
```php
class UpdateOverduePaymentsCommand extends Command
{
    public function handle()
    {
        // تحديث الدفعات المتأخرة
        CollectionPayment::where('status', '!=', CollectionPaymentStatus::COLLECTED)
            ->where('end_date', '<', now())
            ->update(['status' => CollectionPaymentStatus::OVERDUE]);
            
        // إرسال إشعارات للمتأخرات الجديدة
        $newOverdue = CollectionPayment::where('status', CollectionPaymentStatus::OVERDUE)
            ->whereNull('notification_date')
            ->get();
            
        foreach ($newOverdue as $payment) {
            // إرسال إشعار
            $payment->update(['notification_date' => now()]);
        }
        
        $this->info('تم تحديث ' . $newOverdue->count() . ' دفعة متأخرة');
    }
}
```

## 8. Widgets للمتابعة

### 8.1 Collection Payments Widgets
```php
class OverduePaymentsWidget extends Widget
{
    protected static string $view = 'filament.widgets.overdue-payments';
    
    public function getOverduePayments()
    {
        return CollectionPayment::where('status', CollectionPaymentStatus::OVERDUE)
            ->orWhere(function ($q) {
                $q->where('end_date', '<', now())
                  ->where('status', '!=', CollectionPaymentStatus::COLLECTED);
            })
            ->with(['unitContract.unit', 'unitContract.tenant'])
            ->get();
    }
}
```

## الملخص النهائي

تم تبسيط نظام دفعات التحصيل ليستخدم:
1. **Filament's conditional fields** للحقول الشرطية المعقدة
2. **Laravel Enums** لإدارة حالات الدفع
3. **Filament Actions** للعمليات السريعة (تحصيل، تأجيل)
4. **Reactive forms** للتفاعل الديناميكي
5. **Built-in validation** مع conditional rules
6. **Model methods** بدلاً من service classes معقدة
7. **Scheduled commands** لتحديث المتأخرات تلقائياً

هذا النهج يحافظ على كامل المنطق الشرطي المعقد المطلوب من ACF مع تبسيط كبير في التطبيق.