# Unit Model Tasks - Al-Hiaa Real Estate Management System
**Target Stack:** Laravel 12 & Filament 4

## المبادئ الأساسية للتحسين
- ✅ استخدام Filament's built-in Form components بدلاً من ACF معقد
- ✅ تبسيط Unit management مع Property relationship
- ✅ استخدام Filament's advanced filtering بدلاً من custom search
- ✅ استخدام Laravel's Eloquent relationships مباشرة

## 1. نموذج الوحدة المبسط

### 1.1 Unit Model مع العلاقات الأساسية
```php
class Unit extends Model
{
    protected $fillable = [
        'name', 'property_id', 'tenant_id', 'rent_price', 'type', 'status',
        'floor_number', 'rooms_count', 'bathrooms_count', 'area_sqm',
        'has_balcony', 'is_furnished', 'notes'
    ];
    
    protected $casts = [
        'rent_price' => 'decimal:2',
        'area_sqm' => 'decimal:2',
        'has_balcony' => 'boolean',
        'is_furnished' => 'boolean',
    ];
    
    // العلاقات الأساسية
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
    
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }
    
    public function contracts(): HasMany
    {
        return $this->hasMany(UnitContract::class);
    }
    
    public function activeContract(): HasOne
    {
        return $this->hasOne(UnitContract::class)
            ->where('status', 'active');
    }
    
    // دوال مبسطة
    public function isAvailable(): bool
    {
        return $this->tenant_id === null && $this->status === 'available';
    }
    
    public function getMonthlyIncome(): float
    {
        return $this->tenant_id ? $this->rent_price : 0;
    }
}
```

### 1.2 إزالة التعقيدات غير الضرورية
- **❌ إزالة**: UnitService معقد
- **❌ إزالة**: UnitRepository 
- **❌ إزالة**: UnitObserver معقد
- **✅ بدلاً منها**: استخدام Eloquent مباشرة مع scope methods

## 2. Filament Resource للوحدات

### 2.1 Unit Resource مع Filament 4 Syntax
```php
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;

public static function form(Schema $schema): Schema
{
    return $schema->schema([
        Section::make('بيانات الوحدة الأساسية')
            ->schema([
                Grid::make(3)->schema([
                    TextInput::make('name')
                        ->label('اسم الوحدة')
                        ->required()
                        ->columnSpan(1),
                        
                    Select::make('property_id')
                        ->label('تابعة إلى عقار')
                        ->relationship('property', 'name')
                        ->searchable()
                        ->required()
                        ->reactive()
                        ->columnSpan(1),
                        
                    Select::make('tenant_id')
                        ->label('المستأجر')
                        ->relationship('tenant', 'name')
                        ->searchable()
                        ->nullable()
                        ->columnSpan(1),
                ]),
                
                Grid::make(3)->schema([
                    TextInput::make('rent_price')
                        ->label('سعر الإيجار')
                        ->numeric()
                        ->required()
                        ->prefix('ر.س')
                        ->columnSpan(1),
                        
                    Select::make('type')
                        ->label('نوع الوحدة')
                        ->options([
                            'apartment' => 'شقة',
                            'villa' => 'فيلا',
                            'office' => 'مكتب',
                            'shop' => 'متجر'
                        ])
                        ->required()
                        ->columnSpan(1),
                        
                    Select::make('status')
                        ->label('حالة الوحدة')
                        ->options([
                            'available' => 'متاحة',
                            'occupied' => 'مؤجرة',
                            'maintenance' => 'تحت الصيانة',
                            'reserved' => 'محجوزة'
                        ])
                        ->required()
                        ->columnSpan(1),
                ]),
            ]),
            
        Section::make('تفاصيل الوحدة')
            ->schema([
                Grid::make(4)->schema([
                    TextInput::make('rooms_count')
                        ->label('عدد الغرف')
                        ->numeric()
                        ->default(0)
                        ->columnSpan(1),
                        
                    TextInput::make('bathrooms_count')
                        ->label('عدد دورات المياه')
                        ->numeric()
                        ->default(0)
                        ->columnSpan(1),
                        
                    TextInput::make('floor_number')
                        ->label('طابق الوحدة')
                        ->numeric()
                        ->columnSpan(1),
                        
                    TextInput::make('area_sqm')
                        ->label('المساحة (م²)')
                        ->numeric()
                        ->suffix('م²')
                        ->columnSpan(1),
                ]),
                
                Grid::make(2)->schema([
                    Toggle::make('has_balcony')
                        ->label('يوجد شرفة')
                        ->columnSpan(1),
                        
                    Toggle::make('is_furnished')
                        ->label('مفروشة')
                        ->columnSpan(1),
                ]),
                
                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3)
                    ->columnSpanFull(),
            ]),
    ]);
}
```

