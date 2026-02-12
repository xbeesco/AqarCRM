<?php

namespace App\Filament\Resources\PropertyContracts\Pages;

use App\Models\PropertyContract;
use App\Services\PaymentGeneratorService;
use App\Services\PropertyContractService;
use App\Services\PropertyContractValidationService;
use Carbon\Carbon;
use Closure;
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

class RenewContract extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = \App\Filament\Resources\PropertyContracts\PropertyContractResource::class;

    protected string $view = 'filament.resources.property-contract-resource.pages.reschedule-payments';

    public PropertyContract $record;

    public ?array $data = [];

    protected ?PaymentGeneratorService $paymentService = null;

    public function __construct()
    {
        $this->paymentService = app(PaymentGeneratorService::class);
    }

    public function mount(PropertyContract $record): void
    {
        $this->record = $record;

        if (! auth()->user()->can('renew', $record)) {
            abort(403, 'غير مصرح لك بتجديد العقد');
        }

        $this->form->fill([
            'new_commission_rate' => $record->commission_rate,
            'extension_months' => null,
            'new_frequency' => $record->payment_frequency ?? 'monthly',
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return "تجديد عقد العقار: {$this->record->contract_number}";
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('تفاصيل التجديد')
                    ->description('سيتم إضافة الأشهر الجديدة بعد نهاية العقد الحالي')
                    ->columnspan(2)
                    ->schema([
                        Grid::make(12)->schema([
                            TextInput::make('new_commission_rate')
                                ->label('نسبة العمولة للفترة الجديدة')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100)
                                ->suffix('%')
                                ->columnSpan(3),

                            TextInput::make('extension_months')
                                ->label('مدة التجديد')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->suffix('شهر')
                                ->live()
                                ->afterStateUpdated(function ($state, $get, $set) {
                                    $frequency = $get('new_frequency') ?? 'monthly';
                                    $count = PropertyContractService::calculatePaymentsCount($state ?? 0, $frequency);
                                    $set('new_payments_count', $count);

                                    if (($state ?? 0) < 1) {
                                        Notification::make()
                                            ->title('خطأ في المدة')
                                            ->body('يجب أن تكون مدة التجديد شهر واحد على الأقل')
                                            ->danger()
                                            ->send();
                                    }
                                })
                                ->rules([
                                    fn ($get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                        $frequency = $get('new_frequency') ?? 'monthly';

                                        if (! PropertyContractService::isValidDuration($value ?? 0, $frequency)) {
                                            $periodName = match ($frequency) {
                                                'monthly' => 'شهر',
                                                'quarterly' => 'ربع سنة',
                                                'semi_annually' => 'نصف سنة',
                                                'annually' => 'سنة',
                                                default => 'شهر',
                                            };
                                            $fail("عدد الاشهر هذا لا يقبل القسمة علي {$periodName}");
                                        }
                                    },
                                    // التحقق من التداخل مع عقود مستقبلية
                                    fn (): Closure => function (string $attribute, $value, Closure $fail) {
                                        if (! $value || $value < 1) {
                                            return;
                                        }

                                        $propertyId = $this->record->property_id;
                                        $renewalStartDate = $this->record->end_date->copy()->addDay();

                                        $validationService = app(PropertyContractValidationService::class);
                                        $error = $validationService->validateDuration(
                                            $propertyId,
                                            $renewalStartDate,
                                            $value,
                                            $this->record->id
                                        );

                                        if ($error) {
                                            $fail($error);
                                        }
                                    },
                                ])
                                ->validationMessages([
                                    'required' => 'يجب إدخال مدة التجديد',
                                    'min' => 'يجب أن تكون مدة التجديد شهر واحد على الأقل',
                                ])
                                ->columnSpan(3),

                            Select::make('new_frequency')
                                ->label('توريد تلك المده سيكون كل')
                                ->required()
                                ->options([
                                    'monthly' => 'شهر',
                                    'quarterly' => 'ربع سنة',
                                    'semi_annually' => 'نصف سنة',
                                    'annually' => 'سنة',
                                ])
                                ->default('monthly')
                                ->live()
                                ->afterStateUpdated(function ($state, $get, $set) {
                                    $duration = $get('extension_months') ?? 0;
                                    $count = PropertyContractService::calculatePaymentsCount($duration, $state ?? 'monthly');
                                    $set('new_payments_count', $count);
                                })
                                ->columnSpan(3),

                            TextInput::make('new_payments_count')
                                ->label('عدد الدفعات الجديدة')
                                ->disabled()
                                ->dehydrated(false)
                                ->default(function ($get) {
                                    $duration = $get('extension_months') ?? 12;
                                    $frequency = $get('new_frequency') ?? 'monthly';

                                    return PropertyContractService::calculatePaymentsCount($duration, $frequency);
                                })
                                ->columnSpan(3),
                        ]),
                    ]),

                Section::make('معلومات العقد الحالي')
                    ->columnspan(1)
                    ->schema([
                        Grid::make(2)->schema([
                            Placeholder::make('original_duration')
                                ->label('المدة الحالية')
                                ->content($this->record->duration_months.' شهر'),

                            Placeholder::make('current_end_date')
                                ->label('تاريخ الانتهاء الحالي')
                                ->content(fn () => $this->record->end_date?->format('Y-m-d') ?? '-'),

                            Placeholder::make('total_payments')
                                ->label('إجمالي الدفعات الحالية')
                                ->content(fn () => $this->record->supplyPayments()->count().' دفعة'),

                            Placeholder::make('unpaid_payments')
                                ->label('الدفعات غير المدفوعة')
                                ->content(fn () => $this->record->getUnpaidPayments()->count().' دفعة (ستبقى كما هي)'),
                        ]),
                    ]),

                Section::make('ملخص التجديد')
                    ->icon('heroicon-o-arrow-path')
                    ->schema([
                        Placeholder::make('renewal_summary')
                            ->label('')
                            ->content(function ($get) {
                                $extensionMonths = (int) ($get('extension_months') ?? 0);
                                $frequency = $get('new_frequency') ?? 'monthly';
                                $newCommission = $get('new_commission_rate') ?? 0;

                                $currentEndDate = $this->record->end_date;
                                $newStartDate = $currentEndDate ? Carbon::parse($currentEndDate)->addDay() : now();
                                $newEndDate = $newStartDate->copy()->addMonths($extensionMonths)->subDay();

                                $newPaymentsCount = PropertyContractService::calculatePaymentsCount($extensionMonths, $frequency);
                                $newTotalMonths = $this->record->duration_months + $extensionMonths;

                                $summary = "**ملخص التجديد:**\n\n";
                                $summary .= "**المدة الحالية:** {$this->record->duration_months} شهر\n";
                                $summary .= "**مدة التجديد:** {$extensionMonths} شهر\n";
                                $summary .= "**إجمالي المدة الجديدة:** {$newTotalMonths} شهر\n\n";
                                $summary .= "**تاريخ بداية التجديد:** {$newStartDate->format('Y-m-d')}\n";
                                $summary .= "**تاريخ النهاية الجديد:** {$newEndDate->format('Y-m-d')}\n\n";
                                $summary .= "**الدفعات الجديدة:** {$newPaymentsCount} دفعة\n";
                                $summary .= "**نسبة العمولة:** {$newCommission}%\n\n";
                                $summary .= '**ملاحظة:** الدفعات الحالية لن تتأثر';

                                return $summary;
                            }),
                    ])
                    ->visible(fn ($get) => ((int) ($get('extension_months') ?? 0)) > 0),
            ])
            ->columns(2)
            ->statePath('data');
    }

    protected function getActions(): array
    {
        return [
            Action::make('renew')
                ->label('تأكيد التجديد')
                ->color('success')
                ->icon('heroicon-o-check')
                ->mountUsing(function () {
                    // التحقق من صحة النموذج قبل عرض نافذة التأكيد
                    $this->form->validate();
                })
                ->requiresConfirmation()
                ->modalHeading('تأكيد تجديد العقد')
                ->modalDescription(function () {
                    $extensionMonths = $this->data['extension_months'] ?? 0;
                    $newCommission = $this->data['new_commission_rate'] ?? 0;
                    $frequency = $this->data['new_frequency'] ?? 'monthly';
                    $newPaymentsCount = PropertyContractService::calculatePaymentsCount($extensionMonths, $frequency);

                    return new \Illuminate\Support\HtmlString(
                        "<div style='text-align: right; direction: rtl;'>
                            <p>رقم العقد: <strong>{$this->record->contract_number}</strong></p>
                            <p>المالك: <strong>{$this->record->owner?->name}</strong></p>
                            <p>العقار: <strong>{$this->record->property?->name}</strong></p>
                            <hr style='margin: 10px 0;'>
                            <p style='color: green;'>سيتم إضافة: <strong>{$extensionMonths} شهر</strong></p>
                            <p style='color: green;'>سيتم توليد: <strong>{$newPaymentsCount} دفعة جديدة</strong></p>
                            <p>نسبة العمولة: <strong>{$newCommission}%</strong></p>
                            <hr style='margin: 10px 0;'>
                            <p style='color: #666; font-size: 0.9em;'>الدفعات الحالية لن تتأثر</p>
                        </div>"
                    );
                })
                ->modalSubmitActionLabel('نعم، جدد العقد')
                ->action(function () {
                    try {
                        $result = $this->paymentService->renewPropertyContract(
                            $this->record,
                            $this->data['new_commission_rate'],
                            $this->data['extension_months'],
                            $this->data['new_frequency']
                        );

                        Notification::make()
                            ->title('تم تجديد العقد بنجاح')
                            ->body("تم إضافة {$result['extension_months']} شهر وتوليد ".count($result['new_payments']).' دفعة جديدة')
                            ->success()
                            ->send();

                        return redirect()->route('filament.admin.resources.property-contracts.view', $this->record);

                    } catch (\Exception $e) {
                        // إظهار الخطأ تحت حقل المدة
                        $this->addError('data.extension_months', $e->getMessage());
                    }
                }),

            Action::make('cancel')
                ->label('إلغاء')
                ->color('gray')
                ->url(route('filament.admin.resources.property-contracts.view', $this->record)),
        ];
    }
}
