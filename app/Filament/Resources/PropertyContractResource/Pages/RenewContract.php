<?php

namespace App\Filament\Resources\PropertyContractResource\Pages;

use App\Filament\Resources\PropertyContractResource;
use App\Models\PropertyContract;
use App\Services\PaymentGeneratorService;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\Forms\ContractFormSchema;

class RenewContract extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = PropertyContractResource::class;

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

        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'غير مصرح لك بتجديد العقد');
        }

        $this->form->fill([
            'new_commission_rate' => $record->commission_rate,
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
                    ->columnspan(2)
                    ->schema([
                        Grid::make(12)->schema([
                            TextInput::make('new_commission_rate')
                                ->label('نسبة العموله في الفترة الجديدة')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100)
                                ->suffix('%')
                                ->columnSpan(3),

                            ...ContractFormSchema::getDurationFields('property', $this->record)
                        ]),
                    ]),

                Section::make('معلومات العقد الحالي')
                    ->columnspan(1)
                    ->schema([
                        Grid::make(2)->schema([
                            Placeholder::make('original_duration')
                                ->label('المدة الحالية')
                                ->content($this->record->duration_months . ' شهر'),

                            Placeholder::make('end_date')
                                ->label('تاريخ الانتهاء الحالي')
                                ->content(fn() => $this->record->end_date?->format('Y-m-d') ?? '-'),
                        ]),
                    ]),
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
                        $result = $this->paymentService->reschedulePropertyContractPayments(
                            $this->record,
                            $this->data['new_commission_rate'],
                            $this->data['additional_months'],
                            $this->data['new_frequency']
                        );

                        Notification::make()
                            ->title('تم تجديد العقد بنجاح')
                            ->body("تم تحديث مدة العقد وتوليد دفعات التوريد الجديدة")
                            ->success()
                            ->send();

                        return redirect()->route('filament.admin.resources.property-contracts.view', $this->record);

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
                ->url(route('filament.admin.resources.property-contracts.view', $this->record)),
        ];
    }
}