### 2.2 Unit Table مع Advanced Filtering (مبسط)
```php
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
                
            TextColumn::make('tenant.name')
                ->label('المستأجر')
                ->default('متاحة'),
                
            TextColumn::make('rent_price')
                ->label('سعر الإيجار')
                ->money('SAR')
                ->sortable(),
                
            BadgeColumn::make('status')
                ->label('الحالة')
                ->colors([
                    'success' => 'available',
                    'info' => 'occupied',
                    'warning' => 'maintenance',
                    'secondary' => 'reserved',
                ]),
                
            TextColumn::make('rooms_count')
                ->label('الغرف'),
                
            TextColumn::make('bathrooms_count')
                ->label('الحمامات'),
        ])
        ->filters([
            // الفلتر المتقدم المطلوب من ACF
            Filter::make('advanced_search')
                ->form([
                    Grid::make(2)->schema([
                        Select::make('property_id')
                            ->label('العقار')
                            ->relationship('property', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpan(2),
                            
                        TextInput::make('rooms_min')
                            ->label('عدد الغرف (حد أدنى)')
                            ->numeric()
                            ->columnSpan(1),
                            
                        TextInput::make('bathrooms_min')
                            ->label('عدد الحمامات (حد أدنى)')
                            ->numeric()
                            ->columnSpan(1),
                            
                        TextInput::make('price_min')
                            ->label('السعر (حد أدنى)')
                            ->numeric()
                            ->prefix('ر.س')
                            ->columnSpan(1),
                            
                        TextInput::make('price_max')
                            ->label('السعر (حد أقصى)')
                            ->numeric()
                            ->prefix('ر.س')
                            ->columnSpan(1),
                    ])
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['property_id'], fn ($q) => $q->where('property_id', $data['property_id']))
                        ->when($data['rooms_min'], fn ($q) => $q->where('rooms_count', '>=', $data['rooms_min']))
                        ->when($data['bathrooms_min'], fn ($q) => $q->where('bathrooms_count', '>=', $data['bathrooms_min']))
                        ->when($data['price_min'], fn ($q) => $q->where('rent_price', '>=', $data['price_min']))
                        ->when($data['price_max'], fn ($q) => $q->where('rent_price', '<=', $data['price_max']));
                }),
                
            SelectFilter::make('status')
                ->label('الحالة')
                ->options([
                    'available' => 'متاحة',
                    'occupied' => 'مؤجرة',
                    'maintenance' => 'تحت الصيانة'
                ]),
                
            SelectFilter::make('type')
                ->label('النوع')
                ->options([
                    'apartment' => 'شقة',
                    'villa' => 'فيلا',
                    'office' => 'مكتب'
                ]),
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
        ]);
}
```

## 3. تبسيط العقود في الوحدة

### 3.1 استخدام Filament Relation Manager للعقود
```php
class ContractsRelationManager extends RelationManager
{
    protected static string $relationship = 'contracts';
    
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('اسم العقد'),
                TextColumn::make('start_date')->label('تاريخ البداية')->date(),
                TextColumn::make('duration_months')->label('مدة العقد')->suffix(' شهر'),
                BadgeColumn::make('status')->label('الحالة'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }
}
```

## 4. التبسيطات المطبقة

### 4.1 إزالة Over-Engineering
- **❌ إزالة**: UnitService مع دوال معقدة
- **❌ إزالة**: UnitRepository مع queries معقدة
- **❌ إزالة**: UnitAvailabilityChecker
- **❌ إزالة**: UnitPricingCalculator  
- **✅ بدلاً منها**: دوال بسيطة في Model مباشرة

### 4.2 استخدام Filament's Advanced Filtering
- **✅ فلتر متقدم**: تطبيق نفس فلتر ACF مع Filament's Filter component
- **✅ تخطيط مبسط**: استخدام Grid بدلاً من ACF complex layouts
- **✅ validation**: استخدام Filament's built-in validation

