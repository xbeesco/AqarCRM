<?php

namespace App\Filament\Resources\PropertyContractResource\Pages;

use App\Filament\Resources\PropertyContractResource;
use App\Models\PropertyContract;
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

class ReschedulePayments extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = PropertyContractResource::class;

    protected string $view = 'filament.resources.property-contract-resource.pages.reschedule-payments';

    protected static ?string $title = 'إعادة جدولة دفعات العقد';

    public PropertyContract $record;

    public ?array $data = [];

    public function mount(PropertyContract $record): void
    {
        $this->record = $record;

        // التحقق من الصلاحيات - super_admin, admin, employee
        if (! in_array(auth()->user()?->type, ['super_admin', 'admin', 'employee'])) {
            abort(403, 'غير مصرح لك بإعادة جدولة الدفعات');
        }

        if (! $this->record->canBeRescheduled()) {
            Notification::make()
                ->title('لا يمكن إعادة جدولة هذا العقد')
                ->body('العقد غير نشط أو لا توجد دفعات')
                ->danger()
                ->send();

            $this->redirect(PropertyContractResource::getUrl('index'));

            return;
        }

        $this->form->fill([
            'new_commission_rate' => $this->record->commission_rate,
            'new_frequency' => $this->record->payment_frequency ?? 'monthly',
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return "إعادة جدولة دفعات العقد: {$this->record->contract_number}";
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(null)
                    ->columnspan(2)
                    ->schema([
                        Grid::make(12)->schema([
                            TextInput::make('new_commission_rate')
                                ->label('نسبة العمولة')
                                ->numeric()
                                ->required()
                                ->suffix('%')
                                ->minValue(0)
                                ->maxValue(100)
                                ->columnSpan(3),

                            ...(\App\Filament\Forms\ContractFormSchema::getDurationFields('property', $this->record)),
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
                                ->content($this->record->getPaidMonthsCount().' شهر'),

                            Placeholder::make('remaining_months')
                                ->label('الأشهر المتبقية حالياً')
                                ->content($this->record->getRemainingMonths().' شهر'),
                        ]),

                        Grid::make(2)->schema([
                            Placeholder::make('paid_payments')
                                ->label('الدفعات المدفوعة')
                                ->content($this->record->getPaidPayments()->count().' دفعة'),

                            Placeholder::make('unpaid_payments')
                                ->label('الدفعات الغير مدفوعة')
                                ->content($this->record->getUnpaidPayments()->count().' دفعة (سيتم حذفها)'),
                        ]),
                    ]),

                Section::make('')
                    ->schema([
                        Placeholder::make('ملخص التغييرات')
                            ->label('')
                            ->content(function ($get) {
                                $paidMonths = $this->record->getPaidMonthsCount();
                                $additionalMonths = (int) $get('additional_months');
                                $newTotal = $paidMonths + $additionalMonths;
                                $unpaidCount = $this->record->getUnpaidPayments()->count();

                                $summary = "📊 **الملخص:**\n";
                                $summary .= "• الأشهر المدفوعة: {$paidMonths} شهر (ستبقى كما هي)\n";
                                $summary .= "• الأشهر الجديدة: {$additionalMonths} شهر\n";
                                $summary .= "• إجمالي مدة العقد الجديدة: {$newTotal} شهر\n";
                                $summary .= "• الدفعات الغير مدفوعة: {$unpaidCount} دفعة (سيتم حذفها)\n";

                                $frequency = $get('new_frequency');
                                if ($frequency && PropertyContractService::isValidDuration($additionalMonths, $frequency)) {
                                    $newPaymentsCount = PropertyContractService::calculatePaymentsCount($additionalMonths, $frequency);
                                    $summary .= "• الدفعات الجديدة: {$newPaymentsCount} دفعة\n";
                                }

                                return $summary;
                            }),
                    ])
                    ->visible(fn ($get) => (int) $get('additional_months') > 0),
            ])
            ->columns(2)
            ->statePath('data');
    }

    protected function validateDuration($get, $set)
    {
        $months = (int) $get('additional_months');
        $frequency = $get('new_frequency');

        if (! PropertyContractService::isValidDuration($months, $frequency)) {
            Notification::make()
                ->warning()
                ->title('تنبيه')
                ->body("عدد الاشهر ($months) لا يقبل القسمة علي $frequency")
                ->send();

            $set('frequency_error', true);
        } else {
            $set('frequency_error', false);
        }

        $count = PropertyContractService::calculatePaymentsCount($months, $frequency);
        $set('new_payments_count', $count);
    }

    public function getActions(): array
    {
        return [
            Action::make('reschedule')
                ->label('تنفيذ إعادة الجدولة')
                ->color('success')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->modalHeading('تأكيد إعادة الجدولة')
                ->modalContent(function () {
                    $contractNumber = $this->record->contract_number;
                    $ownerName = $this->record->owner?->name ?? 'غير محدد';
                    $propertyName = $this->record->property?->name ?? 'غير محدد';

                    $newCommission = $this->data['new_commission_rate'] ?? 0;
                    $additionalMonths = $this->data['additional_months'] ?? 0;
                    $newPaymentsCount = $this->data['new_payments_count'] ?? 0;
                    $unpaidCount = $this->record->getUnpaidPayments()->count();

                    return new \Illuminate\Support\HtmlString(
                        "<div style='text-align: right; direction: rtl;'>
                             <p>رقم العقد: <strong>{$contractNumber}</strong></p>
                            <p>المالك: <strong>{$ownerName}</strong></p>
                            <p>العقار: <strong>{$propertyName}</strong></p>
                            <hr style='margin: 10px 0;'>
                            <p style='color: red;'>سيتم حذف: <strong>{$unpaidCount} دفعة غير مدفوعة</strong></p>
                            <p style='color: green;'>سيتم إنشاء: <strong>{$newPaymentsCount} دفعة جديدة</strong></p>
                            <p>العمولة الجديدة: <strong>{$newCommission}%</strong></p>
                            <p>المدة الإضافية: <strong>{$additionalMonths} شهر</strong></p>
                            <hr style='margin: 10px 0;'>
                            <p style='color: #666; font-size: 0.9em;'>هل أنت متأكد من إعادة الجدولة؟</p>
                        </div>"
                    );
                })
                ->modalSubmitActionLabel('نعم، أعد الجدولة')
                ->disabled(fn () => ($this->data['frequency_error'] ?? false) || (($this->data['additional_months'] ?? 0) < 1))
                ->action(function () {
                    // التحقق من أن المدة أكبر من صفر
                    if (($this->data['additional_months'] ?? 0) < 1) {
                        Notification::make()
                            ->title('خطأ')
                            ->body('يجب أن تكون المدة شهر واحد على الأقل')
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->reschedule();
                }),

            Action::make('cancel')
                ->label('إلغاء')
                ->color('gray')
                ->url(PropertyContractResource::getUrl('view', ['record' => $this->record])),
        ];
    }

    public function reschedule()
    {
        $data = $this->data;

        try {
            $service = app(PaymentGeneratorService::class);
            $result = $service->reschedulePropertyContractPayments(
                $this->record,
                $data['new_commission_rate'],
                $data['additional_months'],
                $data['new_frequency']
            );

            Notification::make()
                ->title('تمت إعادة الجدولة بنجاح')
                ->body("تم حذف {$result['deleted_count']} دفعة وإنشاء ".count($result['new_payments']).' دفعة جديدة')
                ->success()
                ->send();

            return redirect()->to(PropertyContractResource::getUrl('view', ['record' => $this->record]));

        } catch (\Exception $e) {
            Notification::make()
                ->title('حدث خطأ أثناء إعادة الجدولة')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
