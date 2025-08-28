<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyContractResource\Pages;
use App\Models\PropertyContract;
use App\Models\Property;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Closure;
class PropertyContractResource extends Resource
{
    protected static ?string $model = PropertyContract::class;

    protected static ?string $navigationLabel = 'عقود الملاك';

    protected static ?string $modelLabel = 'عقد المالك';

    protected static ?string $pluralModelLabel = 'عقود الملاك';

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
                            ->options(Property::with('owner')->get()->pluck('name', 'id'))
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name . ' - ' . $record->owner?->name)
                            ->columnSpan(6),

                        TextInput::make('commission_rate')
                            ->label('النسبة المئوية')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->columnSpan(6),

                        DatePicker::make('start_date')
                            ->label('تاريخ بداية العمل بالعقد')
                            ->required()
                            ->default(now())
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
                                fn ($get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
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
                                },
                            ])
                            ->validationAttribute('مدة التعاقد')
                            ->columnSpan(3),

                        Select::make('payment_frequency')
                            ->label('التوريد كل')
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
                                $duration = $get('duration_months') ?? 0;
                                $count = \App\Services\PropertyContractService::calculatePaymentsCount($duration, $state ?? 'monthly');
                                $set('payments_count', $count);
                            })
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
                            ->directory('contract-files')
                            ->preserveFilenames()
                            ->maxSize(10240) // 10MB
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
                TextColumn::make('id')
                    ->label('م')
                    ->searchable()
                    ->sortable()
                    ->width('60px'),

                TextColumn::make('contract_number')
                    ->label('اسم العقد')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('owner.name')
                    ->label('اسم المالك')
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        return $record->property?->owner?->name ?? '-';
                    }),

                TextColumn::make('duration_months')
                    ->label('المدة')
                    ->suffix(' شهر')
                    ->searchable()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('property.name')
                    ->label('العقار')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('تاريخ الانتهاء')
                    ->date('d/m/Y')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('commission_rate')
                    ->label('النسبة المتفق عليها')
                    ->suffix('%')
                    ->searchable()
                    ->sortable()
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('property_id')
                    ->label('العقار')
                    ->relationship('property', 'name')
                    ->searchable(),

                SelectFilter::make('payment_frequency')
                    ->label('تكرار السداد')
                    ->options([
                        'monthly' => 'شهري',
                        'quarterly' => 'ربع سنوي',
                        'four_monthly' => 'ثلث سنوي',
                        'semi_annually' => 'نصف سنوي',
                        'annually' => 'سنوي',
                    ]),
            ])
            ->recordActions([
                Action::make('generatePayments')
                    ->label('توليد الدفعات')
                    ->icon('heroicon-o-calculator')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('توليد دفعات التوريد')
                    ->modalDescription(fn ($record) => "سيتم توليد {$record->payments_count} دفعة للمالك")
                    ->modalSubmitActionLabel('توليد')
                    ->visible(fn ($record) => $record->canGeneratePayments())
                    ->action(function ($record) {
                        $service = app(\App\Services\PaymentGeneratorService::class);
                        
                        try {
                            $count = $service->generateSupplyPaymentsForContract($record);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('تم توليد الدفعات')
                                ->body("تم توليد {$count} دفعة")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('خطأ')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                    
                Action::make('viewPayments')
                    ->label('عرض الدفعات')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => route('filament.admin.resources.supply-payments.index', [
                        'property_contract_id' => $record->id
                    ]))
                    ->visible(fn ($record) => $record->supplyPayments()->exists()),
                    
                EditAction::make()
                    ->visible(fn () => auth()->user()?->type === 'super_admin'),
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
            'index' => Pages\ListPropertyContracts::route('/'),
            'create' => Pages\CreatePropertyContract::route('/create'),
            'view' => Pages\ViewPropertyContract::route('/{record}'),
            'edit' => Pages\EditPropertyContract::route('/{record}/edit'), // Only accessible by super_admin
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
     * Only admins can create contracts
     */
    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->type, ['super_admin', 'admin']);
    }
    
    /**
     * Filter records based on user type
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        
        if ($user && $user->type === 'owner') {
            // Owners can only see their own contracts
            return $query->where('owner_id', $user->id);
        }
        
        return $query;
    }
}