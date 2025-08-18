<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitFeatureResource\Pages;
use App\Models\UnitFeature;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use BackedEnum;

class UnitFeatureResource extends Resource
{
    protected static ?string $model = UnitFeature::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'مميزات الوحدات / Unit Features';

    protected static ?string $modelLabel = 'ميزة وحدة / Unit Feature';

    protected static ?string $pluralModelLabel = 'مميزات الوحدات / Unit Features';


    protected static ?int $navigationSort = 3;

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
                        
                        Select::make('category')
                            ->label('الفئة / Category')
                            ->required()
                            ->options(UnitFeature::getCategoryOptions())
                            ->default('basic'),
                        
                        TextInput::make('icon')
                            ->label('الأيقونة / Icon')
                            ->maxLength(50)
                            ->default('heroicon-o-star')
                            ->placeholder('heroicon-o-star'),
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

                Section::make('إعدادات القيمة / Value Settings')
                    ->schema([
                        Toggle::make('requires_value')
                            ->label('يتطلب قيمة / Requires Value')
                            ->reactive()
                            ->default(false)
                            ->helperText('هل تحتاج هذه الميزة إلى قيمة إضافية؟'),
                        
                        Select::make('value_type')
                            ->label('نوع القيمة / Value Type')
                            ->options(UnitFeature::getValueTypeOptions())
                            ->visible(fn ($get) => $get('requires_value'))
                            ->reactive()
                            ->required(fn ($get) => $get('requires_value')),
                        
                        Repeater::make('value_options_repeater')
                            ->label('خيارات القيمة / Value Options')
                            ->schema([
                                TextInput::make('key')
                                    ->label('المفتاح / Key')
                                    ->required(),
                                TextInput::make('value')
                                    ->label('القيمة / Value')
                                    ->required(),
                            ])
                            ->visible(fn ($get) => $get('requires_value') && $get('value_type') === 'select')
                            ->required(fn ($get) => $get('requires_value') && $get('value_type') === 'select')
                            ->addActionLabel('إضافة خيار / Add Option')
                            ->minItems(1)
                            ->collapsible()
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('الإعدادات / Settings')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('نشط / Active')
                            ->default(true),
                        
                        TextInput::make('sort_order')
                            ->label('ترتيب العرض / Sort Order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])->columns(2),
            ])
            ->mutateFormDataBeforeSave(function (array $data): array {
                // Convert value_options_repeater to value_options JSON
                if (isset($data['value_options_repeater']) && is_array($data['value_options_repeater'])) {
                    $options = [];
                    foreach ($data['value_options_repeater'] as $option) {
                        if (!empty($option['key']) && !empty($option['value'])) {
                            $options[$option['key']] = $option['value'];
                        }
                    }
                    $data['value_options'] = $options;
                    unset($data['value_options_repeater']);
                }
                
                return $data;
            })
            ->mutateFormDataBeforeFill(function (array $data): array {
                // Convert value_options JSON to value_options_repeater array
                if (isset($data['value_options']) && is_array($data['value_options'])) {
                    $repeaterData = [];
                    foreach ($data['value_options'] as $key => $value) {
                        $repeaterData[] = ['key' => $key, 'value' => $value];
                    }
                    $data['value_options_repeater'] = $repeaterData;
                }
                
                return $data;
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_display')
                    ->label('الاسم / Name')
                    ->formatStateUsing(fn (UnitFeature $record): string => "{$record->name_ar} / {$record->name_en}")
                    ->searchable(['name_ar', 'name_en'])
                    ->sortable(['name_ar']),
                
                Tables\Columns\TextColumn::make('slug')
                    ->label('المعرف / Slug')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                
                Tables\Columns\BadgeColumn::make('category')
                    ->label('الفئة / Category')
                    ->formatStateUsing(fn (string $state): string => UnitFeature::getCategoryOptions()[$state] ?? $state)
                    ->colors([
                        'primary' => 'basic',
                        'success' => 'amenities',
                        'warning' => 'safety',
                        'danger' => 'luxury',
                        'secondary' => 'services',
                    ])
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('value_configuration')
                    ->label('إعدادات القيمة / Value Configuration')
                    ->formatStateUsing(function (UnitFeature $record): string {
                        if (!$record->requires_value) {
                            return 'لا يتطلب قيمة / No Value Required';
                        }
                        
                        $type = UnitFeature::getValueTypeOptions()[$record->value_type] ?? $record->value_type;
                        
                        if ($record->value_type === 'select' && $record->value_options) {
                            $optionsCount = count($record->value_options);
                            return "{$type} ({$optionsCount} خيارات / options)";
                        }
                        
                        return $type;
                    }),
                
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
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filters([
                SelectFilter::make('category')
                    ->label('الفئة / Category')
                    ->options(UnitFeature::getCategoryOptions()),
                
                TernaryFilter::make('requires_value')
                    ->label('يتطلب قيمة / Requires Value')
                    ->trueLabel('يتطلب قيمة / Requires Value')
                    ->falseLabel('لا يتطلب قيمة / No Value Required')
                    ->placeholder('الكل / All'),
                
                SelectFilter::make('value_type')
                    ->label('نوع القيمة / Value Type')
                    ->options(UnitFeature::getValueTypeOptions()),
                
                TernaryFilter::make('is_active')
                    ->label('نشط / Active')
                    ->trueLabel('نشط / Active')
                    ->falseLabel('غير نشط / Inactive')
                    ->placeholder('الكل / All'),
            ])
            ->actions([
                ViewAction::make()
                    ->label('عرض / View'),
                EditAction::make()
                    ->label('تعديل / Edit'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف / Delete'),
                ]),
            ])
            ->defaultSort('category')
            ->defaultSort('sort_order')
            ->defaultSort('name_ar');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnitFeatures::route('/'),
            'create' => Pages\CreateUnitFeature::route('/create'),
            'view' => Pages\ViewUnitFeature::route('/{record}'),
            'edit' => Pages\EditUnitFeature::route('/{record}/edit'),
        ];
    }
}