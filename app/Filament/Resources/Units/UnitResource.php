<?php

namespace App\Filament\Resources\Units;

use App\Filament\Resources\Units\Pages\CreateUnit;
use App\Filament\Resources\Units\Pages\EditUnit;
use App\Filament\Resources\Units\Pages\ListUnits;
use App\Models\Unit;
use App\Models\UnitType;
use App\Models\UnitCategory;
use App\Models\UnitFeature;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Schemas\Schema;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;
    
    protected static ?string $navigationLabel = 'الوحدات';
    
    protected static ?string $modelLabel = 'وحدة';
    
    protected static ?string $pluralModelLabel = 'الوحدات';
    
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';
    
    protected static string|\UnitEnum|null $navigationGroup = 'إدارة العقارات';
    
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('المعلومات الأساسية')
                ->columnSpanFull()
                ->columns(10)
                ->schema([
                                            Select::make('unit_type_id')
                            ->label('نوع الوحدة')
                            ->options(UnitType::where('is_active', true)->orderBy('sort_order')->pluck('name_ar', 'id'))
                            ->searchable()
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('name')
                            ->label('اسم الوحدة')
                            ->required()
                            ->columnSpan(4),

                        Select::make('property_id')
                            ->label('العقار')
                            ->relationship('property', 'name')
                            ->searchable()
                            ->required()
                            ->columnSpan(2),


                        Select::make('unit_category_id')
                            ->label('تصنيف الوحدة')
                            ->options(UnitCategory::where('is_active', true)->orderBy('sort_order')->pluck('name_ar', 'id'))
                            ->searchable()
                            ->required()
                            ->columnSpan(2),
                ]),
                
            Section::make('التفاصيل')
                ->schema([
                    Grid::make(4)->schema([
                        TextInput::make('rooms_count')
                            ->label('عدد الغرف')
                            ->numeric()
                            ->nullable(),
                            
                        TextInput::make('bathrooms_count')
                            ->label('عدد دورات المياه')
                            ->numeric()
                            ->nullable(),
                            
                        TextInput::make('balconies_count')
                            ->label('عدد الشرفات')
                            ->numeric()
                            ->nullable(),
                            
                        TextInput::make('floor_number')
                            ->label('رقم الطابق')
                            ->numeric()
                            ->nullable(),
                    ]),
                    
                    Grid::make(4)->schema([
                        Select::make('has_laundry_room')
                            ->label('غرفة غسيل')
                            ->options([
                                1 => 'نعم',
                                0 => 'لا',
                            ])
                            ->default(0)
                            ->required(),
                            
                        TextInput::make('electricity_account_number')
                            ->label('رقم حساب الكهرباء'),
                            
                        TextInput::make('water_expenses')
                            ->label('مصروف المياه')
                            ->numeric()
                            ->prefix('ر.س')
                            ->nullable(),
                                                TextInput::make('area_sqm')
                            ->label('المساحة (م²)')
                            ->numeric()
                            ->suffix('م²')
                            ->nullable(),

                    ]),
                    
                    
                    FileUpload::make('floor_plan_file')
                        ->label('مخطط الوحدة')
                        ->directory('units/floor-plans')
                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                        ->maxSize(5120) // 5MB
                        ->downloadable()
                        ->columnSpanFull(),

                ]),
                
            Section::make('المميزات')
                ->schema([
                    CheckboxList::make('features')
                        ->hiddenLabel()
                        ->relationship('features', 'name_ar')
                        ->columns(4)
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
                TextColumn::make('name')
                    ->label('اسم الوحدة')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('property.name')
                    ->label('العقار')
                    ->searchable(),
                    
                TextColumn::make('unitType.name_ar')
                    ->label('النوع'),
                    
                BadgeColumn::make('unitCategory.name_ar')
                    ->label('التصنيف')
                    ->color(fn ($record) => $record->unitCategory?->color ? 
                        str_replace('#', '', $record->unitCategory->color) : 'gray'),
                    
                TextColumn::make('rooms_count')
                    ->label('الغرف')
                    ->formatStateUsing(fn ($state) => $state ? $state . ' غرف' : '-'),
                    
                TextColumn::make('floor_number')
                    ->label('الطابق')
                    ->formatStateUsing(fn ($state) => $state !== null ? 'الطابق ' . $state : '-'),
                    
                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'available' => 'متاح',
                        'occupied' => 'مشغول',
                        'maintenance' => 'تحت الصيانة',
                        'reserved' => 'محجوز',
                        default => $state
                    })
                    ->color(fn ($state) => match($state) {
                        'available' => 'success',
                        'occupied' => 'danger',
                        'maintenance' => 'warning',
                        'reserved' => 'info',
                        default => 'gray'
                    }),
                    
                TextColumn::make('rent_price')
                    ->label('السعر')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) . ' ر.س' : '-')
                    ->color('success'),
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
                    
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'available' => 'متاح',
                        'occupied' => 'مشغول',
                        'maintenance' => 'تحت الصيانة',
                        'reserved' => 'محجوز',
                    ]),
            ], layout: \Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => ListUnits::route('/'),
            'create' => CreateUnit::route('/create'),
            'edit' => EditUnit::route('/{record}/edit'),
        ];
    }
}