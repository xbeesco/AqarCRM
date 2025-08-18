<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyResource\Pages;
use App\Models\Property;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Schemas\Schema;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;
    
    protected static ?string $navigationLabel = 'العقارات';
    
    protected static ?string $modelLabel = 'عقار';
    
    protected static ?string $pluralModelLabel = 'العقارات';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('بيانات العقار الأساسية')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('name')
                            ->label('اسم العقار')
                            ->required()
                            ->columnSpan(2),
                            
                        Select::make('owner_id')
                            ->label('المالك')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->required()
                            ->columnSpan(1),
                    ]),
                    
                    Grid::make(3)->schema([
                        Select::make('status')
                            ->label('حالة العقار')
                            ->options([
                                'active' => 'نشط',
                                'inactive' => 'غير نشط',
                                'maintenance' => 'تحت الصيانة'
                            ])
                            ->required(),
                            
                        Select::make('type')
                            ->label('نوع العقار')
                            ->options([
                                'residential' => 'سكني',
                                'commercial' => 'تجاري',
                                'mixed' => 'مختلط'
                            ])
                            ->required(),
                            
                        TextInput::make('area_sqm')
                            ->label('المساحة (م²)')
                            ->numeric()
                            ->suffix('م²'),
                    ]),
                ]),
                
            Section::make('الموقع والعنوان')
                ->schema([
                    Select::make('location_id')
                        ->label('الموقع')
                        ->relationship('location', 'name')
                        ->searchable()
                        ->nullable(),
                        
                    Grid::make(2)->schema([
                        TextInput::make('address')
                            ->label('رقم المبنى واسم الشارع')
                            ->required()
                            ->columnSpan(1),
                            
                        TextInput::make('postal_code')
                            ->label('الرمز البريدي')
                            ->columnSpan(1),
                    ]),
                ]),
                
            Section::make('تفاصيل إضافية')
                ->schema([
                    Grid::make(4)->schema([
                        TextInput::make('parking_spots')
                            ->label('عدد المواقف')
                            ->numeric()
                            ->default(0),
                            
                        TextInput::make('elevators')
                            ->label('عدد المصاعد')
                            ->numeric()
                            ->default(0),
                            
                        TextInput::make('floors_count')
                            ->label('عدد الطوابق')
                            ->numeric(),
                            
                        TextInput::make('build_year')
                            ->label('سنة البناء')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(date('Y')),
                    ]),
                    
                    Textarea::make('notes')
                        ->label('ملاحظات خاصة')
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
                    ->label('اسم العقار')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('owner.name')
                    ->label('المالك')
                    ->searchable(),
                    
                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'maintenance',
                        'danger' => 'inactive',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'active' => 'نشط',
                        'inactive' => 'غير نشط',
                        'maintenance' => 'تحت الصيانة',
                        default => $state,
                    }),
                    
                TextColumn::make('type')
                    ->label('النوع')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'residential' => 'سكني',
                        'commercial' => 'تجاري',
                        'mixed' => 'مختلط',
                        default => $state,
                    }),
                    
                TextColumn::make('location.name')
                    ->label('الموقع'),
                    
                TextColumn::make('area_sqm')
                    ->label('المساحة')
                    ->suffix(' م²'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'نشط',
                        'inactive' => 'غير نشط',
                        'maintenance' => 'تحت الصيانة'
                    ]),
                    
                SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        'residential' => 'سكني',
                        'commercial' => 'تجاري',
                        'mixed' => 'مختلط'
                    ]),
                    
                SelectFilter::make('owner')
                    ->label('المالك')
                    ->relationship('owner', 'name'),
            ])
            ->actions([
                ViewAction::make(),
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
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }
}