<?php

namespace App\Filament\Resources\PropertyFeatures;

use App\Filament\Resources\PropertyFeatures\Pages\ManagePropertyFeatures;
use App\Models\PropertyFeature;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PropertyFeatureResource extends Resource
{
    protected static ?string $model = PropertyFeature::class;
    
    protected static ?string $label = 'ميزة عقار';
    
    protected static ?string $pluralLabel = 'مميزات العقارات';

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
                    ->label('الاسم بالعربية'),
                TextColumn::make('name_en')
                    ->label('الاسم بالإنجليزية'),
                TextColumn::make('category')
                    ->label('الفئة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'basics' => 'أساسيات',
                        'amenities' => 'مرافق',
                        'security' => 'أمان',
                        'extras' => 'إضافات',
                        default => $state
                    }),
            ])
            ->searchable(false)
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([])
            ->bulkActions([])
            ->toggleColumnsTriggerAction(null)
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePropertyFeatures::route('/'),
        ];
    }
    
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name_ar', 'name_en', 'slug', 'category', 'icon', 
            'description_ar', 'description_en', 'value_type', 
            'sort_order', 'properties_count', 'is_active', 
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
                      
                      // البحث في الوصف
                      ->orWhere('description_ar', 'LIKE', "%{$search}%")
                      ->orWhere('description_en', 'LIKE', "%{$search}%")
                      
                      // البحث في الأيقونة
                      ->orWhere('icon', 'LIKE', "%{$search}%")
                      
                      // البحث في الأرقام
                      ->orWhere('sort_order', 'LIKE', "%{$search}%")
                      ->orWhere('properties_count', 'LIKE', "%{$search}%")
                      
                      // البحث في الفئة
                      ->orWhere(function($q) use ($search) {
                          $searchLower = strtolower($search);
                          if (strpos('أساسيات', $search) !== false || strpos('basics', $searchLower) !== false) {
                              $q->where('category', 'basics');
                          } elseif (strpos('مرافق', $search) !== false || strpos('amenities', $searchLower) !== false) {
                              $q->where('category', 'amenities');
                          } elseif (strpos('أمان', $search) !== false || strpos('security', $searchLower) !== false) {
                              $q->where('category', 'security');
                          } elseif (strpos('إضافات', $search) !== false || strpos('extras', $searchLower) !== false) {
                              $q->where('category', 'extras');
                          }
                      })
                      
                      // البحث في نوع القيمة
                      ->orWhere(function($q) use ($search) {
                          $searchLower = strtolower($search);
                          if (strpos('منطقي', $search) !== false || strpos('boolean', $searchLower) !== false) {
                              $q->where('value_type', 'boolean');
                          } elseif (strpos('رقم', $search) !== false || strpos('number', $searchLower) !== false) {
                              $q->where('value_type', 'number');
                          } elseif (strpos('نص', $search) !== false || strpos('text', $searchLower) !== false) {
                              $q->where('value_type', 'text');
                          } elseif (strpos('اختيار', $search) !== false || strpos('select', $searchLower) !== false) {
                              $q->where('value_type', 'select');
                          }
                      })
                      
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
                      
                      // البحث في الحالة
                      ->orWhere(function($q) use ($search) {
                          $searchLower = strtolower($search);
                          if (strpos('نشط', $search) !== false || strpos('active', $searchLower) !== false) {
                              $q->where('is_active', 1);
                          } elseif (strpos('غير نشط', $search) !== false || strpos('inactive', $searchLower) !== false || strpos('معطل', $search) !== false) {
                              $q->where('is_active', 0);
                          }
                      })
                      
                      // البحث في requires_value
                      ->orWhere(function($q) use ($search) {
                          if (strpos('يتطلب قيمة', $search) !== false || strpos('requires value', strtolower($search)) !== false) {
                              $q->where('requires_value', 1);
                          } elseif (strpos('لا يتطلب قيمة', $search) !== false || strpos('no value', strtolower($search)) !== false) {
                              $q->where('requires_value', 0);
                          }
                      });
            })
            ->limit(50)
            ->get()
            ->map(function ($record) {
                $categoryNames = [
                    'basics' => 'أساسيات',
                    'amenities' => 'مرافق',
                    'security' => 'أمان',
                    'extras' => 'إضافات',
                ];
                
                $valueTypeNames = [
                    'boolean' => 'نعم/لا',
                    'number' => 'رقم',
                    'text' => 'نص',
                    'select' => 'اختيار',
                ];
                
                $details = [
                    'الاسم بالإنجليزية' => $record->name_en ?? 'غير محدد',
                    'الفئة' => $categoryNames[$record->category] ?? $record->category,
                    'نوع القيمة' => $valueTypeNames[$record->value_type] ?? $record->value_type,
                    'الحالة' => $record->is_active ? 'نشط' : 'غير نشط',
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
