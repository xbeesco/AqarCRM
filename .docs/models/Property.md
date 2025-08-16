# Property Model Tasks - Al-Hiaa Real Estate Management System
**Target Stack:** Laravel 12 & Filament 4

## المبادئ الأساسية للتحسين
- ✅ استخدام Filament's built-in Form components بدلاً من ACF custom fields
- ✅ استخدام Laravel's Eloquent relationships بدلاً من Repository pattern
- ✅ استخدام Filament's Resource مع built-in features
- ✅ تبسيط Property management بدون over-engineering

## 1. نموذج العقار المبسط

### 1.1 Property Model مع العلاقات الأساسية
```php
class Property extends Model
{
    protected $fillable = [
        'name', 'owner_id', 'status', 'type', 'location_id',
        'address', 'postal_code', 'parking_spots', 'elevators', 
        'area_sqm', 'build_year', 'floors_count', 'notes'
    ];
    
    protected $casts = [
        'build_year' => 'integer',
        'area_sqm' => 'decimal:2',
    ];
    
    // العلاقات الأساسية
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
    
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
    
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }
    
    public function contracts(): HasMany
    {
        return $this->hasMany(PropertyContract::class);
    }
    
    // دوال مبسطة
    public function getOccupancyRate(): float
    {
        $totalUnits = $this->units()->count();
        if ($totalUnits === 0) return 0;
        
        $occupiedUnits = $this->units()->whereNotNull('tenant_id')->count();
        return ($occupiedUnits / $totalUnits) * 100;
    }
}
```

### 1.2 إزالة التعقيدات غير الضرورية
- **❌ إزالة**: PropertyService معقد
- **❌ إزالة**: PropertyRepository
- **❌ إزالة**: PropertyObserver معقد
- **✅ بدلاً منها**: استخدام Eloquent مباشرة

## 2. Filament Resource للعقارات

### 2.1 Property Resource مع Filament 4 Syntax
```php
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

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
                // استخدام Location cascade (مبسط)
                Select::make('location_id')
                    ->label('الموقع')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->required(),
                    
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
                Grid::make(3)->schema([
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
                ]),
                
                Textarea::make('notes')
                    ->label('ملاحظات خاصة')
                    ->rows(3)
                    ->columnSpanFull(),
            ]),
    ]);
}
```

### 2.2 Property Table مع Filament's Built-in Features
```php
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
                ]),
                
            TextColumn::make('units_count')
                ->label('عدد الوحدات')
                ->counts('units'),
                
            TextColumn::make('occupancy_rate')
                ->label('معدل الإشغال')
                ->formatStateUsing(fn ($record) => number_format($record->getOccupancyRate(), 1) . '%'),
        ])
        ->filters([
            SelectFilter::make('status')
                ->label('الحالة')
                ->options([
                    'active' => 'نشط',
                    'inactive' => 'غير نشط',
                    'maintenance' => 'تحت الصيانة'
                ]),
                
            SelectFilter::make('owner')
                ->label('المالك')
                ->relationship('owner', 'name'),
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
}
```

## 3. تبسيط الوحدات (Units) في Property

### 3.1 استخدام Filament Relation Managers
```php
class UnitsRelationManager extends RelationManager
{
    protected static string $relationship = 'units';
    
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('اسم الوحدة'),
                TextColumn::make('rent_price')->label('سعر الإيجار')->money('SAR'),
                TextColumn::make('tenant.name')->label('المستأجر'),
                BadgeColumn::make('status')->label('الحالة'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
```

### 3.2 إضافة Relation Manager للعقار
```php
// في PropertyResource
public static function getRelations(): array
{
    return [
        UnitsRelationManager::class,
        ContractsRelationManager::class,
    ];
}
```

## 4. التبسيطات المطبقة

