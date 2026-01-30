<?php

namespace App\Filament\Resources\UnitContracts\Pages;

use App\Filament\Resources\UnitContracts\UnitContractResource;
use App\Models\UnitContract;
use App\Services\PaymentGeneratorService;
use App\Services\PropertyContractService;
use App\Services\UnitContractService;
use Closure;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class ReschedulePayments extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = UnitContractResource::class;

    protected string $view = 'filament.resources.unit-contract-resource.pages.reschedule-payments';

    public UnitContract $record;

    public ?array $data = [];

    protected ?PaymentGeneratorService $paymentService = null;

    public function __construct()
    {
        $this->paymentService = app(PaymentGeneratorService::class);
    }

    public function mount(UnitContract $record): void
    {
        $this->record = $record;

        // Check permissions using Policy
        if (! auth()->user()->can('reschedule', $record)) {
            abort(403, 'You are not authorized to reschedule payments');
        }

        // Additional reschedule eligibility check
        if (! app(UnitContractService::class)->canReschedule($record)) {
            Notification::make()
                ->title('لا يمكن إعادة جدولة هذا العقد')
                ->body('العقد غير نشط أو لا توجد دفعات')
                ->danger()
                ->send();

            $this->redirectRoute('filament.admin.resources.unit-contracts.index');

            return;
        }

        // Load default data
        $this->form->fill([
            'new_monthly_rent' => $record->monthly_rent,
            'additional_months' => app(UnitContractService::class)->getRemainingMonths($record),
            'new_frequency' => $record->payment_frequency ?? 'monthly',
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return "إعادة جدولة دفعات العقد: {$this->record->contract_number}";
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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

                                    if ($state && ! PropertyContractService::isValidDuration($state, $frequency)) {
                                        $set('frequency_error', true);
                                    } else {
                                        $set('frequency_error', false);
                                    }
                                })
                                ->rules([
                                    fn ($get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                        $frequency = $get('new_frequency') ?? 'monthly';
                                        if (! PropertyContractService::isValidDuration($value ?? 0, $frequency)) {
                                            $periodName = match ($frequency) {
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

                                    if ($duration && ! PropertyContractService::isValidDuration($duration, $state ?? 'monthly')) {
                                        $set('frequency_error', true);
                                    } else {
                                        $set('frequency_error', false);
                                    }
                                })
                                ->rules([
                                    fn ($get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                        $duration = $get('additional_months') ?? 0;
                                        if (! PropertyContractService::isValidDuration($duration, $value ?? 'monthly')) {
                                            $periodName = match ($value) {
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
                                ->content($this->record->duration_months.' شهر'),

                            Placeholder::make('paid_months')
                                ->label('الأشهر المدفوعة')
                                ->content(fn () => app(UnitContractService::class)->getPaidMonthsCount($this->record).' شهر'),

                            Placeholder::make('remaining_months')
                                ->label('الأشهر المتبقية حالياً')
                                ->content(fn () => app(UnitContractService::class)->getRemainingMonths($this->record).' شهر'),
                        ]),

                        Grid::make(2)->schema([
                            Placeholder::make('paid_payments')
                                ->label('الدفعات المدفوعة')
                                ->content(fn () => app(UnitContractService::class)->getPaidPaymentsCount($this->record).' دفعة'),

                            Placeholder::make('unpaid_payments')
                                ->label('الدفعات غير المدفوعة')
                                ->content(fn () => app(UnitContractService::class)->getUnpaidPaymentsCount($this->record).' دفعة (سيتم حذفها)'),
                        ]),
                    ]),

                Section::make('')
                    ->schema([
                        Placeholder::make('ملخص التغييرات')
                            ->label('')
                            ->content(function ($get) {
                                $paidMonths = app(UnitContractService::class)->getPaidMonthsCount($this->record);
                                $additionalMonths = $get('additional_months') ?? 0;
                                $newTotal = $paidMonths + $additionalMonths;

                                $summary = "📊 **الملخص:**\n";
                                $summary .= "• الأشهر المدفوعة: {$paidMonths} شهر (ستبقى كما هي)\n";
                                $summary .= "• الأشهر الجديدة: {$additionalMonths} شهر\n";
                                $summary .= "• إجمالي مدة العقد الجديدة: {$newTotal} شهر\n";
                                $summary .= '• الدفعات غير المدفوعة: '.app(UnitContractService::class)->getUnpaidPaymentsCount($this->record)." دفعة (سيتم حذفها)\n";

                                $frequency = $get('new_frequency') ?? 'monthly';
                                if (PropertyContractService::isValidDuration($additionalMonths, $frequency)) {
                                    $newPaymentsCount = PropertyContractService::calculatePaymentsCount($additionalMonths, $frequency);
                                    $summary .= "• الدفعات الجديدة: {$newPaymentsCount} دفعة\n";
                                }

                                return $summary;
                            }),
                    ])
                    ->visible(fn ($get) => $get('additional_months') > 0),
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
                ->modalDescription(function () {
                    $contractNumber = $this->record->contract_number ?? 'غير محدد';
                    $tenantName = $this->record->tenant?->name ?? 'غير محدد';
                    $propertyName = $this->record->property?->name ?? 'غير محدد';
                    $unitName = $this->record->unit?->name ?? 'غير محدد';

                    $newMonthlyRent = number_format($this->data['new_monthly_rent'] ?? 0, 2);
                    $additionalMonths = $this->data['additional_months'] ?? 0;
                    $newPaymentsCount = $this->data['new_payments_count'] ?? 0;

                    $unpaidCount = app(UnitContractService::class)->getUnpaidPaymentsCount($this->record);

                    return new HtmlString(
                        "<div style='text-align: right; direction: rtl;'>
                            <p>رقم العقد: <strong>{$contractNumber}</strong></p>
                            <p>المستأجر: <strong>{$tenantName}</strong></p>
                            <p>العقار: <strong>{$propertyName}</strong> - <strong>{$unitName}</strong></p>
                            <hr style='margin: 10px 0;'>
                            <p style='color: red;'>سيتم حذف: <strong>{$unpaidCount} دفعة غير مدفوعة</strong></p>
                            <p style='color: green;'>سيتم إنشاء: <strong>{$newPaymentsCount} دفعة جديدة</strong></p>
                            <p>القيمة الجديدة: <strong>{$newMonthlyRent} ريال</strong></p>
                            <p>المدة: <strong>{$additionalMonths} شهر</strong></p>
                            <hr style='margin: 10px 0;'>
                            <p style='color: #666; font-size: 0.9em;'>هل أنت متأكد من إعادة الجدولة؟</p>
                        </div>"
                    );
                })
                ->modalSubmitActionLabel('نعم، أعد الجدولة')
                ->disabled(fn () => $this->data['frequency_error'] ?? false)
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
                            ->body("تم حذف {$result['deleted_count']} دفعة وإنشاء ".count($result['new_payments']).' دفعة جديدة')
                            ->success()
                            ->send();

                        return redirect()->route('filament.admin.resources.unit-contracts.view', $this->record);

                    } catch (Exception $e) {
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
