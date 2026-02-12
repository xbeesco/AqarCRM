<?php

namespace App\Filament\Resources\UnitContracts\Tables;

use App\Models\User;
use App\Services\PaymentGeneratorService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class UnitContractsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['tenant', 'unit', 'property']);
            })
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
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('duration_months')
                    ->label('المدة')
                    ->suffix(' شهر'),

                TextColumn::make('end_date')
                    ->label('نهاية العقد')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('payment_frequency')
                    ->label('نوع التحصيل')
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

                TextColumn::make('monthly_rent')
                    ->label('الايجار الشهري')
                    ->money('SAR', 1, null, 0),
            ])
            ->filters([
                SelectFilter::make('owner_id')
                    ->label('المالك')
                    ->options(function () {
                        return User::where('type', 'owner')
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (! empty($data['value'])) {
                            return $query->whereHas('property', function ($q) use ($data) {
                                $q->where('owner_id', $data['value']);
                            });
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! empty($data['value'])) {
                            $owner = User::find($data['value']);

                            return $owner ? 'المالك: '.$owner->name : null;
                        }

                        return null;
                    })
                    ->searchable()
                    ->preload(),

                SelectFilter::make('property_id')
                    ->label('العقار')
                    ->relationship('property', 'name')
                    ->searchable()
                    ->indicateUsing(function (array $data): ?string {
                        if (! empty($data['value'])) {
                            $property = \App\Models\Property::find($data['value']);

                            return $property ? 'العقار: '.$property->name : null;
                        }

                        return null;
                    }),

                SelectFilter::make('unit_id')
                    ->label('الوحدة')
                    ->relationship('unit', 'name')
                    ->searchable()
                    ->indicateUsing(function (array $data): ?string {
                        if (! empty($data['value'])) {
                            $unit = \App\Models\Unit::find($data['value']);

                            return $unit ? 'الوحدة: '.$unit->name : null;
                        }

                        return null;
                    }),

                SelectFilter::make('tenant_id')
                    ->label('المستأجر')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->indicateUsing(function (array $data): ?string {
                        if (! empty($data['value'])) {
                            $tenant = User::find($data['value']);

                            return $tenant ? 'المستأجر: '.$tenant->name : null;
                        }

                        return null;
                    }),

                SelectFilter::make('payment_frequency')
                    ->label('سداد الدفعات')
                    ->options([
                        'monthly' => 'شهري',
                        'quarterly' => 'ربع سنوي',
                        'semi_annually' => 'نصف سنوي',
                        'annually' => 'سنوي',
                    ]),
            ])
            ->recordActions([
                Action::make('viewPayments')
                    ->label('عرض الدفعات')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => $record ? route('filament.admin.resources.collection-payments.index', [
                        'unit_contract_id' => $record->id,
                    ]) : '#')
                    ->visible(fn ($record) => $record && $record->collectionPayments()->exists()),
                Action::make('generatePayments')
                    ->label('توليد الدفعات')
                    ->icon('heroicon-o-calculator')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('توليد دفعات التحصيل')
                    ->modalDescription(function ($record) {
                        if (! $record) {
                            return '';
                        }

                        $paymentsCount = $record->payments_count;
                        $tenantName = $record->tenant?->name ?? 'غير محدد';
                        $propertyName = $record->property?->name ?? 'غير محدد';
                        $unitName = $record->unit?->name ?? 'غير محدد';
                        $contractNumber = $record->contract_number ?? 'غير محدد';

                        return new HtmlString(
                            "<div style='text-align: right; direction: rtl;'>
                                <p>رقم العقد: <strong>{$contractNumber}</strong></p>
                                <p>المستأجر: <strong>{$tenantName}</strong></p>
                                <p>العقار: <strong>{$propertyName}</strong></p>
                                <p>الوحدة: <strong>{$unitName}</strong></p>
                                <hr style='margin: 10px 0;'>
                                <p>سيتم توليد: <strong style='color: green;'>{$paymentsCount} دفعة</strong></p>
                            </div>"
                        );
                    })
                    ->modalSubmitActionLabel('توليد')
                    ->visible(fn ($record) => $record && $record->canGeneratePayments())
                    ->action(function ($record) {
                        try {
                            $paymentService = app(PaymentGeneratorService::class);
                            $payments = $paymentService->generateTenantPayments($record);
                            $count = count($payments);

                            Notification::make()
                                ->title('تم توليد الدفعات بنجاح')
                                ->body("تم توليد {$count} دفعة للعقد رقم {$record->contract_number}")
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
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
                    ->visible(fn ($record) => $record && $record->canReschedule()),

                Action::make('renewContract')
                    ->label('تجديد العقد')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->url(fn ($record) => $record ? route('filament.admin.resources.unit-contracts.renew', $record) : '#')
                    ->visible(fn ($record) => $record && auth()->user()?->can('renew', $record)),

                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square')->visible(fn () => auth()->user()?->type === 'super_admin'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
