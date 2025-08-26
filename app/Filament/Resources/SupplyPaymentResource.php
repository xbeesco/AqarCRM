<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplyPaymentResource\Pages;
use App\Models\SupplyPayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Schemas\Schema;
class SupplyPaymentResource extends Resource
{
    protected static ?string $model = SupplyPayment::class;

    protected static ?string $navigationLabel = 'دفعات الملاك';

    protected static ?string $modelLabel = 'دفعة مالك';

    protected static ?string $pluralModelLabel = 'دفعات الملاك';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('إضافة دفعة توريد')
                ->columnSpan('full')
                ->schema([
                    // العقد
                    Select::make('property_contract_id')
                        ->label('العقد')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            return \App\Models\PropertyContract::with(['property', 'owner'])
                                ->get()
                                ->mapWithKeys(function ($contract) {
                                    $label = sprintf(
                                        'عقد: %s | عقار: %s | المالك: %s',
                                        $contract->contract_number ?: 'بدون رقم',
                                        $contract->property?->name ?? 'غير محدد',
                                        $contract->owner?->name ?? 'غير محدد'
                                    );
                                    return [$contract->id => $label];
                                });
                        })
                        ->columnSpan(['lg' => 2, 'xl' => 3]),
                    
                    // حالة التوريد
                    Select::make('supply_status')
                        ->label('حالة التوريد')
                        ->required()
                        ->options([
                            'pending' => 'قيد الانتظار',
                            'worth_collecting' => 'تستحق التوريد',
                            'collected' => 'تم التوريد',
                        ])
                        ->default('pending')
                        ->live() // جعلها تفاعلية
                        ->afterStateUpdated(function ($state, callable $set) {
                            // تنظيف الحقول عند تغيير الحالة
                            $set('due_date', null);
                            $set('paid_date', null);
                            $set('approval_status', null);
                        })
                        ->columnSpan(['lg' => 1, 'xl' => 1]),
                    
                    // الحقول الديناميكية حسب حالة التوريد
                    
                    // تاريخ الاستحقاق - يظهر مع "قيد الانتظار" و "تستحق التوريد"
                    DatePicker::make('due_date')
                        ->label('تاريخ الاستحقاق')
                        ->visible(fn ($get) => in_array($get('supply_status'), ['pending', 'worth_collecting']))
                        ->required(fn ($get) => in_array($get('supply_status'), ['pending', 'worth_collecting']))
                        ->default(now()->addDays(7))
                        ->columnSpan(['lg' => 1, 'xl' => 1]),
                    
                    // تاريخ التوريد - يظهر مع "تم التوريد"
                    DatePicker::make('paid_date')
                        ->label('تاريخ التوريد')
                        ->visible(fn ($get) => $get('supply_status') === 'collected')
                        ->required(fn ($get) => $get('supply_status') === 'collected')
                        ->default(now())
                        ->columnSpan(['lg' => 1, 'xl' => 1]),
                    
                    // إقرار ما بعد التوريد - يظهر مع "تم التوريد"
                    \Filament\Forms\Components\Placeholder::make('approval_section')
                        ->label('إقرار ما بعد التوريد')
                        ->content('')
                        ->visible(fn ($get) => $get('supply_status') === 'collected')
                        ->columnSpan(['lg' => 3, 'xl' => 4]),
                    
                    \Filament\Forms\Components\Radio::make('approval_status')
                        ->label('أقر')
                        ->options([
                            'approved' => 'موافق',
                            'rejected' => 'غير موافق',
                        ])
                        ->inline()
                        ->visible(fn ($get) => $get('supply_status') === 'collected')
                        ->required(fn ($get) => $get('supply_status') === 'collected')
                        ->columnSpan(['lg' => 3, 'xl' => 4]),
                    
                    // Hidden fields
                    \Filament\Forms\Components\Hidden::make('owner_id'),
                    \Filament\Forms\Components\Hidden::make('payment_number'),
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
            ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query) {
                $query->with(['propertyContract.property', 'propertyContract.owner', 'owner']);
            })
            ->columns([
                TextColumn::make('payment_number')
                    ->label('رقم الدفعة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('propertyContract.contract_number')
                    ->label('رقم العقد')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('propertyContract.property.name')
                    ->label('العقار')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('owner.name')
                    ->label('المالك')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('gross_amount')
                    ->label('المبلغ الإجمالي')
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('net_amount')
                    ->label('المبلغ الصافي')
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('supply_status')
                    ->label('حالة التوريد')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'worth_collecting' => 'info',
                        'collected' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'قيد الانتظار',
                        'worth_collecting' => 'تستحق التوريد',
                        'collected' => 'تم التوريد',
                        default => $state,
                    }),

                TextColumn::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('paid_date')
                    ->label('تاريخ التوريد')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('approval_status')
                    ->label('حالة الموافقة')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'approved' => 'موافق',
                        'rejected' => 'غير موافق',
                        'pending' => 'بانتظار الموافقة',
                        default => '-',
                    })
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('supply_status')
                    ->label('حالة التوريد')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'worth_collecting' => 'تستحق التوريد',
                        'collected' => 'تم التوريد',
                    ]),
                    
                SelectFilter::make('approval_status')
                    ->label('حالة الموافقة')
                    ->options([
                        'approved' => 'موافق',
                        'rejected' => 'غير موافق',
                        'pending' => 'بانتظار الموافقة',
                    ]),
                    
                SelectFilter::make('property_contract_id')
                    ->label('العقد')
                    ->relationship('propertyContract', 'contract_number')
                    ->getOptionLabelFromRecordUsing(function ($record) {
                        return sprintf(
                            '%s - %s - %s',
                            $record->contract_number ?? 'بدون رقم',
                            $record->property?->name ?? 'غير محدد',
                            $record->owner?->name ?? 'غير محدد'
                        );
                    })
                    ->searchable()
                    ->preload(),
                    
                SelectFilter::make('owner_id')
                    ->label('المالك')
                    ->relationship('owner', 'name')
                    ->searchable(),
            ], layout: \Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->recordActions([
                ViewAction::make()
                    ->label('تقرير'),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListSupplyPayments::route('/'),
            'create' => Pages\CreateSupplyPayment::route('/create'),
            'view' => Pages\ViewSupplyPayment::route('/{record}'),
            'edit' => Pages\EditSupplyPayment::route('/{record}/edit'),
        ];
    }
}