### 4.3 تبسيط Features Management
```php
// بدلاً من UnitFeature pivot table معقد
public function hasFeature(string $feature): bool
{
    return in_array($feature, $this->features ?? []);
}

// استخدام JSON field للميزات البسيطة
protected $casts = [
    'features' => 'array',
];
```

## 5. Property-Unit Relationship (مبسط)

### 5.1 Dynamic Unit Loading في Property
```php
// في Property Resource Form
Select::make('property_id')
    ->label('العقار')
    ->relationship('property', 'name')
    ->reactive()
    ->afterStateUpdated(function (callable $set, $state) {
        // يمكن إضافة logic لتحديث معلومات العقار
        $set('property_owner', Property::find($state)?->owner->name);
    }),
```

### 5.2 إزالة المكونات المعقدة
- **❌ إزالة**: PropertyUnitRelationship معقد
- **❌ إزالة**: UnitFormSchema منفصل
- **✅ بدلاً منها**: استخدام Filament's reactive selects

## 6. Migration من WordPress

### 6.1 Unit Import مبسط
```php
class UnitImportService
{
    public function importUnits(): void
    {
        $wpUnits = $this->getWordPressUnits();
        
        foreach ($wpUnits as $wpUnit) {
            Unit::create([
                'name' => $wpUnit->post_title,
                'property_id' => $this->mapProperty($wpUnit->property_id),
                'tenant_id' => $this->mapTenant($wpUnit->tenant_id),
                'rent_price' => $wpUnit->rent_price,
                'type' => $this->mapType($wpUnit->type),
                'status' => $this->mapStatus($wpUnit->status),
                'rooms_count' => $wpUnit->rooms_count,
                'bathrooms_count' => $wpUnit->bathrooms_count,
            ]);
        }
    }
}
```

### 6.2 تبسيط ACF Features Migration
- **❌ إزالة**: معالجة معقدة لـ acfe_taxonomy_terms
- **✅ بدلاً منها**: حفظ الميزات في JSON field بسيط

## 7. الاختبارات المبسطة

### 7.1 اختبارات أساسية فقط
```php
class UnitModelTest extends TestCase
{
    public function test_unit_belongs_to_property()
    {
        $property = Property::factory()->create();
        $unit = Unit::factory()->create(['property_id' => $property->id]);
        
        $this->assertEquals($property->id, $unit->property->id);
    }
    
    public function test_unit_availability()
    {
        $unit = Unit::factory()->create(['tenant_id' => null, 'status' => 'available']);
        
        $this->assertTrue($unit->isAvailable());
    }
    
    public function test_unit_advanced_filter()
    {
        Unit::factory()->create(['rooms_count' => 3, 'rent_price' => 2000]);
        Unit::factory()->create(['rooms_count' => 2, 'rent_price' => 1500]);
        
        $filtered = Unit::where('rooms_count', '>=', 3)
            ->where('rent_price', '>=', 1800)
            ->get();
            
        $this->assertCount(1, $filtered);
    }
}
```

### 7.2 Feature Tests للفلتر المتقدم
```php
class UnitAdvancedFilterTest extends TestCase
{
    public function test_price_range_filter()
    {
        // اختبار فلتر النطاق السعري
    }
    
    public function test_rooms_bathrooms_filter()
    {
        // اختبار فلتر الغرف والحمامات
    }
}
```

## 8. Scopes للبحث والفلترة

### 8.1 Unit Scopes مبسطة
```php
// في Unit Model
public function scopeAvailable($query)
{
    return $query->whereNull('tenant_id')->where('status', 'available');
}

public function scopeByProperty($query, $propertyId)
{
    return $query->where('property_id', $propertyId);
}

public function scopeInPriceRange($query, $min, $max)
{
    return $query->whereBetween('rent_price', [$min, $max]);
}

public function scopeMinRooms($query, $minRooms)
{
    return $query->where('rooms_count', '>=', $minRooms);
}
```

## الملخص النهائي

تم تبسيط نظام الوحدات ليستخدم:
1. **Filament's advanced filtering** بدلاً من custom search components
2. **Simple Eloquent relationships** بدلاً من Repository pattern
3. **Built-in Filament components** لجميع النماذج والعرض
4. **JSON fields** للميزات البسيطة بدلاً من pivot tables معقدة
5. **Filament's reactive forms** للتفاعل بين العقار والوحدات
6. **Model scopes** بدلاً من service classes معقدة

هذا النهج يحافظ على جميع متطلبات البحث المتقدم والفلترة مع تبسيط كبير في التعقيد.