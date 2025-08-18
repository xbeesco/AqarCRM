<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitStatusResource\Pages;
use App\Models\UnitStatus;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;
use BackedEnum;

class UnitStatusResource extends Resource
{
    protected static ?string $model = UnitStatus::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'حالات الوحدات / Unit Statuses';

    protected static ?string $modelLabel = 'حالة وحدة / Unit Status';

    protected static ?string $pluralModelLabel = 'حالات الوحدات / Unit Statuses';


    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('المعلومات الأساسية / Basic Information')
                    ->schema([
                        TextInput::make('name_ar')
                            ->label('الاسم بالعربية / Arabic Name')
                            ->required()
                            ->maxLength(100),
                        
                        TextInput::make('name_en')
                            ->label('الاسم بالإنجليزية / English Name')
                            ->required()
                            ->maxLength(100),
                        
                        TextInput::make('slug')
                            ->label('المعرف / Slug')
                            ->required()
                            ->maxLength(120)
                            ->unique(ignoreRecord: true)
                            ->rules(['regex:/^[a-z0-9-]+$/']),
                        
                        Select::make('color')
                            ->label('اللون / Color')
                            ->required()
                            ->options([
                                'gray' => 'رمادي / Gray',
                                'red' => 'أحمر / Red',
                                'yellow' => 'أصفر / Yellow',
                                'green' => 'أخضر / Green',
                                'blue' => 'أزرق / Blue',
                                'indigo' => 'نيلي / Indigo',
                                'purple' => 'بنفسجي / Purple',
                                'pink' => 'وردي / Pink',
                            ])
                            ->default('gray'),
                        
                        TextInput::make('icon')
                            ->label('الأيقونة / Icon')
                            ->maxLength(50)
                            ->default('heroicon-o-home')
                            ->placeholder('heroicon-o-home'),
                    ])->columns(2),

                Section::make('الوصف / Description')
                    ->schema([
                        Textarea::make('description_ar')
                            ->label('الوصف بالعربية / Arabic Description')
                            ->maxLength(1000)
                            ->rows(3),
                        
                        Textarea::make('description_en')
                            ->label('الوصف بالإنجليزية / English Description')
                            ->maxLength(1000)
                            ->rows(3),
                    ])->columns(2),

                Section::make('إعدادات الحالة / Status Settings')
                    ->schema([
                        Toggle::make('is_available')
                            ->label('متاح للإيجار / Available for Rent')
                            ->default(true)
                            ->helperText('هل الوحدات بهذه الحالة متاحة للإيجار؟'),
                        
                        Toggle::make('allows_tenant_assignment')
                            ->label('يسمح بتخصيص المستأجر / Allows Tenant Assignment')
                            ->default(true)
                            ->helperText('هل يمكن تخصيص مستأجر للوحدات بهذه الحالة؟'),
                        
                        Toggle::make('requires_maintenance')
                            ->label('يتطلب صيانة / Requires Maintenance')
                            ->default(false)
                            ->helperText('هل الوحدات بهذه الحالة تتطلب صيانة؟'),
                        
                        Toggle::make('is_active')
                            ->label('نشط / Active')
                            ->default(true),
                        
                        TextInput::make('sort_order')
                            ->label('ترتيب العرض / Sort Order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->filtersAboveTable()
            ->columns([
                Tables\Columns\TextColumn::make('name_display')
                    ->label('الاسم / Name')
                    ->formatStateUsing(fn (UnitStatus $record): string => "{$record->name_ar} / {$record->name_en}")
                    ->searchable(['name_ar', 'name_en'])
                    ->sortable(['name_ar']),
                
                Tables\Columns\TextColumn::make('slug')
                    ->label('المعرف / Slug')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                
                Tables\Columns\BadgeColumn::make('badge_preview')
                    ->label('شكل الحالة / Badge Preview')
                    ->formatStateUsing(fn (UnitStatus $record): string => $record->name_ar)
                    ->colors([
                        'gray' => fn (UnitStatus $record) => $record->color === 'gray',
                        'danger' => fn (UnitStatus $record) => $record->color === 'red',
                        'warning' => fn (UnitStatus $record) => $record->color === 'yellow',
                        'success' => fn (UnitStatus $record) => $record->color === 'green',
                        'primary' => fn (UnitStatus $record) => $record->color === 'blue',
                        'secondary' => fn (UnitStatus $record) => in_array($record->color, ['indigo', 'purple', 'pink']),
                    ]),
                
                Tables\Columns\TextColumn::make('availability_settings')
                    ->label('إعدادات الإتاحة / Availability Settings')
                    ->formatStateUsing(function (UnitStatus $record): string {
                        $settings = [];
                        if ($record->is_available) $settings[] = 'متاح للإيجار';
                        if ($record->allows_tenant_assignment) $settings[] = 'يسمح بالتخصيص';
                        if ($record->requires_maintenance) $settings[] = 'يتطلب صيانة';
                        return implode(' • ', $settings) ?: 'لا توجد إعدادات';
                    }),
                
                Tables\Columns\TextColumn::make('units_count')
                    ->label('عدد الوحدات / Units Count')
                    ->counts('units')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('الترتيب / Order')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط / Active')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('نشط / Active')
                    ->trueLabel('نشط / Active')
                    ->falseLabel('غير نشط / Inactive')
                    ->placeholder('الكل / All'),
                
                TernaryFilter::make('is_available')
                    ->label('متاح للتأجير / Available for Rent')
                    ->trueLabel('متاح / Available')
                    ->falseLabel('غير متاح / Unavailable')
                    ->placeholder('الكل / All'),
                
                SelectFilter::make('color')
                    ->label('اللون / Color')
                    ->options([
                        'gray' => 'رمادي / Gray',
                        'red' => 'أحمر / Red',
                        'yellow' => 'أصفر / Yellow',
                        'green' => 'أخضر / Green',
                        'blue' => 'أزرق / Blue',
                        'indigo' => 'نيلي / Indigo',
                        'purple' => 'بنفسجي / Purple',
                        'pink' => 'وردي / Pink',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض / View'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل / Edit'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف / Delete'),
                ]),
            ])
            ->defaultSort('sort_order')
            ->defaultSort('name_ar');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnitStatuses::route('/'),
            'create' => Pages\CreateUnitStatus::route('/create'),
            'view' => Pages\ViewUnitStatus::route('/{record}'),
            'edit' => Pages\EditUnitStatus::route('/{record}/edit'),
        ];
    }
}