<?php

namespace App\Filament\Resources\PropertyContracts\Tables;

use App\Models\User;
use App\Services\PaymentGeneratorService;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class PropertyContractsTable
{
    public static function configure(Table $table): Table
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
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'monthly' => 'شهري',
                        'quarterly' => 'ربع سنوي',
                        'semi_annually' => 'نصف سنوي',
                        'annually' => 'سنوي',
                        default => $state
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
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
                    ->schema([
                        Select::make('owner_id')
                            ->label('المالك')
                            ->options(function () {
                                return User::where('type', 'owner')
                                    ->pluck('name', 'id');
                            })
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['owner_id'],
                            fn (Builder $query, $value): Builder => $query->whereHas('property', function ($q) use ($value) {
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

                        return new HtmlString(
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
                    ->visible(fn ($record) => $record->canGeneratePayments())
                    ->action(function ($record) {
                        $service = app(PaymentGeneratorService::class);

                        try {
                            $count = $service->generateSupplyPaymentsForContract($record);

                            Notification::make()
                                ->title('تم توليد الدفعات')
                                ->body('تم تصفية المستحقات والنفقات لهذا الشهر')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
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
                        'property_contract_id' => $record->id,
                    ]))
                    ->visible(fn ($record) => $record->supplyPayments()->exists()),

                Action::make('reschedulePayments')
                    ->label('جدولة الدفعات')
                    ->icon('heroicon-o-calendar')
                    ->color('warning')
                    ->url(fn ($record) => $record ? route('filament.admin.resources.property-contracts.reschedule', $record) : '#')
                    ->visible(fn ($record) => $record && $record->canReschedule()),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