### 4.1 إزالة Over-Engineering
- **❌ إزالة**: PropertyService معقد مع دوال معقدة
- **❌ إزالة**: PropertyRepository مع queries معقدة  
- **❌ إزالة**: PropertyObserver مع logic معقد
- **❌ إزالة**: PropertyMetrics حسابات معقدة
- **✅ بدلاً منها**: دوال بسيطة في Model مباشرة

### 4.2 استخدام Filament's Built-in Features
- **Form Building**: استخدام Filament's form components
- **Data Display**: استخدام Filament's table columns
- **Filtering**: استخدام Filament's built-in filters
- **Relations**: استخدام Filament's relation managers

### 4.3 تبسيط الحسابات
```php
// بدلاً من PropertyMetrics service معقد
public function getMonthlyRevenue(): float
{
    return $this->units()
        ->whereNotNull('tenant_id')
        ->sum('rent_price');
}

public function getAvailableUnits(): int
{
    return $this->units()
        ->whereNull('tenant_id')
        ->count();
}
```

## 5. Migration من WordPress

### 5.1 Property Import مبسط
```php
class PropertyImportService
{
    public function importProperties(): void
    {
        $wpProperties = $this->getWordPressProperties();
        
        foreach ($wpProperties as $wpProperty) {
            Property::create([
                'name' => $wpProperty->post_title,
                'owner_id' => $this->mapOwner($wpProperty->owner),
                'status' => $this->mapStatus($wpProperty->status),
                'type' => $this->mapType($wpProperty->type),
                'location_id' => $this->mapLocation($wpProperty->location),
                'address' => $wpProperty->address,
                'notes' => $wpProperty->post_content,
            ]);
        }
    }
    
    private function mapOwner($wpOwnerId): int
    {
        return User::where('wp_user_id', $wpOwnerId)->first()?->id;
    }
}
```

### 5.2 تبسيط ACF Fields Migration
- **❌ إزالة**: معالجة معقدة لـ ACF fields
- **✅ بدلاً منها**: mapping مباشر للحقول الأساسية

## 6. الاختبارات المبسطة

### 6.1 اختبارات أساسية فقط
```php
class PropertyModelTest extends TestCase
{
    public function test_property_belongs_to_owner()
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        
        $this->assertEquals($owner->id, $property->owner->id);
    }
    
    public function test_property_has_many_units()
    {
        $property = Property::factory()->create();
        $units = Unit::factory(3)->create(['property_id' => $property->id]);
        
        $this->assertCount(3, $property->units);
    }
    
    public function test_occupancy_rate_calculation()
    {
        $property = Property::factory()->create();
        Unit::factory(4)->create(['property_id' => $property->id, 'tenant_id' => null]);
        Unit::factory(2)->create(['property_id' => $property->id, 'tenant_id' => 1]);
        
        $this->assertEquals(33.33, round($property->getOccupancyRate(), 2));
    }
}
```

### 6.2 إزالة الاختبارات المعقدة
- **❌ إزالة**: PropertyServiceTest
- **❌ إزالة**: PropertyRepositoryTest  
- **❌ إزالة**: PropertyObserverTest
- **✅ التركيز**: على Model relationships و Filament resources

## 7. Custom Components المبسطة (إذا لزم الأمر)

### 7.1 Location Selector Component (مبسط)
```php
// فقط إذا احتجنا لمكون مخصص للمواقع الهرمية
class LocationCascadeField extends Field
{
    protected string $view = 'filament.forms.components.location-cascade';
    
    // منطق بسيط للمواقع الهرمية
}
```

## الملخص النهائي

تم تبسيط نظام العقارات ليستخدم:
1. **Laravel's Eloquent** بدلاً من Repository pattern معقد
2. **Filament's built-in components** بدلاً من custom form builders
3. **Simple model methods** بدلاً من service classes معقدة
4. **Filament relation managers** لإدارة الوحدات والعقود
5. **Built-in Filament features** للعرض والتفاعل والفلترة

هذا النهج يحافظ على جميع الوظائف المطلوبة مع تبسيط كبير في التعقيد والكود المطلوب للصيانة.