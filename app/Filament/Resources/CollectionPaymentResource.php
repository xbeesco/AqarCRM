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
use App\Models\PaymentStatus;
use App\Models\PaymentMethod;
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
                Section::make('Payment Information / معلومات الدفعة')
                    ->schema([
                        Select::make('unit_contract_id')
                            ->label('Unit Contract / عقد الوحدة')
                            ->relationship('unitContract', 'contract_number')
                            ->searchable()
                            ->required()
                            ->reactive(),

                        Select::make('unit_id')
                            ->label('Unit / الوحدة')
                            ->relationship('unit', 'unit_number')
                            ->searchable()
                            ->required(),

                        Select::make('property_id')
                            ->label('Property / العقار')
                            ->relationship('property', 'name')
                            ->searchable()
                            ->required(),

                        Select::make('tenant_id')
                            ->label('Tenant / المستأجر')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->required(),
                    ])->columns(2),

                Section::make('Payment Details / تفاصيل الدفعة')
                    ->schema([
                        TextInput::make('payment_number')
                            ->label('Payment Number / رقم الدفعة')
                            ->disabled()
                            ->dehydrated(true),

                        TextInput::make('amount')
                            ->label('Amount / المبلغ')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('SAR')
                            ->required(),

                        TextInput::make('late_fee')
                            ->label('Late Fee / غرامة التأخير')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('SAR')
                            ->default(0.00),

                        TextInput::make('total_amount')
                            ->label('Total Amount / الإجمالي')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated(true),
                    ])->columns(2),

                Section::make('Due Dates / تواريخ الاستحقاق')
                    ->schema([
                        DatePicker::make('due_date_start')
                            ->label('Due Date Start / تاريخ بداية الاستحقاق')
                            ->required()
                            ->displayFormat('d/m/Y'),

                        DatePicker::make('due_date_end')
                            ->label('Due Date End / تاريخ نهاية الاستحقاق')
                            ->required()
                            ->displayFormat('d/m/Y'),

                        TextInput::make('month_year')
                            ->label('Month Year / الشهر والسنة')
                            ->placeholder('YYYY-MM')
                            ->required(),
                    ])->columns(3),

                Section::make('Payment Status / حالة الدفعة')
                    ->schema([
                        Select::make('payment_status_id')
                            ->label('Payment Status / حالة الدفعة')
                            ->relationship('paymentStatus', 'name_ar')
                            ->required()
                            ->reactive(),

                        Select::make('payment_method_id')
                            ->label('Payment Method / طريقة الدفع')
                            ->relationship('paymentMethod', 'name_ar')
                            ->searchable()
                            ->visible(fn ($get) => $get('payment_status_id') == PaymentStatus::COLLECTED),

                        DatePicker::make('paid_date')
                            ->label('Paid Date / تاريخ السداد')
                            ->displayFormat('d/m/Y')
                            ->visible(fn ($get) => $get('payment_status_id') == PaymentStatus::COLLECTED),

                        TextInput::make('payment_reference')
                            ->label('Payment Reference / مرجع الدفعة')
                            ->visible(fn ($get) => $get('payment_status_id') == PaymentStatus::COLLECTED),
                    ])->columns(2),

                Section::make('Delay Information / معلومات التأخير')
                    ->schema([
                        TextInput::make('delay_duration')
                            ->label('Delay Duration (Days) / مدة التأخير (أيام)')
                            ->numeric()
                            ->suffix('days')
                            ->visible(fn ($get) => in_array($get('payment_status_id'), [PaymentStatus::DELAYED, PaymentStatus::OVERDUE])),

                        Textarea::make('delay_reason')
                            ->label('Delay Reason / سبب التأخير')
                            ->rows(3)
                            ->visible(fn ($get) => in_array($get('payment_status_id'), [PaymentStatus::DELAYED, PaymentStatus::OVERDUE])),

                        Textarea::make('late_payment_notes')
                            ->label('Late Payment Notes / ملاحظات تجاوز فترة الدفع')
                            ->rows(3),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_number')
                    ->label('Payment Number / رقم الدفعة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('property.name')
                    ->label('Property / العقار')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unit.unit_number')
                    ->label('Unit / الوحدة')
                    ->sortable(),

                TextColumn::make('tenant.name')
                    ->label('Tenant / المستأجر')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Amount / المبلغ')
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('late_fee')
                    ->label('Late Fee / غرامة التأخير')
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total / الإجمالي')
                    ->money('SAR')
                    ->sortable(),

                BadgeColumn::make('paymentStatus.name_ar')
                    ->label('Status / الحالة')
                    ->colors([
                        'warning' => 'worth_collecting',
                        'success' => 'collected',
                        'info' => 'delayed',
                        'danger' => 'overdue',
                    ]),

                TextColumn::make('due_date_end')
                    ->label('Due Date / تاريخ الاستحقاق')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('paid_date')
                    ->label('Paid Date / تاريخ السداد')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filters([
                SelectFilter::make('property_id')
                    ->label('Property / العقار')
                    ->relationship('property', 'name')
                    ->multiple(),

                SelectFilter::make('payment_status_id')
                    ->label('Status / الحالة')
                    ->relationship('paymentStatus', 'name_ar')
                    ->multiple(),

                Filter::make('due_date_range')
                    ->label('Due Date Range / فترة الاستحقاق')
                    ->form([
                        DatePicker::make('due_date_from')
                            ->label('From / من'),
                        DatePicker::make('due_date_to')
                            ->label('To / إلى'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['due_date_from'], 
                                fn ($q, $date) => $q->where('due_date_end', '>=', $date))
                            ->when($data['due_date_to'], 
                                fn ($q, $date) => $q->where('due_date_end', '<=', $date));
                    }),

                SelectFilter::make('payment_method_id')
                    ->label('Payment Method / طريقة الدفع')
                    ->relationship('paymentMethod', 'name_ar'),

                TernaryFilter::make('overdue')
                    ->label('Overdue Payments / المدفوعات المتأخرة')
                    ->queries(
                        true: fn ($query) => $query->overdue(),
                        false: fn ($query) => $query->whereHas('paymentStatus', fn ($q) => $q->where('is_paid_status', true)),
                    ),
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