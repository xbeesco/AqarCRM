<?php

namespace App\Filament\Resources\CustomFields\Tables;

use App\Enums\CustomFieldTarget;
use App\Enums\CustomFieldType;
use App\Models\CustomField;
use App\Support\CustomFieldSections;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomFieldsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الحقل')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('target')
                    ->label('تابع لـ')
                    ->badge()
                    ->formatStateUsing(fn (CustomFieldTarget $state): string => $state->label())
                    ->color(fn (CustomFieldTarget $state): string => match ($state) {
                        CustomFieldTarget::Unit => 'info',
                        CustomFieldTarget::Property => 'warning',
                    }),

                TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (CustomFieldType $state): string => $state->label())
                    ->color('primary'),

                TextColumn::make('section')
                    ->label('القسم')
                    ->formatStateUsing(fn ($state, $record): string => CustomFieldSections::label($record->target->value, $state))
                    ->sortable(),

                IconColumn::make('is_searchable')
                    ->label('قابل للبحث')
                    ->boolean()
                    ->alignCenter(),

                IconColumn::make('is_required')
                    ->label('إلزامي')
                    ->boolean()
                    ->alignCenter(),

                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable()
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('target')
                    ->label('تابع لـ')
                    ->options(collect(CustomFieldTarget::cases())->mapWithKeys(
                        fn (CustomFieldTarget $t) => [$t->value => $t->label()]
                    )),

                SelectFilter::make('type')
                    ->label('النوع')
                    ->options(collect(CustomFieldType::cases())->mapWithKeys(
                        fn (CustomFieldType $t) => [$t->value => $t->label()]
                    )),
            ])
            ->recordActions([
                EditAction::make()->label('تعديل'),

                // Action for fields IN USE - just shows notification
                Action::make('cannot_delete')
                    ->label('حذف')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->visible(fn (CustomField $record): bool => $record->getUsageCount() > 0)
                    ->action(function (CustomField $record) {
                        Notification::make()
                            ->danger()
                            ->title('لا يمكن حذف هذا الحقل')
                            ->body("مستخدم في {$record->getUsageCount()} سجل")
                            ->send();
                    }),

                // Action for fields NOT in use - deletes with confirmation
                DeleteAction::make()
                    ->label('حذف')
                    ->visible(fn (CustomField $record): bool => $record->getUsageCount() === 0),
            ])
            ->defaultSort('sort_order');
    }
}
