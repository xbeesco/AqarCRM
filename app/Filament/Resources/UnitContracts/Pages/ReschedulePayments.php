<?php

namespace App\Filament\Resources\UnitContracts\Pages;

use App\Filament\Resources\UnitContracts\UnitContractResource;
use App\Models\UnitContract;
use App\Services\PaymentGeneratorService;
use App\Services\PropertyContractService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
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

    protected static ?string $title = 'إعادة جدولة دفعات العقد';

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

        // التحقق من الصلاحيات - super_admin, admin, employee
        if (! in_array(auth()->user()?->type, ['super_admin', 'admin', 'employee'])) {
            abort(403, 'غير مصرح لك بإعادة جدولة الدفعات');
        }

        // Additional reschedule eligibility check
        if (! $this->record->canBeRescheduled()) {
            Notification::make()
                ->title('لا يمكن إعادة جدولة هذا العقد')
                ->body('العقد غير نشط أو لا توجد دفعات')
                ->danger()
                ->send();

            $this->redirectRoute('filament.admin.resources.unit-contracts.index');

            return;
        }

        // تحميل البيانات الافتراضية
        $this->form->fill([
            'new_monthly_rent' => $record->monthly_rent,
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
                                ->columnSpan(3),

                            ...(\App\Filament\Forms\ContractFormSchema::getDurationFields('unit', $this->record)),
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
                                ->content(fn () => $this->record->getPaidMonthsCount().' شهر'),

                            Placeholder::make('remaining_months')
                                ->label('الأشهر المتبقية حالياً')
                                ->content(fn () => $this->record->getRemainingMonths().' شهر'),
                        ]),

                        Grid::make(2)->schema([
                            Placeholder::make('paid_payments')
                                ->label('الدفعات المدفوعة')
                                ->content(fn () => $this->record->getPaidPaymentsCount().' دفعة'),

                            Placeholder::make('unpaid_payments')
                                ->label('الدفعات غير المدفوعة')
                                ->content(fn () => $this->record->getUnpaidPaymentsCount().' دفعة (سيتم حذفها)'),
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
                                $summary .= '• الدفعات غير المدفوعة: '.$this->record->getUnpaidPaymentsCount()." دفعة (سيتم حذفها)\n";

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
                ->mountUsing(function () {
                    // التحقق من صحة النموذج قبل عرض نافذة التأكيد
                    $this->form->validate();
                })
                ->requiresConfirmation()
                ->modalHeading('تأكيد إعادة الجدولة')
                ->modalDescription(function () {
                    $additionalMonths = $this->data['additional_months'] ?? 0;
                    $newRent = number_format($this->data['new_monthly_rent'] ?? 0, 2);
                    $frequency = $this->data['new_frequency'] ?? 'monthly';
                    $newPaymentsCount = PropertyContractService::calculatePaymentsCount($additionalMonths, $frequency);
                    $unpaidCount = $this->record->getUnpaidPaymentsCount();

                    return new HtmlString(
                        "<div style='text-align: right; direction: rtl;'>
                            <p>رقم العقد: <strong>{$this->record->contract_number}</strong></p>
                            <p>المستأجر: <strong>{$this->record->tenant?->name}</strong></p>
                            <p>العقار: <strong>{$this->record->property?->name}</strong> - <strong>{$this->record->unit?->name}</strong></p>
                            <hr style='margin: 10px 0;'>
                            <p style='color: red;'>سيتم حذف: <strong>{$unpaidCount} دفعة غير مدفوعة</strong></p>
                            <p style='color: green;'>سيتم إنشاء: <strong>{$newPaymentsCount} دفعة جديدة</strong></p>
                            <p>قيمة الإيجار: <strong>{$newRent} ريال</strong></p>
                            <p>المدة الإضافية: <strong>{$additionalMonths} شهر</strong></p>
                        </div>"
                    );
                })
                ->modalSubmitActionLabel('نعم، أعد الجدولة')
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

                    } catch (\Exception $e) {
                        // إظهار الخطأ تحت حقل المدة
                        $this->addError('data.additional_months', $e->getMessage());
                    }
                }),

            Action::make('cancel')
                ->label('إلغاء')
                ->color('gray')
                ->url(route('filament.admin.resources.unit-contracts.view', $this->record)),
        ];
    }
}
