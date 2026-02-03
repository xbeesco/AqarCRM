<?php

namespace App\Filament\Resources\UnitContractResource\Pages;

use App\Filament\Resources\UnitContractResource;
use App\Models\UnitContract;
use App\Services\PaymentGeneratorService;
use App\Services\PropertyContractService;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Closure;
use App\Filament\Forms\ContractFormSchema;

class RenewContract extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = UnitContractResource::class;

    protected string $view = 'filament.resources.unit-contract-resource.pages.reschedule-payments'; // Reuse the same view

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

        if (!auth()->user()->can('reschedule', $record)) {
            abort(403, 'غير مصرح لك بتجديد العقد');
        }

        // Default: Add 12 months to what's already there
        $this->form->fill([
            'new_monthly_rent' => $record->monthly_rent,
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
                    ->columnspan(2)
                    ->schema([
                        Grid::make(12)->schema([
                            TextInput::make('new_monthly_rent')
                                ->label('قيمة الإيجار في الفترة الجديدة')
                                ->numeric()
                                ->required()
                                ->minValue(0.01)
                                ->step(0.01)
                                ->postfix('ريال')
                                ->columnSpan(3),

                            ...ContractFormSchema::getDurationFields('unit', $this->record)
                        ]),
                    ]),

                Section::make('معلومات العقد الحالي')
                    ->columnspan(1)
                    ->schema([
                        Grid::make(3)->schema([
                            Placeholder::make('original_duration')
                                ->label('المدة الحالية')
                                ->content($this->record->duration_months . ' شهر'),

                            Placeholder::make('paid_months')
                                ->label('الأشهر المدفوعة')
                                ->content(fn() => $this->record->getPaidMonthsCount() . ' شهر'),

                            Placeholder::make('end_date')
                                ->label('تاريخ الانتهاء الحالي')
                                ->content(fn() => $this->record->end_date?->format('Y-m-d') ?? '-'),
                        ]),
                    ]),

                Section::make('ملخص التجديد')
                    ->schema([
                        Placeholder::make('ملخص')
                            ->label('')
                            ->content(function ($get) {
                                $paidMonths = $this->record->getPaidMonthsCount();
                                $totalNewMonths = $get('additional_months') ?? 0;
                                $addedMonths = $totalNewMonths - $this->record->getRemainingMonths();

                                $summary = "🔄 **ملخص التجديد:**\n";
                                $summary .= "• الأشهر المدفوعة مسبقاً: {$paidMonths} شهر\n";
                                $summary .= "• إجمالي المدة المتبقية + الإضافية: {$totalNewMonths} شهر\n";
                                $summary .= "• سيتم تمديد العقد لمدة: " . max(0, $addedMonths) . " شهر إضافي\n";

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
            Action::make('renew')
                ->label('تأكيد التجديد')
                ->color('success')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        $result = $this->paymentService->rescheduleContractPayments(
                            $this->record,
                            $this->data['new_monthly_rent'],
                            $this->data['additional_months'],
                            $this->data['new_frequency']
                        );

                        Notification::make()
                            ->title('تم تجديد العقد بنجاح')
                            ->body("تم تحديث مدة العقد وتوليد الدفعات الجديدة")
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
