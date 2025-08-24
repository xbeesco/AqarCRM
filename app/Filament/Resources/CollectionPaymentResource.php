<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CollectionPaymentResource\Pages;
use App\Models\CollectionPayment;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use BackedEnum;

class CollectionPaymentResource extends Resource
{
    protected static ?string $model = CollectionPayment::class;
    protected static ?string $navigationLabel = 'دفعات المستأجرين';
    protected static ?string $modelLabel = 'دفعة مستأجر';
    protected static ?string $pluralModelLabel = 'دفعات المستأجرين';
    // Navigation properties removed - managed centrally in AdminPanelProvider

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('إضافة دفعة تحصيل')
                    ->schema([
                        // العقد فقط - مثل النظام القديم
                        Select::make('unit_contract_id')
                            ->label('العقد')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                return \App\Models\UnitContract::with(['tenant', 'unit', 'property'])
                                    ->get()
                                    ->mapWithKeys(function ($contract) {
                                        $label = sprintf(
                                            '%s - %s - %s',
                                            $contract->tenant?->name ?? 'غير محدد',
                                            $contract->unit?->name ?? 'غير محدد',
                                            $contract->property?->name ?? 'غير محدد'
                                        );
                                        return [$contract->id => $label];
                                    });
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $contract = \App\Models\UnitContract::find($state);
                                    if ($contract) {
                                        $set('unit_id', $contract->unit_id);
                                        $set('property_id', $contract->property_id);
                                        $set('tenant_id', $contract->tenant_id);
                                        $set('amount', $contract->monthly_rent ?? 0); // تغيير من amount_simple إلى amount
                                    }
                                }
                            }),
                        
                        // القيمة المالية - نستخدم الحقل الأصلي
                        TextInput::make('amount')
                            ->label('القيمة المالية')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->prefix('SAR'),
                        
                        // حالة التحصيل
                        Select::make('collection_status')
                            ->label('حالة التحصيل')
                            ->required()
                            ->options(CollectionPayment::getStatusOptions())
                            ->default(CollectionPayment::STATUS_DUE),
                        
                        // بداية التاريخ - نستخدم الحقل الأصلي
                        DatePicker::make('due_date_start')
                            ->label('بداية التاريخ')
                            ->required()
                            ->default(now()->startOfMonth()),
                        
                        // إلى التاريخ - نستخدم الحقل الأصلي
                        DatePicker::make('due_date_end')
                            ->label('إلى التاريخ')
                            ->required()
                            ->default(now()->endOfMonth()),
                        
                        // Hidden fields للحفظ
                        \Filament\Forms\Components\Hidden::make('unit_id'),
                        \Filament\Forms\Components\Hidden::make('property_id'),
                        \Filament\Forms\Components\Hidden::make('tenant_id'),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_number')
                    ->label('رقم الدفعة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unitContract.tenant.name')
                    ->label('المستأجر')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unitContract.unit.name')
                    ->label('الوحدة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unitContract.property.name')
                    ->label('العقار')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('القيمة المالية')
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('collection_status')
                    ->label('حالة التحصيل')
                    ->badge()
                    ->formatStateUsing(fn ($state) => CollectionPayment::getStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        CollectionPayment::STATUS_COLLECTED => 'success',
                        CollectionPayment::STATUS_DUE => 'warning',
                        CollectionPayment::STATUS_POSTPONED => 'info',
                        CollectionPayment::STATUS_OVERDUE => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('due_date_start')
                    ->label('من تاريخ')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('due_date_end')
                    ->label('إلى تاريخ')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('collection_status')
                    ->label('حالة التحصيل')
                    ->options(CollectionPayment::getStatusOptions()),
                
                SelectFilter::make('unit_contract_id')
                    ->label('العقد')
                    ->relationship('unitContract', 'id')
                    ->searchable()
                    ->preload(),
                
                SelectFilter::make('tenant_id')
                    ->label('المستأجر')
                    ->relationship('tenant', 'name')
                    ->searchable(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('process_payment')
                    ->label('Process Payment / تحصيل الدفعة')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(fn (CollectionPayment $record) => $record->paymentStatus->slug === 'worth_collecting')
                    ->form([
                        Select::make('payment_method_id')
                            ->label('Payment Method / طريقة الدفع')
                            ->options(\App\Models\PaymentMethod::active()->pluck('name_ar', 'id'))
                            ->required(),
                        DatePicker::make('paid_date')
                            ->label('Paid Date / تاريخ السداد')
                            ->default(now())
                            ->required(),
                        TextInput::make('payment_reference')
                            ->label('Payment Reference / مرجع الدفعة'),
                    ])
                    ->action(function (CollectionPayment $record, array $data) {
                        $record->processPayment(
                            $data['payment_method_id'],
                            $data['paid_date'],
                            $data['payment_reference'] ?? null
                        );
                    }),
                Action::make('generate_receipt')
                    ->label('Print Receipt / طباعة الإيصال')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->visible(fn (CollectionPayment $record) => $record->paymentStatus->is_paid_status)
                    ->url(fn (CollectionPayment $record) => route('collection-payment.receipt', $record)),
                DeleteAction::make(),
            ])
            ->bulkActions([
                // Bulk actions here
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCollectionPayments::route('/'),
            'create' => Pages\CreateCollectionPayment::route('/create'),
            'view' => Pages\ViewCollectionPayment::route('/{record}'),
            'edit' => Pages\EditCollectionPayment::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::overdue()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}