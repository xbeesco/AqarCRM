<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use App\Models\User;
use App\Models\Property;
use App\Models\Unit;
use App\Enums\UserType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\IconPosition;

class TenantReport extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'تقرير المستأجرين';
    protected static ?string $title = 'تقرير المستأجرين';
    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';
    protected static ?int $navigationSort = 2;

    public ?int $property_id = null;
    public ?int $unit_id = null;
    public string $tenant_status = 'all';
    public ?string $date_from = null;
    public ?string $date_to = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('طباعة')
                ->icon('heroicon-o-printer')
                ->iconPosition(IconPosition::Before)
                ->color('primary')
                ->extraAttributes([
                    'onclick' => 'window.print(); return false;',
                ]),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema($this->getFormSchema())
            ->columns(4);
    }
    
    protected function getFormSchema(): array
    {
        return [
            Select::make('property_id')
                ->label('العقار')
                ->placeholder('جميع العقارات')
                ->options(function () {
                    $options = [];
                    $properties = Property::with('owner')->get();
                    
                    foreach ($properties as $property) {
                        $propertyName = (string) ($property->name ?: 'عقار #' . $property->id);
                        $ownerName = (string) (optional($property->owner)->name ?: 'بدون مالك');
                        $label = $propertyName . ' - ' . $ownerName;
                        
                        if (!empty($label)) {
                            $options[(string) $property->id] = $label;
                        }
                    }
                    
                    return $options;
                })
                ->live()
                ->afterStateUpdated(function ($state) {
                    $this->unit_id = null;
                    $this->updateWidgets();
                }),

            Select::make('unit_id')
                ->label('الوحدة')
                ->placeholder('جميع الوحدات')
                ->options(function () {
                    if (!$this->property_id) {
                        return Unit::all()->pluck('name', 'id');
                    }
                    return Unit::where('property_id', $this->property_id)->pluck('name', 'id');
                })
                ->live()
                ->afterStateUpdated(fn () => $this->updateWidgets()),

            Select::make('tenant_status')
                ->label('حالة المستأجر')
                ->options([
                    'all' => 'جميع المستأجرين',
                    'active' => 'نشط',
                    'expired' => 'منتهي العقد',
                    'defaulter' => 'متعثر',
                ])
                ->default('all')
                ->live()
                ->afterStateUpdated(fn () => $this->updateWidgets()),

            DatePicker::make('date_from')
                ->label('من تاريخ')
                ->live()
                ->afterStateUpdated(fn () => $this->updateWidgets()),

            DatePicker::make('date_to')
                ->label('إلى تاريخ')
                ->live()
                ->afterStateUpdated(fn () => $this->updateWidgets()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\Reports\TenantStatsWidget::class,
            \App\Filament\Widgets\Reports\CollectionStatusWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\Reports\TenantPaymentsTableWidget::class,
        ];
    }

    public function updateWidgets(): void
    {
        $this->dispatch('tenant-filters-updated', [
            'property_id' => $this->property_id,
            'unit_id' => $this->unit_id,
            'tenant_status' => $this->tenant_status,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ]);
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        
        $userType = UserType::tryFrom($user->type);
        if (!$userType) {
            return false;
        }
        
        return in_array($userType, [
            UserType::SUPER_ADMIN,
            UserType::ADMIN,
            UserType::EMPLOYEE,
        ]);
    }

    public function mount(): void
    {
        $this->form->fill([
            'property_id' => $this->property_id,
            'unit_id' => $this->unit_id,
            'tenant_status' => $this->tenant_status,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ]);
    }

    protected function getViewData(): array
    {
        return [];
    }

    protected string $view = 'filament.pages.reports.tenant-report';
}