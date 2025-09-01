<?php

namespace App\Filament\Resources\UnitContractResource\Pages;

use App\Filament\Resources\UnitContractResource;
use App\Models\UnitContract;
use App\Services\PaymentGeneratorService;
use App\Services\PropertyContractService;
use Filament\Resources\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Closure;

class ReschedulePayments extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = UnitContractResource::class;

    protected string $view = 'filament.resources.unit-contract-resource.pages.reschedule-payments';

    public UnitContract $record;
    public ?array $data = [];
    
    protected PaymentGeneratorService $paymentService;

    public function mount(UnitContract $record): void
    {
        $this->record = $record;
        $this->paymentService = app(PaymentGeneratorService::class);
        
        // التحقق من الصلاحيات
        if (auth()->user()->type !== 'super_admin') {
            abort(403, 'غير مصرح لك بإعادة جدولة الدفعات');
        }
        
        // التحقق من إمكانية إعادة الجدولة
        if (!$record->canReschedule()) {
            Notification::make()
                ->title('لا يمكن إعادة جدولة هذا العقد')
                ->body('العقد غير نشط أو لا توجد دفعات')
                ->danger()
                ->send();
                
            redirect()->route('filament.admin.resources.unit-contracts.index');
        }
        
        // تحميل البيانات الافتراضية
        $this->form->fill([
            'new_monthly_rent' => $record->monthly_rent,
            'additional_months' => $record->getRemainingMonths(),
            'new_frequency' => $record->payment_frequency ?? 'monthly',
        ]);
    }

    public function getTitle(): string | Htmlable
    {
        return "إعادة جدولة دفعات العقد: {$this->record->contract_number}";
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(null)
                    ->columnspan(2)
                    ->schema([
                        Grid::make(12)->schema([
                            TextInput::make('new_monthly_rent')
                                ->label('قيمة الإيجار')
                                ->numeric()
                                ->required()
                                ->minValue(0.01)
                                ->step(0.01)
                                ->postfix('ريال')
                                ->columnSpan(3),
                                
                            TextInput::make('additional_months')
                                ->label('المدة المعاد جدولتها')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->suffix('شهر')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, $get, $set) {
                                    $frequency = $get('new_frequency') ?? 'monthly';
                                    $count = PropertyContractService::calculatePaymentsCount($state ?? 0, $frequency);
                                    $set('new_payments_count', $count);
                                    
                                    if ($state && !PropertyContractService::isValidDuration($state, $frequency)) {
                                        $set('frequency_error', true);
                                    } else {
                                        $set('frequency_error', false);
                                    }
                                })
                                ->rules([
                                    fn ($get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                        $frequency = $get('new_frequency') ?? 'monthly';
                                        if (!PropertyContractService::isValidDuration($value ?? 0, $frequency)) {
                                            $periodName = match($frequency) {
                                                'quarterly' => 'ربع سنة',
                                                'semi_annually' => 'نصف سنة',
                                                'annually' => 'سنة',
                                                default => $frequency,
                                            };
                                            
                                            $fail("عدد الاشهر هذا لا يقبل القسمة علي {$periodName}");
                                        }
                                    },
                                ])
                                ->validationAttribute('مدة التعاقد')
                                ->columnSpan(3),
                                
                            Select::make('new_frequency')
                                ->label('تحصيل تلك المدة سيكون كل')
                                ->required()
                                ->searchable()
                                ->options([
                                    'monthly' => 'شهر',
                                    'quarterly' => 'ربع سنة',
                                    'semi_annually' => 'نصف سنة',
                                    'annually' => 'سنة',
                                ])
                                ->default('monthly')
                                ->live()
                                ->afterStateUpdated(function ($state, $get, $set) {
                                    $duration = $get('additional_months') ?? 0;
                                    $count = PropertyContractService::calculatePaymentsCount($duration, $state ?? 'monthly');
                                    $set('new_payments_count', $count);
                                    
                                    if ($duration && !PropertyContractService::isValidDuration($duration, $state ?? 'monthly')) {
                                        $set('frequency_error', true);
                                    } else {
                                        $set('frequency_error', false);
                                    }
                                })
                                ->rules([
                                    fn ($get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                        $duration = $get('additional_months') ?? 0;
                                        if (!PropertyContractService::isValidDuration($duration, $value ?? 'monthly')) {
                                            $periodName = match($value) {
                                                'quarterly' => 'ربع سنة',
                                                'semi_annually' => 'نصف سنة',
                                                'annually' => 'سنة',
                                                default => $value,
                                            };
                                            $fail("عدد الاشهر هذا لا يقبل القسمة علي {$periodName}");
                                        }
                                    },
                                ])
                                ->validationAttribute('تكرار التحصيل')
                                ->columnSpan(3),
                                
                            TextInput::make('new_payments_count')
                                ->label('عدد الدفعات')
                                ->disabled()
                                ->dehydrated(false)
                                ->default(function ($get) {
                                    $duration = $get('additional_months') ?? 0;
                                    $frequency = $get('new_frequency') ?? 'monthly';
                                    $result = PropertyContractService::calculatePaymentsCount($duration, $frequency);
                                    return $result;
                                })
                                ->columnSpan(3),
                        ]),
                    ]),
                    
                Section::make('معلومات العقد الحالي')
                    ->columnspan(1)
                    ->schema([
                    
                        Grid::make(3)->schema([
                            Placeholder::make('original_duration')
                                ->label('المدة الأصلية')
                                ->content($this->record->duration_months . ' شهر'),
                                
                            Placeholder::make('paid_months')
                                ->label('الأشهر المدفوعة')
                                ->content(fn() => $this->record->getPaidMonthsCount() . ' شهر'),
                                
                            Placeholder::make('remaining_months')
                                ->label('الأشهر المتبقية حالياً')
                                ->content(fn() => $this->record->getRemainingMonths() . ' شهر'),
                        ]),
                        
                        Grid::make(2)->schema([
                            Placeholder::make('paid_payments')
                                ->label('الدفعات المدفوعة')
                                ->content(fn() => $this->record->getPaidPaymentsCount() . ' دفعة'),
                                
                            Placeholder::make('unpaid_payments')
                                ->label('الدفعات غير المدفوعة')
                                ->content(fn() => $this->record->getUnpaidPaymentsCount() . ' دفعة (سيتم حذفها)'),
                        ]),
                    ]),
                    
                Section::make('')
                    ->schema([
                        Placeholder::make('ملخص التغييرات')
                            ->label('')
                            ->content(function ($get) {
                                $paidMonths = $this->record->getPaidMonthsCount();
                                $additionalMonths = $get('additional_months') ?? 0;
                                $newTotal = $paidMonths + $additionalMonths;
                                
                                $summary = "📊 **الملخص:**\n";
                                $summary .= "• الأشهر المدفوعة: {$paidMonths} شهر (ستبقى كما هي)\n";
                                $summary .= "• الأشهر الجديدة: {$additionalMonths} شهر\n";
                                $summary .= "• إجمالي مدة العقد الجديدة: {$newTotal} شهر\n";
                                $summary .= "• الدفعات غير المدفوعة: " . $this->record->getUnpaidPaymentsCount() . " دفعة (سيتم حذفها)\n";
                                
                                $frequency = $get('new_frequency') ?? 'monthly';
                                if (PropertyContractService::isValidDuration($additionalMonths, $frequency)) {
                                    $newPaymentsCount = PropertyContractService::calculatePaymentsCount($additionalMonths, $frequency);
                                    $summary .= "• الدفعات الجديدة: {$newPaymentsCount} دفعة\n";
                                }
                                
                                return $summary;
                            }),
                    ])
                    ->visible(fn($get) => $get('additional_months') > 0),
            ])
            ->columns(2)
            ->statePath('data');
    }

    protected function getActions(): array
    {
        return [
            Action::make('reschedule')
                ->label('تنفيذ إعادة الجدولة')
                ->color('success')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->modalHeading('تأكيد إعادة الجدولة')
                ->modalDescription('سيتم حذف جميع الدفعات غير المدفوعة وإنشاء دفعات جديدة حسب البيانات المدخلة. هل أنت متأكد؟')
                ->modalSubmitActionLabel('نعم، أعد الجدولة')
                ->disabled(fn() => $this->data['frequency_error'] ?? false)
                ->action(function () {
                    try {
                        $result = $this->paymentService->rescheduleContractPayments(
                            $this->record,
                            $this->data['new_monthly_rent'],
                            $this->data['additional_months'],
                            $this->data['new_frequency']
                        );
                        
                        Notification::make()
                            ->title('تمت إعادة الجدولة بنجاح')
                            ->body("تم حذف {$result['deleted_count']} دفعة وإنشاء " . count($result['new_payments']) . " دفعة جديدة")
                            ->success()
                            ->send();
                            
                        return redirect()->route('filament.admin.resources.unit-contracts.view', $this->record);
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('فشلت إعادة الجدولة')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
                
            Action::make('cancel')
                ->label('إلغاء')
                ->color('gray')
                ->url(route('filament.admin.resources.unit-contracts.view', $this->record)),
        ];
    }
}