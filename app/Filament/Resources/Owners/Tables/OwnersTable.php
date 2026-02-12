<?php

namespace App\Filament\Resources\Owners\Tables;

use App\Filament\Resources\CollectionPayments\CollectionPaymentResource;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\PropertyContracts\PropertyContractResource;
use App\Filament\Resources\SupplyPayments\SupplyPaymentResource;
use App\Filament\Resources\UnitContracts\UnitContractResource;
use App\Filament\Resources\Units\UnitResource;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class OwnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('التليفون 1')
                    ->searchable(),

                TextColumn::make('secondary_phone')
                    ->label('التليفون 2')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('الإيميل')
                    ->searchable(),

                ImageColumn::make('identity_file')
                    ->label('ملف الهوية')
                    ->disk('local')
                    ->height(40)
                    ->width(40)
                    ->defaultImageUrl(asset('images/no-image.png'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([])
            ->recordActions([
                ViewAction::make()
                    ->label('تقرير')
                    ->icon('heroicon-o-document-text'),
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->maxSelectableRecords(1)
            ->bulkActions([
                BulkAction::make('view_properties')
                    ->label('العقارات')
                    ->action(function (Collection $records) {
                        $ids = $records->pluck('id')->implode(',');

                        return redirect(PropertyResource::getUrl('index').'?owner_ids='.$ids);
                    }),
                BulkAction::make('view_units')
                    ->label('الوحدات')
                    ->action(function (Collection $records) {
                        $ids = $records->pluck('id')->implode(',');

                        return redirect(UnitResource::getUrl('index').'?owner_ids='.$ids);
                    }),
                BulkAction::make('view_property_contracts')
                    ->label('عقود العقارات')
                    ->action(function (Collection $records) {
                        $ids = $records->pluck('id')->implode(',');

                        return redirect(PropertyContractResource::getUrl('index').'?owner_ids='.$ids);
                    }),
                BulkAction::make('view_unit_contracts')
                    ->label('عقود الوحدات')
                    ->action(function (Collection $records) {
                        $ids = $records->pluck('id')->implode(',');

                        return redirect(UnitContractResource::getUrl('index').'?owner_ids='.$ids);
                    }),
                BulkAction::make('view_supply_payments')
                    ->label('دفعات المالك')
                    ->action(function (Collection $records) {
                        $ids = $records->pluck('id')->implode(',');

                        return redirect(SupplyPaymentResource::getUrl('index').'?owner_ids='.$ids);
                    }),
                BulkAction::make('view_collection_payments')
                    ->label('دفعات المستأجرين')
                    ->action(function (Collection $records) {
                        $ids = $records->pluck('id')->implode(',');

                        return redirect(CollectionPaymentResource::getUrl('index').'?owner_ids='.$ids);
                    }),
                BulkAction::make('view_expenses')
                    ->label('النفقات')
                    ->action(function (Collection $records) {
                        $ids = $records->pluck('id')->implode(',');

                        return redirect(ExpenseResource::getUrl('index').'?owner_ids='.$ids);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
