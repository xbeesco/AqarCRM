<?php

namespace App\Filament\Resources\UnitContractResource\Pages;

use App\Filament\Resources\UnitContractResource;
use App\Models\UnitContract;
use App\Services\PaymentGeneratorService;
use App\Services\PropertyContractService;
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

        if (! auth()->user()->can('renew', $record)) {
            abort(403, 'غير مصرح لك بتجديد العقد');
        }

        $this->form->fill([
            'new_monthly_rent' => $record->monthly_rent,
            'extension_months' => null,
            'new_frequency' => $record->payment_frequency ?? 'monthly',
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return "تجديد عقد الوحدة: {$this->record->contract_number}";
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
                            TextInput::make('new_monthly_rent')
                                ->label('قيمة الإيجار للفترة الجديدة')
                                ->numeric()
                                ->required()
                                ->minValue(0.01)
                                ->step(0.01)
                                ->postfix('ريال')
                                ->live()
                                ->afterStateUpdated(function ($state) {
                                    if (($state ?? 0) <= 0) {
                                        Notification::make()
                                            ->title('خطأ في قيمة الإيجار')
                                            ->body('يجب أن تكون قيمة الإيجار أكبر من صفر')
                                            ->danger()
                                            ->send();
                                    }
                                })
                                ->validationMessages([
                                    'required' => 'يجب إدخال قيمة الإيجار',
                                    'min' => 'يجب أن تكون قيمة الإيجار أكبر من صفر',
                                ])
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
                                                'quarterly' => 'ربع سنة',
                                                'semi_annually' => 'نصف سنة',
                                                'annually' => 'سنة',
                                                default => $frequency,
                                            };
                                            $fail("عدد الاشهر هذا لا يقبل القسمة علي {$periodName}");
                                        }
                                    },
                                ])
                                ->validationMessages([
                                    'required' => 'يجب إدخال مدة التجديد',
                                    'min' => 'يجب أن تكون مدة التجديد شهر واحد على الأقل',
                                ])
                                ->columnSpan(3),

                            Select::make('new_frequency')
                                ->label('تحصيل تلك المدة سيكون كل')
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
                                ->validationMessages([
                                    'required' => 'يجب اختيار دورية التحصيل',
                                ])
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
                                ->content(fn () => $this->record->payments()->count().' دفعة'),

                            Placeholder::make('unpaid_payments')
                                ->label('الدفعات غير المدفوعة')
                                ->content(fn () => $this->record->getUnpaidPaymentsCount().' دفعة (ستبقى كما هي)'),
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
                                $newRent = $get('new_monthly_rent') ?? 0;

                                $currentEndDate = $this->record->end_date;
                                $newStartDate = $currentEndDate ? $currentEndDate->copy()->addDay() : now();
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
                                $summary .= '**قيمة الإيجار:** '.number_format($newRent, 2)." ريال\n\n";
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
                ->requiresConfirmation()
                ->modalHeading('تأكيد تجديد العقد')
                ->modalDescription(function () {
                    $extensionMonths = $this->data['extension_months'] ?? 0;
                    $newRent = number_format($this->data['new_monthly_rent'] ?? 0, 2);
                    $frequency = $this->data['new_frequency'] ?? 'monthly';
                    $newPaymentsCount = PropertyContractService::calculatePaymentsCount($extensionMonths, $frequency);

                    return new \Illuminate\Support\HtmlString(
                        "<div style='text-align: right; direction: rtl;'>
                            <p>رقم العقد: <strong>{$this->record->contract_number}</strong></p>
                            <p>المستأجر: <strong>{$this->record->tenant?->name}</strong></p>
                            <hr style='margin: 10px 0;'>
                            <p style='color: green;'>سيتم إضافة: <strong>{$extensionMonths} شهر</strong></p>
                            <p style='color: green;'>سيتم توليد: <strong>{$newPaymentsCount} دفعة جديدة</strong></p>
                            <p>قيمة الإيجار: <strong>{$newRent} ريال</strong></p>
                            <hr style='margin: 10px 0;'>
                            <p style='color: #666; font-size: 0.9em;'>الدفعات الحالية لن تتأثر</p>
                        </div>"
                    );
                })
                ->modalSubmitActionLabel('نعم، جدد العقد')
                ->disabled(fn () => (($this->data['extension_months'] ?? 0) < 1)
                    || (($this->data['new_monthly_rent'] ?? 0) <= 0))
                ->action(function () {
                    // التحقق من أن المدة أكبر من صفر
                    if (($this->data['extension_months'] ?? 0) < 1) {
                        Notification::make()
                            ->title('خطأ')
                            ->body('يجب أن تكون مدة التجديد شهر واحد على الأقل')
                            ->danger()
                            ->send();

                        return;
                    }

                    // التحقق من أن قيمة الإيجار أكبر من صفر
                    if (($this->data['new_monthly_rent'] ?? 0) <= 0) {
                        Notification::make()
                            ->title('خطأ')
                            ->body('يجب أن تكون قيمة الإيجار أكبر من صفر')
                            ->danger()
                            ->send();

                        return;
                    }

                    try {
                        $result = $this->paymentService->renewUnitContract(
                            $this->record,
                            $this->data['new_monthly_rent'],
                            $this->data['extension_months'],
                            $this->data['new_frequency']
                        );

                        Notification::make()
                            ->title('تم تجديد العقد بنجاح')
                            ->body("تم إضافة {$result['extension_months']} شهر وتوليد ".count($result['new_payments']).' دفعة جديدة')
                            ->success()
                            ->send();

                        return redirect()->route('filament.admin.resources.unit-contracts.view', $this->record);

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('فشل التجديد')
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
