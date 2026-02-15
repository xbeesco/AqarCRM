<?php

namespace App\Filament\Resources\Owners\Tables;

use App\Filament\Resources\CollectionPayments\CollectionPaymentResource;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\PropertyContracts\PropertyContractResource;
use App\Filament\Resources\SupplyPayments\SupplyPaymentResource;
use App\Filament\Resources\UnitContracts\UnitContractResource;
use App\Filament\Resources\Units\UnitResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                ActionGroup::make([
                    Action::make('view_properties')
                        ->label('العقارات')
                        ->icon('heroicon-o-building-office-2')
                        ->url(fn ($record) => PropertyResource::getUrl('index').'?owner_id='.$record->id),
                    Action::make('view_units')
                        ->label('الوحدات')
                        ->icon('heroicon-o-home')
                        ->url(fn ($record) => UnitResource::getUrl('index').'?owner_id='.$record->id),
                    Action::make('view_property_contracts')
                        ->label('عقود العقارات')
                        ->icon('heroicon-o-document-duplicate')
                        ->url(fn ($record) => PropertyContractResource::getUrl('index').'?owner_id='.$record->id),
                    Action::make('view_unit_contracts')
                        ->label('عقود الوحدات')
                        ->icon('heroicon-o-document-text')
                        ->url(fn ($record) => UnitContractResource::getUrl('index').'?owner_id='.$record->id),
                    Action::make('view_supply_payments')
                        ->label('دفعات المالك')
                        ->icon('heroicon-o-banknotes')
                        ->url(fn ($record) => SupplyPaymentResource::getUrl('index').'?owner_id='.$record->id),
                    Action::make('view_collection_payments')
                        ->label('دفعات المستأجرين')
                        ->icon('heroicon-o-currency-dollar')
                        ->url(fn ($record) => CollectionPaymentResource::getUrl('index').'?owner_id='.$record->id),
                    Action::make('view_expenses')
                        ->label('النفقات')
                        ->icon('heroicon-o-receipt-percent')
                        ->url(fn ($record) => ExpenseResource::getUrl('index').'?owner_id='.$record->id),
                ])
                    ->label('المزيد')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->color('primary')
                    ->button(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }
}
