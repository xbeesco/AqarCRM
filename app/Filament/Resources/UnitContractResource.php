<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitContractResource\Pages;
use App\Models\UnitContract;
use App\Models\User;
use App\Models\Property;
use App\Models\Unit;
use App\Services\UnitContractService;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Closure;

class UnitContractResource extends Resource
{
    protected static ?string $model = UnitContract::class;

    protected static ?string $navigationLabel = 'عقود الوحدات';

    protected static ?string $modelLabel = 'عقد وحدة';

    protected static ?string $pluralModelLabel = 'عقود الوحدات';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('بيانات العقد')
                    ->schema([

                        Select::make('property_id')
                            ->label('العقار')
                            ->required()
                            ->searchable()
                            ->relationship('property', 'name')
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state) {
                                // Clear unit selection when property changes
                                $set('unit_id', null);
                                
                                // Auto-select unit if only one unit exists
                                if ($state) {
                                    $units = Unit::where('property_id', $state)->get();
                                    if ($units->count() === 1) {
                                        $unit = $units->first();
                                        $set('unit_id', $unit->id);
                                        $set('monthly_rent', $unit->rent_price ?? 0);
                                    }
                                }
                            })
                            ->columnSpan(3),

                        Select::make('unit_id')
                            ->label('الوحدة')
                            ->required()
                            ->native(true)
                            ->placeholder('اختر وحدة')
                            ->options(function (callable $get) {
                                $propertyId = $get('property_id');
                                
                                if (!$propertyId) {
                                    return [];
                                }
                                
                                // Get all units for the property immediately without search
                                return Unit::where('property_id', $propertyId)
                                    ->pluck('name', 'id');
                            })
                            ->searchable(false) // Disable search to show all options immediately
                            ->disabled(fn (callable $get): bool => !$get('property_id'))
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state) {
                                    $unit = Unit::find($state);
                                    if ($unit) {
                                        $set('monthly_rent', $unit->rent_price ?? 0);
                                    }
                                }
                            })
                            ->columnSpan(3),

                        TextInput::make('monthly_rent')
                            ->label(label: 'قيمة الإيجار بالشهر')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->postfix('ريال')
                            ->columnSpan(3),

                        Select::make('tenant_id')
                            ->label('المستأجر')
                            ->required()
                            ->searchable()
                            ->relationship('tenant', 'name')
                            ->options(User::where('type', 'tenant')->pluck('name', 'id'))
                            ->columnSpan(3),
                        DatePicker::make('start_date')
                            ->label('تاريخ بداية العمل بالعقد')
                            ->required()
                            ->default(now())
                            ->live(onBlur: true)
                            ->rules([
                                'required',
                                'date',
                                fn ($get, $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                    $unitId = $get('unit_id');
                                    if (!$unitId || !$value) {
                                        return;
                                    }
                                    
                                    $validationService = app(\App\Services\ContractValidationService::class);
                                    $excludeId = $record ? $record->id : null;
                                    
                                    // التحقق من تاريخ البداية فقط
                                    $error = $validationService->validateStartDate($unitId, $value, $excludeId);
                                    if ($error) {
                                        $fail($error);
                                    }
                                },
                            ])
                            ->validationAttribute('تاريخ البداية')
                            ->columnSpan(3),

                        TextInput::make('duration_months')
                            ->label('مدة التعاقد بالشهر')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('شهر')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $get, $set) {
                                $frequency = $get('payment_frequency') ?? 'monthly';
                                $count = \App\Services\PropertyContractService::calculatePaymentsCount($state ?? 0, $frequency);
                                $set('payments_count', $count);
                            })
                            ->rules([
                                fn ($get, $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                    $frequency = $get('payment_frequency') ?? 'monthly';
                                    if (!\App\Services\PropertyContractService::isValidDuration($value ?? 0, $frequency)) {
                                        $periodName = match($frequency) {
                                            'quarterly' => 'ربع سنة',
                                            'semi_annually' => 'نصف سنة',
                                            'annually' => 'سنة',
                                            default => $frequency,
                                        };
                                        
                                        $fail("عدد الاشهر هذا لا يقبل القسمة علي {$periodName}");
                                    }
                                    
                                    // التحقق من المدة فقط
                                    $unitId = $get('unit_id');
                                    $startDate = $get('start_date');
                                    
                                    if ($unitId && $startDate && $value) {
                                        $validationService = app(\App\Services\ContractValidationService::class);
                                        $excludeId = $record ? $record->id : null;
                                        
                                        // التحقق من المدة وتأثيرها على النهاية
                                        $error = $validationService->validateDuration($unitId, $startDate, $value, $excludeId);
                                        if ($error) {
                                            $fail($error);
                                        }
                                    }
                                },
                            ])
                            ->validationAttribute('مدة التعاقد')
                            ->columnSpan(3),

                        Select::make('payment_frequency')
                            ->label('التحصيل كل')
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
                                $duration = $get('duration_months') ?? 0;
                                $count = \App\Services\PropertyContractService::calculatePaymentsCount($duration, $state ?? 'monthly');
                                $set('payments_count', $count);
                            })
                            ->rules([
                                fn ($get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $duration = $get('duration_months') ?? 0;
                                    if (!\App\Services\PropertyContractService::isValidDuration($duration, $value ?? 'monthly')) {
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
                        TextInput::make('payments_count')
                            ->label('عدد الدفعات')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(function ($get) {
                                $duration = $get('duration_months') ?? 0;
                                $frequency = $get('payment_frequency') ?? 'monthly';
                                $result = \App\Services\PropertyContractService::calculatePaymentsCount($duration, $frequency);
                                return $result;
                            })
                            ->columnSpan(3),
                        
                            FileUpload::make('contract_file')
                            ->label('ملف العقد')
                            ->required()
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->disk('public')
                            ->directory('unit-contracts')
                            ->preserveFilenames()
                            ->maxSize(10240)
                            ->columnSpan(6),

                        Textarea::make('notes')
                            ->label('الملاحظات')
                            ->rows(3)
                            ->columnSpan(6),
                    ])
                    ->columns(12)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')
                    ->label('اسم المستأجر')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unit.name')
                    ->label('الوحدة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('property.name')
                    ->label('العقار')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('بداية العقد')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('duration_months')
                    ->label('المدة')
                    ->suffix(' شهر'),

                TextColumn::make('end_date')
                    ->label('نهاية العقد')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('payment_frequency')
                    ->label('نوع التحصيل')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'monthly' => 'شهري',
                        'quarterly' => 'ربع سنوي',
                        'semi_annually' => 'نصف سنوي',
                        'annually' => 'سنوي',
                        default => $state
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'monthly' => 'success',
                        'quarterly' => 'info',
                        'semi_annually' => 'warning',
                        'annually' => 'danger',
                        default => 'gray'
                    }),

                TextColumn::make('monthly_rent')
                    ->label('الايجار الشهري')
                    ->money('SAR' , 1 , null , 0 ),
            ])
            ->filters([
                SelectFilter::make('property_id')
                    ->label('العقار')
                    ->relationship('property', 'name')
                    ->searchable(),

                SelectFilter::make('unit_id')
                    ->label('الوحدة')
                    ->relationship('unit', 'name')
                    ->searchable(),

                SelectFilter::make('tenant_id')
                    ->label('المستأجر')
                    ->relationship('tenant', 'name')
                    ->searchable(),

                SelectFilter::make('payment_frequency')
                    ->label('سداد الدفعات')
                    ->options([
                        'monthly' => 'شهري',
                        'quarterly' => 'ربع سنوي',
                        'semi_annually' => 'نصف سنوي',
                        'annually' => 'سنوي',
                    ]),
            ])
            //->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->recordActions([
                Action::make('viewPayments')
                    ->label('عرض الدفعات')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => $record ? route('filament.admin.resources.collection-payments.index', [
                        'unit_contract_id' => $record->id
                    ]) : '#')
                    ->visible(fn ($record) => $record && $record->payments()->exists()),
                Action::make('generatePayments')
                    ->label('توليد الدفعات')
                    ->icon('heroicon-o-calculator')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('توليد دفعات التحصيل')
                    ->modalDescription(fn ($record) => $record ? "سيتم توليد {$record->payments_count} دفعة للمستأجر" : '')
                    ->modalSubmitActionLabel('توليد')
                    ->visible(fn ($record) => $record && $record->canGeneratePayments())
                    ->action(function ($record) {
                        try {
                            $paymentService = app(\App\Services\PaymentGeneratorService::class);
                            $payments = $paymentService->generateTenantPayments($record);
                            $count = count($payments);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('تم توليد الدفعات بنجاح')
                                ->body("تم توليد {$count} دفعة للعقد رقم {$record->contract_number}")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('فشل توليد الدفعات')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('reschedulePayments')
                    ->label('جدولة الدفعات')
                    ->icon('heroicon-o-calendar')
                    ->color('warning')
                    ->url(fn ($record) => $record ? route('filament.admin.resources.unit-contracts.reschedule', $record) : '#')
                    ->visible(fn ($record) => $record && $record->canReschedule() && auth()->user()?->type === 'super_admin'),
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square')->visible(fn () => auth()->user()?->type === 'super_admin'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnitContracts::route('/'),
            'create' => Pages\CreateUnitContract::route('/create'),
            'view' => Pages\ViewUnitContracts::route('/{record}'),
            'edit' => Pages\EditUnitContract::route('/{record}/edit'), // Only accessible by super_admin
            'reschedule' => Pages\ReschedulePayments::route('/{record}/reschedule'), // Only accessible by super_admin
        ];
    }
    
    /**
     * Only super_admin can edit contracts
     */
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        return $user && $user->type === 'super_admin';
    }
    
    /**
     * Only super_admin can delete contracts
     */
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        return $user && $user->type === 'super_admin';
    }
    
    /**
     * Only admins and employees can create contracts
     */
    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->type, ['super_admin', 'admin', 'employee']);
    }
    
    /**
     * Filter records based on user type
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        
        if ($user) {
            switch ($user->type) {
                case 'owner':
                    // Owners see contracts for their properties only
                    return $query->whereHas('property', function ($q) use ($user) {
                        $q->where('owner_id', $user->id);
                    });
                    
                case 'tenant':
                    // Tenants see only their own contracts
                    return $query->where('tenant_id', $user->id);
            }
        }
        
        return $query;
    }
}