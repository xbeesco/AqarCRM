<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyContractResource\Pages;
use App\Models\Property;
use App\Models\PropertyContract;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Select as FilterSelect;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PropertyContractResource extends Resource
{
    protected static ?string $model = PropertyContract::class;

    protected static ?string $navigationLabel = 'عقود العقارات';

    protected static ?string $modelLabel = 'عقد العقار';

    protected static ?string $pluralModelLabel = 'عقود العقارات';

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
                            ->getOptionLabelFromRecordUsing(fn($record) => $record->name . ' - ' . $record->owner?->name)
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
                            ->live(onBlur: true)
                            ->rules([
                                'required',
                                'date',
                                fn($get, $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                    $propertyId = $get('property_id');
                                    if (!$propertyId || !$value) {
                                        return;
                                    }

                                    $validationService = app(\App\Services\PropertyContractValidationService::class);
                                    $excludeId = $record ? $record->id : null;

                                    // التحقق من تاريخ البداية فقط
                                    $error = $validationService->validateStartDate($propertyId, $value, $excludeId);
                                    if ($error) {
                                        $fail($error);
                                    }
                                },
                            ])
                            ->columnSpan(3),

                        ...(\App\Filament\Forms\ContractFormSchema::getDurationFields('property')),

                        FileUpload::make('file')
                            ->label('ملف العقد')
                            ->required()
                            ->directory('property-contract--file')
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
                TextColumn::make('owner.name')
                    ->label('اسم المالك')
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        return $record->property?->owner?->name ?? '-';
                    }),

                TextColumn::make('property.name')
                    ->label('العقار')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('تاريخ البداية')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('duration_months')
                    ->label('المدة')
                    ->suffix(' شهر')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('تاريخ الانتهاء')
                    ->date('Y-m-d'),

                TextColumn::make('payment_frequency')
                    ->label('نوع التوريد')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'monthly' => 'شهري',
                        'quarterly' => 'ربع سنوي',
                        'semi_annually' => 'نصف سنوي',
                        'annually' => 'سنوي',
                        default => $state
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'monthly' => 'success',
                        'quarterly' => 'info',
                        'semi_annually' => 'warning',
                        'annually' => 'danger',
                        default => 'gray'
                    }),

                TextColumn::make('commission_rate')
                    ->label('النسبة المتفق عليها')
                    ->suffix('%'),
            ])
            ->filters([
                SelectFilter::make('property_id')
                    ->label('العقار')
                    ->relationship('property', 'name')
                    ->searchable(),

                Filter::make('owner')
                    ->label('المالك')
                    ->form([
                        FilterSelect::make('owner_id')
                            ->label('المالك')
                            ->options(function () {
                                return \App\Models\User::where('type', 'owner')
                                    ->pluck('name', 'id');
                            })
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['owner_id'],
                            fn(Builder $query, $value): Builder => $query->whereHas('property', function ($q) use ($value) {
                                $q->where('owner_id', $value);
                            })
                        );
                    }),
            ])
            ->recordActions([
                Action::make('generatePayments')
                    ->label('توليد الدفعات')
                    ->icon('heroicon-o-calculator')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('توليد دفعات التوريد')
                    ->modalDescription(function ($record) {
                        $paymentsCount = $record->payments_count;
                        $ownerName = $record->owner?->name ?? 'غير محدد';
                        $propertyName = $record->property?->name ?? 'غير محدد';
                        $contractNumber = $record->contract_number ?? 'غير محدد';

                        return new \Illuminate\Support\HtmlString(
                            "<div style='text-align: right; direction: rtl;'>
                                <p>رقم العقد: <strong>{$contractNumber}</strong></p>
                                <p>العقار: <strong>{$propertyName}</strong></p>
                                <p>المالك: <strong>{$ownerName}</strong></p>
                                <hr style='margin: 10px 0;'>
                                <p>سيتم توليد: <strong style='color: green;'>{$paymentsCount} دفعة</strong></p>
                            </div>"
                        );
                    })
                    ->modalSubmitActionLabel('توليد')
                    ->visible(fn($record) => $record->canGeneratePayments())
                    ->action(function ($record) {
                        $service = app(\App\Services\PaymentGeneratorService::class);

                        try {
                            $count = $service->generateSupplyPaymentsForContract($record);

                            \Filament\Notifications\Notification::make()
                                ->title('تم توليد الدفعات')
                                ->body('تم تصفية المستحقات والنفقات لهذا الشهر')
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
                    ->url(fn($record) => route('filament.admin.resources.supply-payments.index', [
                        'property_contract_id' => $record->id,
                    ]))
                    ->visible(fn($record) => $record->supplyPayments()->exists()),

                Action::make('reschedule')
                    ->label('إعادة جدولة')
                    ->icon('heroicon-m-calendar-days')
                    ->color('warning')
                    ->url(fn(PropertyContract $record): string => PropertyContractResource::getUrl('reschedule', ['record' => $record]))
                    ->visible(fn(PropertyContract $record) => $record->canBeRescheduled() && auth()->user()->isSuperAdmin()),

                Action::make('renewContract')
                    ->label('تجديد العقد')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->url(fn(PropertyContract $record): string => PropertyContractResource::getUrl('renew', ['record' => $record]))
                    ->visible(fn(PropertyContract $record) => $record->canBeRescheduled() && auth()->user()->isSuperAdmin()),

                // EditAction::make()
                //     ->label('تعديل')
                //     ->icon('heroicon-o-pencil-square')
                //     ->visible(fn() => auth()->user()?->type === 'super_admin'),
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
            'edit' => Pages\EditPropertyContract::route('/{record}/edit'),
            'reschedule' => Pages\ReschedulePayments::route('/{record}/reschedule'),
            'renew' => Pages\RenewContract::route('/{record}/renew'),
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
