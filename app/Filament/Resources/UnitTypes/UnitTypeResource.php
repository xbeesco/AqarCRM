<?php

namespace App\Filament\Resources\UnitTypes;

use App\Filament\Resources\UnitTypes\Pages\ManageUnitTypes;
use App\Models\UnitType;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UnitTypeResource extends Resource
{
    protected static ?string $model = UnitType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    
    protected static ?string $navigationLabel = 'أنواع الوحدات';
    
    protected static ?string $modelLabel = 'نوع وحدة';
    
    protected static ?string $pluralModelLabel = 'أنواع الوحدات';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->required()
                    ->maxLength(100),
                
                TextInput::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->required()
                    ->maxLength(100),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name_ar')
            ->columns([
                TextColumn::make('name_ar')
                    ->label('الاسم بالعربية'),
                
                TextColumn::make('name_en')
                    ->label('الاسم بالإنجليزية'),
            ])
            ->paginated(false)
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->toolbarActions([
                // إزالة bulk actions
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUnitTypes::route('/'),
        ];
    }
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['name_ar', 'name_en', 'slug', 'description', 'icon', 'sort_order', 'is_active', 'created_at', 'updated_at'];
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
                      ->orWhere('description', 'LIKE', "%{$search}%")
                      
                      // البحث في الأيقونة
                      ->orWhere('icon', 'LIKE', "%{$search}%")
                      
                      // البحث في رقم الترتيب
                      ->orWhere('sort_order', 'LIKE', "%{$search}%")
                      
                      // البحث بدون همزات في الاسم العربي
                      ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name_ar, 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ء', ''), 'ؤ', 'و'), 'ئ', 'ي') LIKE ?", ["%{$normalizedSearch}%"])
                      
                      // البحث بدون همزات في الوصف
                      ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(description, 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ء', ''), 'ؤ', 'و'), 'ئ', 'ي') LIKE ?", ["%{$normalizedSearch}%"])
                      
                      // البحث بدون مسافات
                      ->orWhereRaw("REPLACE(name_ar, ' ', '') LIKE ?", ["%{$searchWithoutSpaces}%"])
                      ->orWhereRaw("REPLACE(name_en, ' ', '') LIKE ?", ["%{$searchWithoutSpaces}%"])
                      ->orWhereRaw("REPLACE(description, ' ', '') LIKE ?", ["%{$searchWithoutSpaces}%"])
                      
                      // البحث في التواريخ - تاريخ الإنشاء
                      ->orWhereRaw("DATE_FORMAT(created_at, '%Y-%m-%d') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("DATE_FORMAT(created_at, '%d-%m-%Y') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("DATE_FORMAT(created_at, '%Y/%m/%d') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("DATE_FORMAT(created_at, '%d/%m/%Y') LIKE ?", ["%{$search}%"])
                      
                      // البحث في التواريخ - تاريخ التحديث
                      ->orWhereRaw("DATE_FORMAT(updated_at, '%Y-%m-%d') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("DATE_FORMAT(updated_at, '%d-%m-%Y') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("DATE_FORMAT(updated_at, '%Y/%m/%d') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("DATE_FORMAT(updated_at, '%d/%m/%Y') LIKE ?", ["%{$search}%"])
                      
                      // البحث في الحالة (نشط/غير نشط)
                      ->orWhere(function($q) use ($search) {
                          $searchLower = strtolower($search);
                          if (strpos('نشط', $search) !== false || strpos('active', $searchLower) !== false) {
                              $q->where('is_active', 1);
                          } elseif (strpos('غير نشط', $search) !== false || strpos('inactive', $searchLower) !== false || strpos('معطل', $search) !== false) {
                              $q->where('is_active', 0);
                          }
                      });
            })
            ->limit(50)
            ->get()
            ->map(function ($record) {
                $details = [
                    'الاسم بالإنجليزية' => $record->name_en ?? 'غير محدد',
                    'الحالة' => $record->is_active ? 'نشط' : 'غير نشط',
                    'تاريخ الإنشاء' => $record->created_at?->format('Y-m-d') ?? 'غير محدد',
                ];
                
                if ($record->description) {
                    $details['الوصف'] = \Str::limit($record->description, 50);
                }
                
                if ($record->sort_order > 0) {
                    $details['الترتيب'] = $record->sort_order;
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
