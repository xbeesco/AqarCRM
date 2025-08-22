<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyContractResource\Pages;
use App\Models\PropertyContract;
use App\Models\Property;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
class PropertyContractResource extends Resource
{
    protected static ?string $model = PropertyContract::class;

    protected static ?string $navigationLabel = 'عقود الملاك';

    protected static ?string $modelLabel = 'عقد ملكية';

    protected static ?string $pluralModelLabel = 'عقود الملاك';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('بيانات العقد')
                    ->schema([
                        Select::make('property_id')
                            ->label('العقار')
                            ->required()
                            ->searchable()
                            ->relationship('property', 'name')
                            ->options(Property::with('owner')->get()->pluck('name', 'id'))
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name . ' - ' . $record->owner?->name)
                            ->columnSpan(4),

                        DatePicker::make('contract_date')
                            ->label('تاريخ بداية العمل بالعقد')
                            ->required()
                            ->default(now())
                            ->columnSpan(4),

                        TextInput::make('commission_rate')
                            ->label('النسبة المئوية')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->columnSpan(4),

                        TextInput::make('duration_months')
                            ->label('مدة التعاقد بالشهر')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('شهر')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $get, $set) {
                                $frequency = $get('payment_frequency') ?? 'monthly';
                                $count = \App\Services\PropertyContractService::calculatePaymentsCount($state ?? 0, $frequency);
                                $set('payments_count', $count);
                            })
                            ->columnSpan(4),

                        Select::make('payment_frequency')
                            ->label('التوريد كل')
                            ->required()
                            ->options([
                                'monthly' => 'شهر',
                                'quarterly' => 'ربع سنة',
                                'semi_annually' => 'نصف سنة',
                                'annually' => 'سنة',
                            ])
                            ->default('monthly')
                            ->live()
                            ->afterStateUpdated(function ($state, $get, $set) {
                                $duration = $get('duration_months') ?? 0;
                                $count = \App\Services\PropertyContractService::calculatePaymentsCount($duration, $state ?? 'monthly');
                                $set('payments_count', $count);
                            })
                            ->columnSpan(4),

                        TextInput::make('payments_count')
                            ->label('عدد الدفعات')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(function ($get) {
                                $duration = $get('duration_months') ?? 0;
                                $frequency = $get('payment_frequency') ?? 'monthly';
                                return \App\Services\PropertyContractService::calculatePaymentsCount($duration, $frequency);
                            })
                            ->columnSpan(4),

                        FileUpload::make('contract_file')
                            ->label('ملف العقد')
                            ->required()
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->disk('public')
                            ->directory('contract-files')
                            ->preserveFilenames()
                            ->maxSize(10240) // 10MB
                            ->columnSpan(6),

                        Textarea::make('notes')
                            ->label('الملاحظات')
                            ->rows(3)
                            ->columnSpan(6),
                    ])
                    ->columns(12)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('property.name')
                    ->label('العقار')
                    ->searchable()
                    ->sortable()
                    ->description(fn (PropertyContract $record): string => $record->owner?->name ?? ''),

                TextColumn::make('contract_date')
                    ->label('تاريخ العقد')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('duration_months')
                    ->label('مدة التعاقد')
                    ->sortable()
                    ->suffix(' شهر')
                    ->alignCenter(),

                TextColumn::make('commission_rate')
                    ->label('النسبة')
                    ->sortable()
                    ->suffix('%')
                    ->alignCenter(),

                BadgeColumn::make('payment_frequency')
                    ->label('التوريد كل')
                    ->colors([
                        'primary' => 'monthly',
                        'success' => 'quarterly',
                        'warning' => 'four_monthly',
                        'info' => 'semi_annually',
                        'danger' => 'annually',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'monthly' => 'شهر',
                            'quarterly' => 'ربع سنة',
                            'semi_annually' => 'نصف سنة',
                            'annually' => 'سنة',
                            default => $state,
                        };
                    }),

                TextColumn::make('payments_count')
                    ->label('عدد الدفعات')
                    ->alignCenter()
                    ->sortable()
                    ->suffix(' دفعة'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('property_id')
                    ->label('العقار')
                    ->relationship('property', 'name')
                    ->searchable(),

                SelectFilter::make('payment_frequency')
                    ->label('تكرار السداد')
                    ->options([
                        'monthly' => 'شهري',
                        'quarterly' => 'ربع سنوي',
                        'four_monthly' => 'ثلث سنوي',
                        'semi_annually' => 'نصف سنوي',
                        'annually' => 'سنوي',
                    ]),

                Filter::make('contract_date_range')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('contract_date_from')
                                    ->label('من تاريخ'),
                                DatePicker::make('contract_date_until')
                                    ->label('إلى تاريخ'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['contract_date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('contract_date', '>=', $date),
                            )
                            ->when(
                                $data['contract_date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('contract_date', '<=', $date),
                            );
                    })
                    ->label('نطاق تاريخ العقد'),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->recordActions([
                EditAction::make(),
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
            'index' => Pages\ListPropertyContracts::route('/'),
            'create' => Pages\CreatePropertyContract::route('/create'),
            'view' => Pages\ViewPropertyContract::route('/{record}'),
            'edit' => Pages\EditPropertyContract::route('/{record}/edit'),
        ];
    }
}