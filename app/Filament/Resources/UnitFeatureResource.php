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
use Illuminate\Database\Eloquent\Builder;
use Filament\GlobalSearch\GlobalSearchResult;
use Illuminate\Support\Collection;

class UnitFeatureResource extends Resource
{
    protected static ?string $model = UnitFeature::class;

    protected static ?string $navigationLabel = 'مميزات الوحدات';

    protected static ?string $modelLabel = 'ميزة وحدة';

    protected static ?string $pluralModelLabel = 'مميزات الوحدات';
    
    protected static ?string $recordTitleAttribute = 'name_ar';

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
                            ->visible(fn (callable $get) => $get('requires_value'))
                            ->reactive()
                            ->required(fn (callable $get) => $get('requires_value')),
                        
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
                            ->visible(fn (callable $get) => $get('requires_value') && $get('value_type') === 'select')
                            ->required(fn (callable $get) => $get('requires_value') && $get('value_type') === 'select')
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
            ]);
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
                
                Tables\Columns\TextColumn::make('category')
                    ->label('الفئة / Category')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => UnitFeature::getCategoryOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'basic' => 'primary',
                        'amenities' => 'success',
                        'safety' => 'warning',
                        'luxury' => 'danger',
                        'services' => 'secondary',
                        default => 'gray',
                    })
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
            ->recordActions([
                ViewAction::make()
                    ->label('عرض / View'),
                EditAction::make()
                    ->label('تعديل / Edit'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف / Delete'),
                ]),
            ])
            ->defaultSort('category')
            ->defaultSort('sort_order')
            ->defaultSort('name_ar')
            ->paginated(false);
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
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name_ar');
    }
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['name_ar', 'name_en', 'slug', 'description_ar', 'description_en', 'category'];
    }
    
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery();
    }
    
    public static function getGlobalSearchResults(string $search): Collection
    {
        // تنظيف البحث وإزالة الهمزات
        $normalizedSearch = str_replace(
            ['أ', 'إ', 'آ', 'ء', 'ؤ', 'ئ'],
            ['ا', 'ا', 'ا', '', 'و', 'ي'],
            $search
        );
        
        // إزالة المسافات الزائدة
        $searchWithoutSpaces = str_replace(' ', '', $normalizedSearch);
        $searchWithSpaces = str_replace(' ', '%', $normalizedSearch);
        
        // البحث في الأرقام
        $isNumeric = is_numeric($search);
        
        // التحقق من البحث بأسماء الفئات العربية وتحويلها للإنجليزية
        $categorySearch = null;
        $arabicCategories = [
            'اساسيات' => 'basic',
            'أساسيات' => 'basic',
            'مرافق' => 'amenities',
            'امان' => 'safety',
            'أمان' => 'safety',
            'فاخر' => 'luxury',
            'خدمات' => 'services',
        ];
        
        // البحث في أسماء الفئات العربية
        foreach ($arabicCategories as $arabic => $english) {
            if (stripos($arabic, $search) !== false || stripos($arabic, $normalizedSearch) !== false) {
                $categorySearch = $english;
                break;
            }
        }
        
        $query = static::getModel()::query();
        
        return $query->where(function (Builder $query) use ($normalizedSearch, $searchWithoutSpaces, $searchWithSpaces, $search, $isNumeric, $categorySearch) {
            // البحث العادي
            $query->where('name_ar', 'LIKE', "%{$search}%")
                  ->orWhere('name_en', 'LIKE', "%{$search}%")
                  ->orWhere('slug', 'LIKE', "%{$search}%")
                  ->orWhere('description_ar', 'LIKE', "%{$search}%")
                  ->orWhere('description_en', 'LIKE', "%{$search}%")
                  ->orWhere('category', 'LIKE', "%{$search}%")
                  // البحث بدون همزات في الاسم العربي
                  ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name_ar, 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ء', ''), 'ؤ', 'و'), 'ئ', 'ي') LIKE ?", ["%{$normalizedSearch}%"])
                  ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(description_ar, 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ء', ''), 'ؤ', 'و'), 'ئ', 'ي') LIKE ?", ["%{$normalizedSearch}%"])
                  // البحث بدون مسافات
                  ->orWhereRaw("REPLACE(name_ar, ' ', '') LIKE ?", ["%{$searchWithoutSpaces}%"])
                  ->orWhereRaw("REPLACE(name_en, ' ', '') LIKE ?", ["%{$searchWithoutSpaces}%"])
                  // البحث مع تجاهل المسافات في الكلمة المبحوث عنها
                  ->orWhere('name_ar', 'LIKE', "%{$searchWithSpaces}%")
                  ->orWhere('name_en', 'LIKE', "%{$searchWithSpaces}%");
                  
            // البحث بالفئة العربية المحولة
            if ($categorySearch) {
                $query->orWhere('category', '=', $categorySearch);
            }
            
            // البحث في قيم الفئات الإنجليزية مباشرة
            $englishCategories = ['basic', 'amenities', 'safety', 'luxury', 'services'];
            $searchLower = strtolower($search);
            foreach ($englishCategories as $cat) {
                if (stripos($cat, $searchLower) !== false) {
                    $query->orWhere('category', '=', $cat);
                }
            }
                  
            // البحث في الأرقام إذا كان البحث رقمي
            if ($isNumeric) {
                $query->orWhere('sort_order', '=', $search);
            }
        })
        ->limit(50)
        ->get()
        ->map(function ($record) {
            // الحصول على اسم الفئة
            $categoryName = UnitFeature::getCategoryOptions()[$record->category] ?? $record->category;
            
            // تحديد نوع القيمة
            $valueType = 'لا يتطلب قيمة';
            if ($record->requires_value) {
                $valueType = UnitFeature::getValueTypeOptions()[$record->value_type] ?? $record->value_type;
            }
            
            return new \Filament\GlobalSearch\GlobalSearchResult(
                title: $record->name_ar . ' / ' . $record->name_en,
                url: static::getUrl('edit', ['record' => $record]),
                details: [
                    'الفئة' => $categoryName,
                    'المعرف' => $record->slug,
                    'نوع القيمة' => $valueType,
                    'الحالة' => $record->is_active ? 'نشط' : 'غير نشط',
                ],
                actions: []
            );
        });
    }
}