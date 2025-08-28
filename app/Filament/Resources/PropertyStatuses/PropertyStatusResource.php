<?php

namespace App\Filament\Resources\PropertyStatuses;

use App\Filament\Resources\PropertyStatuses\Pages\ManagePropertyStatuses;
use App\Models\PropertyStatus;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PropertyStatusResource extends Resource
{
    protected static ?string $model = PropertyStatus::class;
    
    protected static ?string $label = 'حالة عقار';
    
    protected static ?string $pluralLabel = 'حالات العقارات';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->required()
                    ->maxLength(255),
                TextInput::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->searchable(),
                TextColumn::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->searchable(),
            ])
            ->searchable(false)
            ->filters([])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->toolbarActions([])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePropertyStatuses::route('/'),
        ];
    }
    
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name_ar', 'name_en', 'slug', 'color', 'icon',
            'description_ar', 'description_en', 'sort_order',
            'properties_count', 'is_available', 'is_active',
            'created_at', 'updated_at'
        ];
    }
    
    public static function getGlobalSearchResults(string $search): Collection
    {
        $normalizedSearch = str_replace(
            ['أ', 'إ', 'آ', 'ء', 'ؤ', 'ئ'],
            ['ا', 'ا', 'ا', '', 'و', 'ي'],
            $search
        );
        
        $searchWithoutSpaces = str_replace(' ', '', $normalizedSearch);
        
        return static::getModel()::query()
            ->where(function ($query) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                // البحث في الأسماء
                $query->where('name_ar', 'LIKE', "%{$search}%")
                      ->orWhere('name_en', 'LIKE', "%{$search}%")
                      
                      // البحث في slug
                      ->orWhere('slug', 'LIKE', "%{$search}%")
                      
                      // البحث في اللون
                      ->orWhere('color', 'LIKE', "%{$search}%")
                      
                      // البحث في الأيقونة
                      ->orWhere('icon', 'LIKE', "%{$search}%")
                      
                      // البحث في الوصف
                      ->orWhere('description_ar', 'LIKE', "%{$search}%")
                      ->orWhere('description_en', 'LIKE', "%{$search}%")
                      
                      // البحث في الأرقام
                      ->orWhere('sort_order', 'LIKE', "%{$search}%")
                      ->orWhere('properties_count', 'LIKE', "%{$search}%")
                      
                      // البحث بدون همزات
                      ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name_ar, 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ء', ''), 'ؤ', 'و'), 'ئ', 'ي') LIKE ?", ["%{$normalizedSearch}%"])
                      ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(description_ar, 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ء', ''), 'ؤ', 'و'), 'ئ', 'ي') LIKE ?", ["%{$normalizedSearch}%"])
                      
                      // البحث بدون مسافات
                      ->orWhereRaw("REPLACE(name_ar, ' ', '') LIKE ?", ["%{$searchWithoutSpaces}%"])
                      ->orWhereRaw("REPLACE(name_en, ' ', '') LIKE ?", ["%{$searchWithoutSpaces}%"])
                      ->orWhereRaw("REPLACE(description_ar, ' ', '') LIKE ?", ["%{$searchWithoutSpaces}%"])
                      ->orWhereRaw("REPLACE(description_en, ' ', '') LIKE ?", ["%{$searchWithoutSpaces}%"])
                      
                      // البحث في التواريخ
                      ->orWhereRaw("DATE_FORMAT(created_at, '%Y-%m-%d') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("DATE_FORMAT(created_at, '%d-%m-%Y') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("DATE_FORMAT(created_at, '%Y/%m/%d') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("DATE_FORMAT(created_at, '%d/%m/%Y') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("DATE_FORMAT(updated_at, '%Y-%m-%d') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("DATE_FORMAT(updated_at, '%d-%m-%Y') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("DATE_FORMAT(updated_at, '%Y/%m/%d') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("DATE_FORMAT(updated_at, '%d/%m/%Y') LIKE ?", ["%{$search}%"])
                      
                      // البحث في حالة النشاط
                      ->orWhere(function($q) use ($search) {
                          $searchLower = strtolower($search);
                          if (strpos('نشط', $search) !== false || strpos('active', $searchLower) !== false) {
                              $q->where('is_active', 1);
                          } elseif (strpos('غير نشط', $search) !== false || strpos('inactive', $searchLower) !== false || strpos('معطل', $search) !== false) {
                              $q->where('is_active', 0);
                          }
                      })
                      
                      // البحث في حالة التوفر
                      ->orWhere(function($q) use ($search) {
                          $searchLower = strtolower($search);
                          if (strpos('متاح', $search) !== false || strpos('available', $searchLower) !== false) {
                              $q->where('is_available', 1);
                          } elseif (strpos('غير متاح', $search) !== false || strpos('unavailable', $searchLower) !== false || strpos('محجوز', $search) !== false) {
                              $q->where('is_available', 0);
                          }
                      })
                      
                      // البحث في الألوان بالعربي
                      ->orWhere(function($q) use ($search) {
                          $colorMap = [
                              'أخضر' => 'green',
                              'أحمر' => 'red',
                              'أزرق' => 'blue',
                              'أصفر' => 'yellow',
                              'برتقالي' => 'orange',
                              'رمادي' => 'gray',
                              'أسود' => 'black',
                              'أبيض' => 'white',
                              'بنفسجي' => 'purple',
                              'وردي' => 'pink',
                          ];
                          
                          foreach ($colorMap as $arabicColor => $englishColor) {
                              if (strpos($search, $arabicColor) !== false) {
                                  $q->orWhere('color', $englishColor);
                              }
                          }
                      });
            })
            ->limit(50)
            ->get()
            ->map(function ($record) {
                $colorNames = [
                    'green' => 'أخضر',
                    'red' => 'أحمر',
                    'blue' => 'أزرق',
                    'yellow' => 'أصفر',
                    'orange' => 'برتقالي',
                    'gray' => 'رمادي',
                    'black' => 'أسود',
                    'white' => 'أبيض',
                    'purple' => 'بنفسجي',
                    'pink' => 'وردي',
                ];
                
                $details = [
                    'الاسم بالإنجليزية' => $record->name_en ?? 'غير محدد',
                    'اللون' => $colorNames[$record->color] ?? $record->color,
                    'متاح' => $record->is_available ? 'نعم' : 'لا',
                    'نشط' => $record->is_active ? 'نعم' : 'لا',
                ];
                
                if ($record->properties_count > 0) {
                    $details['عدد العقارات'] = $record->properties_count;
                }
                
                if ($record->description_ar) {
                    $details['الوصف'] = \Str::limit($record->description_ar, 50);
                }
                
                return new \Filament\GlobalSearch\GlobalSearchResult(
                    title: $record->name_ar,
                    url: static::getUrl('index'),
                    details: $details,
                    actions: []
                );
            });
    }
}
