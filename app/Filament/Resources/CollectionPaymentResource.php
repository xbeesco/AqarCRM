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
                    ->columnSpan('full')
                    ->schema([
                        // العقد
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
                                        $set('amount', $contract->monthly_rent ?? 0);
                                    }
                                }
                            })
                            ->columnSpan(['lg' => 2, 'xl' => 3]),
                        
                        // القيمة المالية
                        TextInput::make('amount')
                            ->label('القيمة المالية')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->prefix('SAR')
                            ->columnSpan(['lg' => 1, 'xl' => 1]),
                        
                        // حالة التحصيل - تفاعلية
                        Select::make('collection_status')
                            ->label('حالة التحصيل')
                            ->required()
                            ->options(CollectionPayment::getStatusOptions())
                            ->default(CollectionPayment::STATUS_DUE)
                            ->live() // جعلها تفاعلية
                            ->afterStateUpdated(function ($state, callable $set) {
                                // تنظيف الحقول عند تغيير الحالة
                                $set('due_date_start', null);
                                $set('due_date_end', null);
                                $set('collection_date', null);
                                $set('delay_reason', null);
                                $set('delay_duration', null);
                                $set('late_payment_notes', null);
                            })
                            ->columnSpan(['lg' => 3, 'xl' => 4]),
                        
                        // الحقول الديناميكية حسب الحالة
                        
                        // حقول "تم التحصيل" - 3 حقول
                        DatePicker::make('due_date_start')
                            ->label('بداية التاريخ')
                            ->visible(fn ($get) => in_array($get('collection_status'), [
                                CollectionPayment::STATUS_COLLECTED,
                                CollectionPayment::STATUS_DUE
                            ]))
                            ->required(fn ($get) => in_array($get('collection_status'), [
                                CollectionPayment::STATUS_COLLECTED,
                                CollectionPayment::STATUS_DUE
                            ]))
                            ->default(now()->startOfMonth()),
                        
                        DatePicker::make('due_date_end')
                            ->label('إلى التاريخ')
                            ->visible(fn ($get) => in_array($get('collection_status'), [
                                CollectionPayment::STATUS_COLLECTED,
                                CollectionPayment::STATUS_DUE
                            ]))
                            ->required(fn ($get) => in_array($get('collection_status'), [
                                CollectionPayment::STATUS_COLLECTED,
                                CollectionPayment::STATUS_DUE
                            ]))
                            ->default(now()->endOfMonth()),
                        
                        DatePicker::make('collection_date')
                            ->label('تاريخ التحصيل')
                            ->visible(fn ($get) => $get('collection_status') === CollectionPayment::STATUS_COLLECTED)
                            ->required(fn ($get) => $get('collection_status') === CollectionPayment::STATUS_COLLECTED)
                            ->default(now()),
                        
                        // حقول "المؤجلة" - 2 حقول
                        Textarea::make('delay_reason')
                            ->label('سبب التأجيل')
                            ->visible(fn ($get) => $get('collection_status') === CollectionPayment::STATUS_POSTPONED)
                            ->required(fn ($get) => $get('collection_status') === CollectionPayment::STATUS_POSTPONED)
                            ->rows(2)
                            ->columnSpan(['lg' => 2, 'xl' => 3]),
                        
                        TextInput::make('delay_duration')
                            ->label('مدة التأجيل بالأيام')
                            ->numeric()
                            ->minValue(1)
                            ->visible(fn ($get) => $get('collection_status') === CollectionPayment::STATUS_POSTPONED)
                            ->required(fn ($get) => $get('collection_status') === CollectionPayment::STATUS_POSTPONED)
                            ->suffix('يوم')
                            ->columnSpan(['lg' => 1, 'xl' => 1]),
                        
                        // حقل "تجاوزت المدة" - 1 حقل
                        Textarea::make('late_payment_notes')
                            ->label('ملاحظات في حالة تجاوز مدة الدفعة')
                            ->visible(fn ($get) => $get('collection_status') === CollectionPayment::STATUS_OVERDUE)
                            ->required(fn ($get) => $get('collection_status') === CollectionPayment::STATUS_OVERDUE)
                            ->rows(3)
                            ->columnSpan(['lg' => 3, 'xl' => 4]),
                        
                        // Hidden fields للحفظ
                        \Filament\Forms\Components\Hidden::make('unit_id'),
                        \Filament\Forms\Components\Hidden::make('property_id'),
                        \Filament\Forms\Components\Hidden::make('tenant_id'),
                    ])->columns([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3,
                        'xl' => 4,
                    ]),
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
                    ->label('بداية التاريخ')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('due_date_end')
                    ->label('إلى التاريخ')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('collection_date')
                    ->label('تاريخ التحصيل')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable()
                    ->visible(fn () => true),
                
                TextColumn::make('delay_reason')
                    ->label('سبب التأجيل')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->delay_reason)
                    ->toggleable(),
                
                TextColumn::make('delay_duration')
                    ->label('مدة التأجيل')
                    ->formatStateUsing(fn ($state) => $state ? $state . ' يوم' : '-')
                    ->toggleable(),
                
                TextColumn::make('late_payment_notes')
                    ->label('ملاحظات التأخير')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->late_payment_notes)
                    ->toggleable(),
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
            ->recordActions([
                ViewAction::make()
                    ->label('تقرير'),
                EditAction::make(),
            ])
            ->toolbarActions([
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