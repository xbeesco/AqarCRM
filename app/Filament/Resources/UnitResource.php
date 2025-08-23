<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitResource\Pages;
use App\Models\Unit;
use App\Models\Property;
use App\Models\UnitType;
use App\Models\UnitCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $navigationLabel = 'الوحدات';
    
    protected static ?string $modelLabel = 'وحدة';
    
    protected static ?string $pluralModelLabel = 'الوحدات';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('المعلومات الأساسية')
                ->columnSpanFull()
                ->columns(12)
                ->schema([
                    Select::make('property_id')
                        ->label('العقار')
                        ->relationship('property', 'name')
                        ->searchable()
                        ->required()
                        ->columnSpan(3),
                    
                    Select::make('unit_type_id')
                        ->label('نوع الوحدة')
                        ->relationship('unitType', 'name_ar')
                        ->required()
                        ->native(false)
                        ->columnSpan(3),
                    
                    TextInput::make('name')
                        ->label('اسم الوحدة')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(3),
                    
                    Select::make('unit_category_id')
                        ->label('تصنيف الوحدة')
                        ->relationship('unitCategory', 'name_ar')
                        ->required()
                        ->native(false)
                        ->columnSpan(3),
                ]),

                Section::make('التفاصيل')
                    ->columnSpan(1)
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('rent_price')
                                ->label('سعر الايجار الاستدلالي')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->prefix('ريال'),
                            TextInput::make('rooms_count')
                                ->label('عدد الغرف')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(20)
                                ->nullable(),
                            
                            TextInput::make('bathrooms_count')
                                ->label('عدد دورات المياه')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(10)
                                ->nullable(),
                            
                            TextInput::make('balconies_count')
                                ->label('عدد الشرفات')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(10)
                                ->nullable(),
                            
                            TextInput::make('floor_number')
                                ->label('رقم الطابق')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->nullable(),
                            
                            Select::make('has_laundry_room')
                                ->label('غرفة غسيل')
                                ->options([
                                    1 => 'نعم',
                                    0 => 'لا',
                                ])
                                ->default(0),
                            
                            TextInput::make('electricity_account_number')
                                ->label('رقم حساب الكهرباء')
                                ->maxLength(255)
                                ->nullable(),
                            
                            TextInput::make('water_expenses')
                                ->label('مصروف المياه')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('ريال')
                                ->nullable(),
                            
                            TextInput::make('area_sqm')
                                ->label('المساحة')
                                ->numeric()
                                ->minValue(0)
                                ->suffix('م²')
                                ->nullable(),
                        ]),
                    ]),

                Section::make('المميزات')
                    ->columnSpan(1)
                    ->schema([
                        // يمكن إضافة المميزات هنا لاحقاً
                    ]),

            Section::make('المخططات والملاحظات')
                ->columnSpanFull()
                ->schema([
                    Grid::make(2)->schema([
                        FileUpload::make('floor_plan_file')
                            ->label('مخطط الوحدة')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(5120) // 5MB
                            ->directory('unit-floor-plans')
                            ->preserveFilenames()
                            ->nullable(),
                        
                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->maxLength(65535)
                            ->rows(3),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الوحدة')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('property.name')
                    ->label('العقار')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('unitType.name_ar')
                    ->label('النوع')
                    ->searchable(),
                
                TextColumn::make('unitCategory.name_ar')
                    ->label('التصنيف')
                    ->searchable(),
                
                TextColumn::make('rooms_count')
                    ->label('الغرف')
                    ->sortable(),
                
                TextColumn::make('bathrooms_count')
                    ->label('الحمامات')
                    ->sortable(),
                
                TextColumn::make('floor_number')
                    ->label('الطابق')
                    ->sortable(),
                
                TextColumn::make('area_sqm')
                    ->label('المساحة')
                    ->suffix(' م²')
                    ->sortable(),
                
                TextColumn::make('rent_price')
                    ->label('الإيجار')
                    ->money('SAR')
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('property_id')
                    ->label('العقار')
                    ->relationship('property', 'name'),
                
                SelectFilter::make('unit_type_id')
                    ->label('نوع الوحدة')
                    ->relationship('unitType', 'name_ar'),
                
                SelectFilter::make('unit_category_id')
                    ->label('تصنيف الوحدة')
                    ->relationship('unitCategory', 'name_ar'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([]);
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
            'index' => Pages\ListUnits::route('/'),
            'create' => Pages\CreateUnit::route('/create'),
            'view' => Pages\ViewUnit::route('/{record}'),
            'edit' => Pages\EditUnit::route('/{record}/edit'),
        ];
    }
}