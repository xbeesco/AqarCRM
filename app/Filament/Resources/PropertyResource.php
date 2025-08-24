<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyResource\Pages;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\PropertyStatus;
use App\Models\PropertyFeature;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\GlobalSearch\GlobalSearchResult;
use Illuminate\Database\Eloquent\Builder;
class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;
    
    protected static ?string $navigationLabel = 'العقارات';
    
    protected static ?string $modelLabel = 'عقار';
    
    protected static ?string $pluralModelLabel = 'العقارات';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('البيانات الأساسية')
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
                            ->preload()
                            ->required()
                            ->columnSpan(1),
                    ]),
                    
                    Grid::make(2)->schema([
                        Select::make('status_id')
                            ->label('حالة العقار')
                            ->options(PropertyStatus::where('is_active', true)->orderBy('sort_order')->pluck('name_ar', 'id'))
                            ->searchable()
                            ->required(),
                            
                        Select::make('type_id')
                            ->label('نوع العقار')
                            ->options(PropertyType::where('is_active', true)->orderBy('sort_order')->pluck('name_ar', 'id'))
                            ->searchable()
                            ->required(),
                    ]),
                ]),
                
            Section::make('الموقع والعنوان')
                ->schema([
                    Select::make('location_id')
                        ->label('الموقع')
                        ->relationship('location', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                        
                    Grid::make(2)->schema([
                        TextInput::make('address')
                            ->label('رقم المبنى واسم الشارع')
                            ->required()
                            ->columnSpan(1),
                            
                        TextInput::make('postal_code')
                            ->label('الرمز البريدي')
                            ->numeric()
                            ->columnSpan(1),
                    ]),
                ]),
                
            Section::make('تفاصيل إضافية')
                ->ColumnSpanFull()
                ->schema([
                    Grid::make(4)->schema([
                        TextInput::make('parking_spots')
                            ->label('عدد المواقف')
                            ->numeric()
                            ->nullable(),
                            
                        TextInput::make('elevators')
                            ->label('عدد المصاعد')
                            ->numeric()
                            ->nullable(),
                            
                        TextInput::make('floors_count')
                            ->label('عدد الطوابق')
                            ->numeric()
                            ->nullable(),
                            
                        TextInput::make('built_year')
                            ->label('سنة البناء')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(date('Y'))
                            ->nullable(),
                    ]),
                                        
                    CheckboxList::make('features')
                        ->label('المميزات')
                        ->relationship('features', 'name_ar')
                        ->columns(4)
                        ->columnSpanFull(),

                    Textarea::make('notes')
                        ->label('ملاحظات خاصة')
                        ->rows(2)
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
                    
                BadgeColumn::make('propertyStatus.name_ar')
                    ->label('الحالة')
                    ->color(fn ($record) => $record->propertyStatus?->color ? 
                        str_replace('#', '', $record->propertyStatus->color) : 'gray'),
                    
                TextColumn::make('propertyType.name_ar')
                    ->label('النوع'),
                    
                TextColumn::make('location.name')
                    ->label('الموقع'),
                    
                TextColumn::make('total_units')
                    ->label('عدد الوحدات')
                    ->default(0),
            ])
            ->filters([
                SelectFilter::make('owner')
                    ->label('المالك')
                    ->relationship('owner', 'name'),
                    
                SelectFilter::make('location')
                    ->label('الموقع')
                    ->relationship('location', 'name'),
            ], layout: \Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                // Remove bulk actions as per requirements
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

    //protected static ?string $recordTitleAttribute = 'name';
    
    // public static function getGloballySearchableAttributes(): array
    // {
    //     return ['name', 'address', 'notes', 'owner.name', 'location.name_ar', 'location.name_en'];
    // }
    
    // public static function getGlobalSearchResultDetails($record): array
    // {
    //     return [
    //         'المالك' => $record->owner?->name ?? 'غير محدد',
    //         'الموقع' => $record->location?->name_ar ?? 'غير محدد',
    //         'العنوان' => $record->address ?? 'غير محدد',
    //     ];
    // }
    
    // public static function getGlobalSearchEloquentQuery(): Builder
    // {
    //     return parent::getGlobalSearchEloquentQuery()->with(['owner', 'location']);
    // }
    
    // public static function getGlobalSearchResultActions($record): array
    // {
    //     return [
    //         Action::make('edit')
    //             ->label('تحرير')
    //             ->icon('heroicon-s-pencil')
    //             ->url(static::getUrl('edit', ['record' => $record])),
    //     ];
    // }
}