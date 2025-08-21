<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OperationResource\Pages;
use App\Models\Operation;
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
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Schemas\Schema;
class OperationResource extends Resource
{
    protected static ?string $model = Operation::class;

    protected static ?string $navigationLabel = 'العمليات';

    protected static ?string $modelLabel = 'عملية';

    protected static ?string $pluralModelLabel = 'العمليات';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('معلومات العملية')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('title')
                            ->label('عنوان العملية')
                            ->required()
                            ->maxLength(255),

                        Select::make('type')
                            ->label('نوع العملية')
                            ->options([
                                'maintenance' => 'صيانة',
                                'inspection' => 'تفتيش',
                                'repair' => 'إصلاح',
                                'cleaning' => 'تنظيف',
                                'other' => 'أخرى'
                            ])
                            ->required(),
                    ]),

                    Grid::make(3)->schema([
                        Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'pending' => 'في الانتظار',
                                'in_progress' => 'قيد التنفيذ',
                                'completed' => 'مكتملة',
                                'cancelled' => 'ملغية'
                            ])
                            ->required()
                            ->default('pending'),

                        Select::make('priority')
                            ->label('الأولوية')
                            ->options([
                                'low' => 'منخفضة',
                                'normal' => 'عادية',
                                'high' => 'عالية',
                                'urgent' => 'عاجلة'
                            ])
                            ->required()
                            ->default('normal'),

                        DatePicker::make('due_date')
                            ->label('تاريخ الاستحقاق'),
                    ]),

                    Select::make('assigned_to')
                        ->label('مُكلف بها')
                        ->relationship('assignedUser', 'name')
                        ->searchable()
                        ->nullable(),

                    Textarea::make('description')
                        ->label('الوصف')
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('عنوان العملية')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('type')
                    ->label('النوع')
                    ->colors([
                        'primary' => 'maintenance',
                        'success' => 'inspection',
                        'warning' => 'repair',
                        'info' => 'cleaning',
                        'secondary' => 'other',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'maintenance' => 'صيانة',
                        'inspection' => 'تفتيش',
                        'repair' => 'إصلاح',
                        'cleaning' => 'تنظيف',
                        'other' => 'أخرى',
                        default => $state,
                    }),

                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'في الانتظار',
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتملة',
                        'cancelled' => 'ملغية',
                        default => $state,
                    }),

                BadgeColumn::make('priority')
                    ->label('الأولوية')
                    ->colors([
                        'secondary' => 'low',
                        'primary' => 'normal',
                        'warning' => 'high',
                        'danger' => 'urgent',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'low' => 'منخفضة',
                        'normal' => 'عادية',
                        'high' => 'عالية',
                        'urgent' => 'عاجلة',
                        default => $state,
                    }),

                TextColumn::make('assignedUser.name')
                    ->label('المُكلف')
                    ->searchable(),

                TextColumn::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'في الانتظار',
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتملة',
                        'cancelled' => 'ملغية'
                    ]),

                SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        'maintenance' => 'صيانة',
                        'inspection' => 'تفتيش',
                        'repair' => 'إصلاح',
                        'cleaning' => 'تنظيف',
                        'other' => 'أخرى'
                    ]),

                SelectFilter::make('priority')
                    ->label('الأولوية')
                    ->options([
                        'low' => 'منخفضة',
                        'normal' => 'عادية',
                        'high' => 'عالية',
                        'urgent' => 'عاجلة'
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
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
            'index' => Pages\ListOperations::route('/'),
            'create' => Pages\CreateOperation::route('/create'),
            'view' => Pages\ViewOperation::route('/{record}'),
            'edit' => Pages\EditOperation::route('/{record}/edit'),
        ];
    }
}