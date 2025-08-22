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
use Filament\Tables\Columns\BadgeColumn;
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
            Section::make('معلومات دفعة المالك')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('property_contract_id')
                            ->label('عقد العقار')
                            ->relationship('propertyContract', 'contract_number')
                            ->required()
                            ->searchable(),

                        TextInput::make('amount')
                            ->label('المبلغ')
                            ->numeric()
                            ->required()
                            ->prefix('ر.س'),

                        DatePicker::make('payment_date')
                            ->label('تاريخ الدفع')
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'pending' => 'في الانتظار',
                                'approved' => 'موافق عليه',
                                'paid' => 'مدفوع',
                                'cancelled' => 'ملغي'
                            ])
                            ->required()
                            ->default('pending'),

                        TextInput::make('reference_number')
                            ->label('رقم المرجع')
                            ->maxLength(255),
                    ]),

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
                TextColumn::make('propertyContract.contract_number')
                    ->label('رقم العقد')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('payment_date')
                    ->label('تاريخ الدفع')
                    ->date('Y-m-d')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'primary' => 'paid',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'في الانتظار',
                        'approved' => 'موافق عليه',
                        'paid' => 'مدفوع',
                        'cancelled' => 'ملغي',
                        default => $state,
                    }),

                TextColumn::make('reference_number')
                    ->label('رقم المرجع')
                    ->searchable(),

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
                        'approved' => 'موافق عليه',
                        'paid' => 'مدفوع',
                        'cancelled' => 'ملغي'
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
            'index' => Pages\ListSupplyPayments::route('/'),
            'create' => Pages\CreateSupplyPayment::route('/create'),
            'view' => Pages\ViewSupplyPayment::route('/{record}'),
            'edit' => Pages\EditSupplyPayment::route('/{record}/edit'),
        ];
    }
}