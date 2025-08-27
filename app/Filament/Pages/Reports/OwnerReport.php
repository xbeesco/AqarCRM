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

class OwnerReport extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'تقرير الملاك';
    protected static ?string $title = 'تقرير الملاك';
    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';
    protected static ?int $navigationSort = 3;

    public ?int $owner_id = null;
    public ?int $property_id = null;
    public ?int $unit_id = null;
    public string $owner_status = 'all';
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
            ->columns(5);
    }
    
    protected function getFormSchema(): array
    {
        return [
            Select::make('owner_id')
                ->label('المالك')
                ->placeholder('جميع الملاك')
                ->options(function () {
                    return User::where('type', \App\Enums\UserType::OWNER->value)
                        ->orderBy('name')
                        ->pluck('name', 'id');
                })
                ->searchable()
                ->live()
                ->afterStateUpdated(function ($state) {
                    $this->property_id = null;
                    $this->unit_id = null;
                    $this->updateWidgets();
                }),

            Select::make('property_id')
                ->label('العقار')
                ->placeholder('جميع العقارات')
                ->options(function () {
                    $options = [];
                    $propertiesQuery = Property::with('owner');
                    
                    // Filter by selected owner if specified
                    if ($this->owner_id) {
                        $propertiesQuery->where('owner_id', $this->owner_id);
                    }
                    
                    $properties = $propertiesQuery->get();
                    
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

            Select::make('owner_status')
                ->label('حالة المالك')
                ->options([
                    'all' => 'جميع الملاك',
                    'active' => 'نشط',
                    'inactive' => 'غير نشط',
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
            \App\Filament\Widgets\Reports\OwnerStatsWidget::class,
            \App\Filament\Widgets\Reports\OwnerPaymentStatusWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\Reports\OwnerPaymentsTableWidget::class,
        ];
    }

    public function updateWidgets(): void
    {
        $this->dispatch('owner-filters-updated', [
            'owner_id' => $this->owner_id,
            'property_id' => $this->property_id,
            'unit_id' => $this->unit_id,
            'owner_status' => $this->owner_status,
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
        // Get owner_id from URL parameter if provided
        if (request()->has('owner_id')) {
            $this->owner_id = (int) request()->get('owner_id');
        }
        
        $this->form->fill([
            'owner_id' => $this->owner_id,
            'property_id' => $this->property_id,
            'unit_id' => $this->unit_id,
            'owner_status' => $this->owner_status,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ]);
    }

    protected function getViewData(): array
    {
        return [];
    }

    protected string $view = 'filament.pages.reports.owner-report';
}