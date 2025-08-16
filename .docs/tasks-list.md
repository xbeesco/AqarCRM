# قائمة مهام ترحيل نظام الحياة العقاري
**من WordPress إلى Laravel 12 + Filament 4**

## المبادئ الأساسية
- ✅ استخدام ميزات Filament 4 الجاهزة بدلاً من البناء من الصفر
- ✅ تطبيق برمجي سليم بدون مبالغات (No Over-Engineering)
- ✅ الاستفادة من Laravel 12 Built-in Features
- ✅ الحفاظ على Business Logic الحالي
- ✅ دعم اللغة العربية RTL

---

## المرحلة 1: إعداد البيئة والأساسيات

### 1.1 إعداد المشروع
- [ ] إنشاء مشروع Laravel 12 جديد
- [ ] تثبيت Filament 4
- [ ] إعداد قاعدة البيانات MySQL
- [ ] تكوين اللغة العربية والـ RTL
- [ ] إعداد Pusher للإشعارات المباشرة

### 1.2 إعداد User Management
- [ ] استخدام Laravel's built-in User model
- [ ] تثبيت Spatie Laravel Permission
- [ ] إنشاء الأدوار: Owner, Tenant, GeneralManager, Admin
- [ ] إعداد Filament Panel permissions

---

## المرحلة 2: النماذج الأساسية (Models)

### 2.1 Location Model (النظام الهرمي للمواقع)
```php
// استخدام Laravel's Nested Set أو Adjacency List
class Location extends Model {
    // parent_id للتدرج الهرمي
    public function parent() { return $this->belongsTo(Location::class, 'parent_id'); }
    public function children() { return $this->hasMany(Location::class, 'parent_id'); }
}
```

### 2.2 Property & Unit Models
```php
class Property extends Model {
    public function units() { return $this->hasMany(Unit::class); }
    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function location() { return $this->belongsTo(Location::class); }
}

class Unit extends Model {
    public function property() { return $this->belongsTo(Property::class); }
    public function tenant() { return $this->belongsTo(User::class, 'tenant_id'); }
    public function contracts() { return $this->hasMany(UnitContract::class); }
}
```

### 2.3 Contract Models
```php
class PropertyContract extends Model {
    public function property() { return $this->belongsTo(Property::class); }
    public function supplyPayments() { return $this->hasMany(SupplyPayment::class); }
}

class UnitContract extends Model {
    public function unit() { return $this->belongsTo(Unit::class); }
    public function collectionPayments() { return $this->hasMany(CollectionPayment::class); }
}
```

### 2.4 Payment Models
```php
class CollectionPayment extends Model {
    protected $casts = ['due_date' => 'date', 'payment_date' => 'date'];
    // استخدام Enum للحالات
    protected $casts = ['status' => PaymentStatus::class];
}

class SupplyPayment extends Model {
    protected $casts = ['due_date' => 'date', 'supply_date' => 'date'];
}
```

---

## المرحلة 3: Filament Resources

### 3.1 استخدام Filament Clusters للتنظيم
```php
// بدلاً من إنشاء تنظيم معقد، نستخدم Filament Clusters
class PropertyCluster extends Cluster {
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
}
```

### 3.2 Property Resource
- [ ] استخدام Filament's Table مع built-in filters
- [ ] استخدام Filament's Form components للـ ACF fields
- [ ] استخدام RelationManager للوحدات
- [ ] استخدام Filament's Actions للعمليات

### 3.3 User Resource  
- [ ] استخدام Filament's built-in User Resource
- [ ] إضافة role-based access control
- [ ] استخدام Filament's filters للأدوار

---

## المرحلة 4: Business Logic Services

### 4.1 Payment Calculation Service
```php
class PaymentCalculationService {
    public function generateCollectionSchedule(UnitContract $contract): void
    {
        // نقل منطق payment_interval() الحالي
        // استخدام Carbon للتواريخ
    }
    
    public function generateSupplySchedule(PropertyContract $contract): void
    {
        // نقل منطق prop_payment_interval() الحالي
    }
}
```

### 4.2 Financial Service
```php
class FinancialService {
    public function calculateCommission(float $amount, float $percentage): float
    {
        return $amount * ($percentage / 100);
    }
    
    public function processPayment(CollectionPayment $payment): void
    {
        // منطق معالجة الدفعات
    }
}
```

---

## المرحلة 5: Filament Forms (بدلاً من ACF)

### 5.1 استخدام Filament's Built-in Components
```php
// بدلاً من إنشاء components مخصصة، نستخدم Filament's components
TextInput::make('name')->required(),
Select::make('status')->options(PropertyStatus::class),
Textarea::make('description'),
FileUpload::make('contract_file'),

// للمواقع الهرمية - استخدام Dependent Selects
Select::make('region_id')
    ->options(Location::where('level', 1)->pluck('name', 'id'))
    ->reactive(),
Select::make('city_id')
    ->options(fn ($get) => Location::where('parent_id', $get('region_id'))->pluck('name', 'id'))
    ->reactive(),
```

### 5.2 Form Validation
- [ ] استخدام Laravel's built-in validation rules
- [ ] إضافة Arabic error messages
- [ ] استخدام Filament's validation features

---

## المرحلة 6: Data Tables (بدلاً من DataTables.js)

### 6.1 استخدام Filament Tables
```php
// بدلاً من AJAX endpoints معقدة، نستخدم Filament Tables
public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('owner.name'),
            BadgeColumn::make('status'),
        ])
        ->filters([
            SelectFilter::make('status'),
            Filter::make('created_at')->form([
                DatePicker::make('created_from'),
                DatePicker::make('created_until'),
            ])
        ])
        ->actions([
            EditAction::make(),
            DeleteAction::make(),
        ]);
}
```

---

## المرحلة 7: Dashboard & Reports

### 7.1 استخدام Filament Widgets
```php
// بدلاً من إنشاء dashboard معقد
class PropertyStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Properties', Property::count()),
            Stat::make('Occupied Units', Unit::where('status', 'occupied')->count()),
            Stat::make('Monthly Income', $this->calculateMonthlyIncome()),
        ];
    }
}
```

### 7.2 استخدام Filament Charts
```php
// بدلاً من ApexCharts معقدة
class IncomeChartWidget extends ChartWidget
{
    protected function getData(): array
    {
        // استخدام Chart.js built-in في Filament
        return [
            'datasets' => [
                [
                    'label' => 'Monthly Income',
                    'data' => $this->getMonthlyIncomeData(),
                ]
            ],
        ];
    }
}
```

---

## المرحلة 8: Migration Scripts

### 8.1 Data Migration من WordPress
```php
class MigrateWordPressDataCommand extends Command
{
    public function handle()
    {
        // نقل البيانات من WordPress بدون تعقيد
        $this->migrateUsers();
        $this->migrateProperties();
        $this->migrateContracts();
        $this->migratePayments();
    }
}
```

---

## المرحلة 9: Security Fixes

### 9.1 إصلاح المشاكل الأمنية
- [ ] استخدام Laravel's built-in CSRF protection
- [ ] استخدام Laravel's validation بدلاً من $_REQUEST
- [ ] استخدام Laravel's authorization policies
- [ ] إزالة hardcoded passwords واستخدام Laravel's authentication

### 9.2 Performance Improvements
- [ ] استخدام Laravel's Eloquent relationships بدلاً من N+1 queries
- [ ] استخدام Laravel's caching
- [ ] استخدام database indexes

---

## المرحلة 10: Testing & Deployment

### 10.1 Testing
- [ ] كتابة Feature tests للعمليات الأساسية
- [ ] اختبار User authentication وال permissions
- [ ] اختبار calculation logic

### 10.2 Deployment
- [ ] إعداد production environment
- [ ] تشغيل migrations
- [ ] تشغيل data migration
- [ ] اختبار final functionality

---

## ملاحظات مهمة:

1. **لا نعيد اختراع العجلة**: استخدام Filament's built-in features قدر الإمكان
2. **Keep it Simple**: التركيز على Business Logic وليس التعقيدات التقنية  
3. **استخدام Laravel Standards**: اتباع Laravel conventions
4. **الحفاظ على البيانات**: التأكد من نقل جميع البيانات الحالية بأمان
5. **Arabic Support**: التأكد من دعم اللغة العربية في جميع المراحل

**الهدف: نظام عملي وبسيط يحافظ على جميع الوظائف الحالية مع تحسين الأمان والأداء**
  TextInput::make('name_ar')
      ->label('تعديل / اضافة تصنيف الوحدة')
      ->required()
      ->maxLength(255)
      ->live(onBlur: true)
      ->afterStateUpdated(fn ($state, Set $set) => $set('slug', Str::slug($state)))
  ```
- **العلاقات**: belongsToMany(Unit::class) للربط مع الوحدات
- **الاختبارات**:
  - Unit: `UnitClassificationModelTest::test_classification_validation()`
  - Unit: `UnitClassificationModelTest::test_slug_auto_generation()`
  - Integration: `UnitClassificationSeederTest::test_default_classifications_created()`
  - Integration: `UnitClassificationTest::test_unit_classification_relationship()`

#### 1.1.6 جدول unit_statuses
- **الجدول**: `unit_statuses` (id, name_ar, name_en, slug, color, is_available)
- **النموذج**: `UnitStatus.php`
- **الاختبارات**:
  - Unit: `UnitStatusModelTest::test_status_availability_flag()`
  - Integration: `UnitStatusTest::test_status_affects_unit_availability()`

### 1.2 تحسين جدول المواقع
#### 1.2.1 إضافة حقول للمواقع
- **التعديلات**: إضافة (level, path, coordinates, postal_code) لجدول locations
- **الفهارس**: (parent_id, level, path)
- **الاختبارات**:
  - Unit: `LocationModelTest::test_location_level_validation()`
  - Unit: `LocationModelTest::test_location_path_generation()`
  - Integration: `LocationHierarchyTest::test_location_tree_building()`

#### 1.2.2 خدمة المواقع
- **الكلاس**: `LocationService.php`
- **الدوال**: `buildHierarchyTree()`, `findByPath()`, `getLocationOptions()`
- **الاختبارات**:
  - Unit: `LocationServiceTest::test_hierarchy_tree_structure()`
  - Integration: `LocationServiceTest::test_location_cascade_delete()`

#### 1.2.3 مراكز المدن (City Centers) - جديد من group_639ee4281793f.json
- **تحليل الملف**: تحليل ملف المراكز الذي يحتوي على 2 حقول رئيسية
- **الجدول**: `city_centers` أو إضافة حقل `center_name` لجدول locations
- **الحقول المطلوبة**:
  - `parent_city_id` (required, references locations table)
  - `center_name` (text, optional)
- **النموذج**: `CityCenter.php` أو تحديث `Location.php`
- **الملاحظة**: استخدام حقل مخصص `taxonmy_level_selector` للاختيار الهرمي للمواقع

#### 1.2.4 Filament مكون اختيار المواقع الهرمي
- **المكون المطلوب**: مكافئ لحقل `taxonmy_level_selector` المخصص
- **التطبيق**: 
  ```php
  Select::make('parent_city_id')
      ->label('المدينة التابعة')
      ->relationship('parentCity', 'name')
      ->searchable()
      ->preload()
      ->required()
      ->options(function () {
          return Location::where('level', 'city')
              ->pluck('name', 'id');
      })
  ```
- **خدمة مساعدة**: `HierarchicalLocationService.php`
- **الدوال**: `getCityOptions()`, `getDistrictsByCityId()`, `buildLocationTree()`
- **الاختبارات**:
  - Unit: `HierarchicalLocationServiceTest::test_city_options_generation()`
  - Integration: `FilamentLocationSelectTest::test_hierarchical_selection()`
  - Feature: `CityCenterResourceTest::test_location_hierarchy_in_form()`

### 1.3 البيانات الأولية
#### 1.3.1 Seeders للبيانات المرجعية
- **الملفات**: PropertyTypeSeeder, PropertyStatusSeeder, PropertyFeatureSeeder, UnitTypeSeeder, UnitStatusSeeder
- **الاختبارات**:
  - Integration: `SeederTest::test_all_seeders_run_successfully()`
  - Integration: `SeederTest::test_seeders_idempotent()`

## 2. إدارة المستخدمين
### 2.1 نظام الصلاحيات
#### 2.1.1 تثبيت وتكوين Spatie
- **الأمر**: `composer require spatie/laravel-permission`
- **Migration**: إنشاء جداول roles, permissions, model_has_roles, model_has_permissions
- **الاختبارات**:
  - Unit: `PermissionTest::test_permission_structure()`
  - Integration: `PermissionTest::test_spatie_tables_created()`

#### 2.1.2 إنشاء الأدوار
- **البيانات**: super_admin, admin, manager, owner, tenant, maintenance_staff
- **الكلاس**: `RoleSeeder.php`
- **الاختبارات**:
  - Unit: `RoleTest::test_all_roles_created()`
  - Integration: `RoleTest::test_role_hierarchy()`

#### 2.1.3 إنشاء الصلاحيات
- **المجموعات**: properties.*, units.*, contracts.*, payments.*, reports.*
- **الكلاس**: `PermissionSeeder.php`
- **الاختبارات**:
  - Unit: `PermissionTest::test_all_permissions_created()`
  - Integration: `PermissionTest::test_permission_assignment_to_roles()`

#### 2.1.4 خدمة الصلاحيات
- **الكلاس**: `RolePermissionService.php`
- **الدوال**: `syncRolePermissions()`, `getUserPermissionMatrix()`, `checkPermissionDependencies()`
- **الاختبارات**:
  - Unit: `RolePermissionServiceTest::test_permission_sync()`
  - Integration: `RolePermissionServiceTest::test_user_permission_check()`

### 2.2 الملاك
#### 2.2.1 حقول إضافية للملاك
- **الحقول**: national_id, commercial_register, tax_number, bank_account في جدول users
- **Migration**: `add_owner_fields_to_users_table.php`
- **الاختبارات**:
  - Unit: `OwnerModelTest::test_owner_field_validation()`
  - Unit: `OwnerModelTest::test_national_id_uniqueness()`

#### 2.2.2 نموذج المالك
- **Scope**: `owners()` في User model
- **العلاقات**: `hasMany(Property::class, 'owner_id')`
- **الاختبارات**:
  - Unit: `OwnerModelTest::test_owner_scope()`
  - Integration: `OwnerModelTest::test_owner_properties_relationship()`

#### 2.2.3 خدمة الملاك
- **الكلاس**: `OwnerService.php`
- **الدوال**: `createOwner()`, `getOwnerPortfolio()`, `calculateOwnerRevenue()`, `getOwnerStatement()`
- **الاختبارات**:
  - Unit: `OwnerServiceTest::test_owner_creation()`
  - Unit: `OwnerServiceTest::test_portfolio_calculation()`
  - Integration: `OwnerServiceTest::test_owner_statement_generation()`

#### 2.2.4 مستودع الملاك
- **الكلاس**: `OwnerRepository.php`
- **الدوال**: `findWithProperties()`, `getOwnersWithActiveContracts()`, `getOwnerPaymentHistory()`
- **الاختبارات**:
  - Unit: `OwnerRepositoryTest::test_find_with_properties()`
  - Integration: `OwnerRepositoryTest::test_payment_history_query()`

### 2.3 المستأجرون
#### 2.3.1 حقول إضافية للمستأجرين
- **الحقول**: national_id, occupation, employer, emergency_contact في جدول users
- **Migration**: `add_tenant_fields_to_users_table.php`
- **الاختبارات**:
  - Unit: `TenantModelTest::test_tenant_field_validation()`
  - Unit: `TenantModelTest::test_emergency_contact_format()`

#### 2.3.2 نموذج المستأجر
- **Scope**: `tenants()` في User model
- **العلاقات**: `hasMany(UnitContract::class, 'tenant_id')`
- **الدوال**: `getCurrentUnit()`, `getPaymentHistory()`
- **الاختبارات**:
  - Unit: `TenantModelTest::test_tenant_scope()`
  - Unit: `TenantModelTest::test_current_unit_detection()`
  - Integration: `TenantModelTest::test_payment_history()`

#### 2.3.3 خدمة المستأجرين
- **الكلاس**: `TenantService.php`
- **الدوال**: `createTenant()`, `assignToUnit()`, `getTenantLedger()`, `checkTenantEligibility()`
- **الاختبارات**:
  - Unit: `TenantServiceTest::test_tenant_creation()`
  - Unit: `TenantServiceTest::test_eligibility_check()`
  - Integration: `TenantServiceTest::test_unit_assignment()`

#### 2.3.4 أحداث المستأجرين
- **الأحداث**: TenantCreated, TenantAssigned, TenantRemoved
- **المستمعات**: NotifyAdminOnTenantCreation, UpdateUnitAvailability
- **الاختبارات**:
  - Unit: `TenantEventTest::test_events_fired()`
  - Integration: `TenantEventTest::test_event_listeners_executed()`

### 2.4 إدارة المستخدمين - تحليل النظام القديم
#### 2.4.1 نتائج تحليل class-alhiaa-system.php
- **مرجع الملف**: `D:\Server\crm\wp-content\themes\alhiaa-system\classes\class-alhiaa-system.php`
- **الخط 50-80**: آلية إدارة ملكية العقارات للمستخدمين
- **النمط المكتشف**: استخدام `user_property` meta field لربط العقارات بالملاك
- **Business Logic**:
  ```php
  // عند حفظ عقار جديد أو تحديث المالك
  $user_property = get_user_meta((int) $owner_id, 'user_property', true);
  $user_property[] = $post_id; // إضافة العقار لقائمة ملكية المستخدم
  update_user_meta((int) $owner_id, 'user_property', $user_property);
  
  // إزالة العقار من المالك السابق عند تغيير الملكية
  $key = array_search($post_id, $prev_owner_properties);
  if($key !== false) {
      unset($prev_owner_properties[$key]);
      update_user_meta((int) $old_owner_id, 'user_property', $prev_owner_properties);
  }
  ```

#### 2.4.2 إدارة الصلاحيات القائمة على الأدوار
- **مرجع الملف**: الخط 412-425 في `class-alhiaa-system.php`
- **النمط المكتشف**: إخفاء/تعطيل الحقول بناءً على دور المستخدم
- **Business Logic**:
  ```php
  // للمستخدمين من نوع alh_owner
  if($user && $user->roles[0] === 'alh_owner') {
      if($field['key'] == 'field_631da28bf2770') {
          $field['wrapper']['class'] = 'hidden'; // إخفاء الحقل
      }
      $field['readonly'] = true;  // جعل الحقل للقراءة فقط
      $field['disabled'] = true; // تعطيل الحقل
  }
  ```

#### 2.4.3 متطلبات المايجريشن في Laravel
- **PropertyOwnershipService.php**:
  ```php
  class PropertyOwnershipService {
      public function assignPropertyToOwner(Property $property, User $owner): void
      public function transferPropertyOwnership(Property $property, User $newOwner): void
      public function getOwnerProperties(User $owner): Collection
      public function removePropertyFromOwner(Property $property, User $owner): void
  }
  ```

- **RoleBasedFieldAccess.php**:
  ```php
  class RoleBasedFieldAccess {
      public function shouldHideField(string $fieldKey, User $user): bool
      public function shouldDisableField(string $fieldKey, User $user): bool
      public function getFieldAccessMatrix(User $user): array
  }
  ```

#### 2.4.4 الأدوار المكتشفة في النظام القديم
- **`alh_owner`**: مالك العقار - وصول محدود للحقول
- **Admin roles**: وصول كامل لجميع الحقول
- **المتطلبات**:
  - إنشاء أدوار مقابلة في Spatie Permission
  - تطبيق نفس منطق الصلاحيات في Filament Resources
  - إنشاء Middleware للتحكم في الوصول للحقول

#### 2.4.5 User Meta Migration Strategy
- **الهدف**: مايجريشن `user_property` meta إلى علاقة Laravel مناسبة
- **النهج**:
  ```php
  // في User Model
  public function ownedProperties(): HasMany 
  {
      return $this->hasMany(Property::class, 'owner_id');
  }
  
  // Migration Command
  class MigrateUserPropertyMeta extends Command {
      public function handle() {
          // قراءة user_meta من WordPress
          // تحويل إلى علاقة Laravel
          // تحديث Property records بـ owner_id
      }
  }
  ```

#### 2.4.6 Testing Strategy للنظام القديم
- **الاختبارات**:
  - Unit: `PropertyOwnershipServiceTest::test_property_assignment()`
  - Unit: `PropertyOwnershipServiceTest::test_ownership_transfer()`
  - Unit: `RoleBasedFieldAccessTest::test_field_visibility_by_role()`
  - Integration: `PropertyOwnershipTest::test_wordpress_migration()`
  - Integration: `UserRolePermissionTest::test_filament_field_access()`

#### 2.4.7 Filament Implementation Notes
- **Resource Policies**: تطبيق نفس منطق إخفاء الحقول في Filament Resources
- **Custom Form Components**: إنشاء مكونات مخصصة للحقول المشروطة
- **Role-based Navigation**: تطبيق نفس منطق القوائم المختلفة حسب الدور

## 3. إدارة الأصول
### 3.1 العقارات
#### 3.1.1 تحسين جدول العقارات
- **الحقول الجديدة**: coordinates, area_sqm, build_year, floors_count, has_elevator, parking_spots, garden_area
- **Migration**: `add_detailed_fields_to_properties_table.php`
- **الاختبارات**:
  - Unit: `PropertyModelTest::test_area_validation()`
  - Unit: `PropertyModelTest::test_coordinate_format()`

#### 3.1.2 علاقات العقار
- **العلاقات**: belongsToMany(PropertyFeature), morphMany(Media), hasMany(Unit)
- **الدوال**: `getOccupancyRate()`, `getMonthlyRevenue()`, `getTotalUnits()`
- **الاختبارات**:
  - Unit: `PropertyModelTest::test_occupancy_calculation()`
  - Integration: `PropertyModelTest::test_feature_attachment()`

#### 3.1.3 خدمة العقارات
- **الكلاس**: `PropertyService.php`
- **الدوال**: `createPropertyWithFeatures()`, `updatePropertyStatus()`, `calculatePropertyMetrics()`, `generatePropertyReport()`
- **الاختبارات**:
  - Unit: `PropertyServiceTest::test_property_creation_with_features()`
  - Unit: `PropertyServiceTest::test_metrics_calculation()`
  - Integration: `PropertyServiceTest::test_report_generation()`

#### 3.1.4 مستودع العقارات
- **الكلاس**: `PropertyRepository.php`
- **الدوال**: `findWithFullDetails()`, `searchProperties()`, `getNearbyProperties()`
- **الاختبارات**:
  - Unit: `PropertyRepositoryTest::test_search_filters()`
  - Integration: `PropertyRepositoryTest::test_nearby_search()`

### 3.2 الوحدات
#### 3.2.1 تحسين جدول الوحدات
- **الحقول الجديدة**: floor_number, unit_number, area_sqm, rooms_count, bathrooms_count, has_balcony, view_type, furnished
- **Migration**: `add_detailed_fields_to_units_table.php`
- **الاختبارات**:
  - Unit: `UnitModelTest::test_unit_number_uniqueness()`
  - Unit: `UnitModelTest::test_floor_validation()`

#### 3.2.2 علاقات الوحدة
- **العلاقات**: belongsTo(Property), belongsToMany(UnitFeature), hasOne(ActiveUnitContract)
- **الدوال**: `isAvailable()`, `getCurrentTenant()`, `getNextAvailableDate()`
- **الاختبارات**:
  - Unit: `UnitModelTest::test_availability_logic()`
  - Integration: `UnitModelTest::test_tenant_relationship()`

#### 3.2.3 خدمة الوحدات
- **الكلاس**: `UnitService.php`
- **الدوال**: `checkUnitAvailability()`, `assignTenant()`, `releaseUnit()`, `calculateUnitPricing()`
- **الاختبارات**:
  - Unit: `UnitServiceTest::test_availability_check()`
  - Unit: `UnitServiceTest::test_pricing_calculation()`
  - Integration: `UnitServiceTest::test_tenant_assignment_workflow()`

#### 3.2.4 مراقب الوحدات
- **الكلاس**: `UnitObserver.php`
- **الأحداث**: creating, updating, deleting
- **الاختبارات**:
  - Unit: `UnitObserverTest::test_observer_triggers()`
  - Integration: `UnitObserverTest::test_property_metrics_update()`

#### 3.2.5 Filament Resource للوحدات مع فلتر متقدم
- **الكلاس**: `UnitResource.php`
- **الجداول**: 
  - عرض جميع الوحدات مع تصفية متقدمة
  - إظهار حالة الوحدة (متاحة، مؤجرة، تحت الصيانة)
  - ربط الوحدة بالعقار والمستأجر الحالي
- **فلتر البحث المتقدم (من تحليل ACF group_63452111dd96c.json)**:
  - `TextInput` لاسم الوحدة (اسم الوحدة)
  - `Select` للعقار مع علاقة بجدول Properties
  - `TextInput` رقمي لعدد الغرف مع validation
  - `TextInput` رقمي لعدد الحمامات مع validation
  - نطاق سعري (حد أدنى وأقصى) باستخدام `TextInput` رقمي
- **مكونات Filament مطلوبة**:
  - `Tables\Filters\Filter` للفلتر المتقدم مع تخطيط مخصص
  - `Forms\Components\Grid` لتخطيط الفلتر (columnSpan: 2 للعقار، 1 للغرف/حمامات)
  - Validation rules للحقول الرقمية (min: 0 للغرف والحمامات، min: 1 للأسعار)
  - Dynamic relationship filtering للعقار → الوحدات مع preload()
  - Price range validation (max_price >= min_price)
  - Arabic UI labels matching ACF field labels exactly
- **الاختبارات**:
  - Feature: `UnitResourceTest::test_unit_listing()`
  - Feature: `UnitResourceTest::test_advanced_filtering()`
  - Feature: `UnitResourceTest::test_property_unit_relationship()`
  - Unit: `UnitAdvancedFilterTest::test_price_range_filter()`
  - Unit: `UnitAdvancedFilterTest::test_rooms_bathrooms_filter()`
  - Unit: `UnitAdvancedFilterTest::test_arabic_labels_display()`
  - Integration: `UnitFilterIntegrationTest::test_filter_form_layout_matches_acf()`

## 4. إدارة العقود
### 4.1 عقود الملاك
#### 4.1.1 جدول عقود الملاك المحسن
- **الحقول الجديدة**: contract_number, notary_number, commission_rate, payment_day, auto_renew, notice_period
- **الحالات**: draft, active, suspended, expired, terminated
- **Migration**: `add_fields_to_property_contracts_table.php`
- **الاختبارات**:
  - Unit: `PropertyContractModelTest::test_contract_number_generation()`
  - Unit: `PropertyContractModelTest::test_state_transitions()`

#### 4.1.2 خدمة عقود الملاك
- **الكلاس**: `PropertyContractService.php`
- **الدوال**: `createContract()`, `activateContract()`, `renewContract()`, `terminateContract()`, `generatePaymentSchedule()`
- **الاختبارات**:
  - Unit: `PropertyContractServiceTest::test_contract_creation()`
  - Unit: `PropertyContractServiceTest::test_payment_schedule_generation()`
  - Integration: `PropertyContractServiceTest::test_contract_activation_workflow()`

#### 4.1.3 مهام عقود الملاك
- **المهام**: CheckContractExpiry, GenerateMonthlyInvoices, SendRenewalNotifications
- **الجدولة**: Daily, Monthly
- **الاختبارات**:
  - Unit: `ContractJobTest::test_expiry_check()`
  - Integration: `ContractJobTest::test_invoice_generation()`

### 4.2 عقود المستأجرين
#### 4.2.1 جدول عقود المستأجرين المحسن
- **الحقول الأساسية**: name, contract_status, unit_id, property_id, tenant_id, start_date, duration_months, rent_amount, payment_schedule, contract_document, notes
- **الحقول الجديدة**: security_deposit, utilities_included, payment_method, grace_period, late_fee_rate, evacuation_notice
- **Enum للدفعات**: PaymentScheduleEnum (month, three_month, six_month, year)
- **العلاقات**: hasMany(ContractAddendum), morphMany(Document), belongsTo(Unit), belongsTo(Property), belongsTo(User as tenant)
- **Migration**: `add_fields_to_unit_contracts_table.php`
- **الاختبارات**:
  - Unit: `UnitContractModelTest::test_deposit_validation()`
  - Unit: `UnitContractModelTest::test_late_fee_calculation()`
  - Unit: `UnitContractModelTest::test_payment_schedule_enum()`

#### 4.2.2 خدمة عقود المستأجرين
- **الكلاس**: `UnitContractService.php`
- **الدوال**: `createLeaseContract()`, `addAddendum()`, `processEarlyTermination()`, `calculateRefund()`
- **الاختبارات**:
  - Unit: `UnitContractServiceTest::test_lease_creation()`
  - Unit: `UnitContractServiceTest::test_early_termination_penalty()`
  - Integration: `UnitContractServiceTest::test_addendum_workflow()`

#### 4.2.3 Filament Resource للعقود (من تحليل ACF)
- **التنفيذ**: UnitContractResource.php
- **الحقول المطلوبة**:
  - TextInput للاسم (اسم العقد)
  - Toggle مع تسميات عربية للحالة (نشط/غير نشط)
  - Select للوحدة مع تصفية بالعقار
  - Select للعقار مع تحديث ديناميكي للوحدات
  - Select للمستأجر مع تصفية بالدور (alh_tenant)
  - DatePicker لتاريخ البداية (عرض d/m/Y، حفظ Y-m-d)
  - TextInput رقمي لمدة العقد (افتراضي 12 شهر)
  - TextInput رقمي لقيمة الإيجار (حد أدنى 1)
  - Select لجدولة الدفع (شهري، ربع سنوي، نصف سنوي، سنوي)
  - FileUpload للمستند (PDF فقط)
  - Textarea للملاحظات
- **الاختبارات**:
  - Unit: `UnitContractResourceTest::test_form_validation()`
  - Unit: `UnitContractResourceTest::test_dynamic_unit_filtering()`
  - Feature: `UnitContractResourceTest::test_create_contract_workflow()`

#### 4.2.4 إشعارات العقود
- **الإشعارات**: ContractSignedNotification, PaymentDueNotification, ContractExpiryNotification
- **القنوات**: mail, database, sms
- **الاختبارات**:
  - Unit: `ContractNotificationTest::test_notification_content()`
  - Integration: `ContractNotificationTest::test_notification_sending()`

## 5. النظام المالي
### 5.1 دفعات التحصيل
#### 5.1.1 جدول دفعات التحصيل المحسن
- **الحقول الجديدة**: invoice_number, payment_reference, payment_channel, discount_amount, tax_amount, notes
- **الحالات**: pending, due, paid, partial, overdue, cancelled
- **Migration**: `add_fields_to_collection_payments_table.php`
- **الاختبارات**:
  - Unit: `CollectionPaymentModelTest::test_invoice_number_generation()`
  - Unit: `CollectionPaymentModelTest::test_state_machine()`

#### 5.1.2 خدمة التحصيل
- **الكلاس**: `CollectionService.php`
- **الدوال**: `processPayment()`, `bulkCollect()`, `generateReceipt()`, `reconcilePayments()`
- **الاختبارات**:
  - Unit: `CollectionServiceTest::test_payment_processing()`
  - Unit: `CollectionServiceTest::test_receipt_generation()`
  - Integration: `CollectionServiceTest::test_bulk_collection()`

#### 5.1.3 مهام التحصيل
- **المهام**: SendPaymentReminders, UpdateOverdueStatuses, GenerateMonthlyStatements
- **الجدولة**: Daily, Weekly, Monthly
- **الاختبارات**:
  - Unit: `CollectionJobTest::test_reminder_sending()`
  - Integration: `CollectionJobTest::test_overdue_update()`

#### 5.1.4 تحليل ملف group_631d9e384ad89.json - دفعات تحصيل
##### 5.1.4.1 تحليل الحقول الموجودة من النظام القديم
تم تحليل ملف دفعات التحصيل الذي يحتوي على 13 حقل (8 مرئية + 3 مخفية + 2 رسائل):

**الحقول الأساسية:**
- **اسم العقد** (post_title): اسم دفعة التحصيل (required, text)
- **العقد** (contract): العقد المرتبط (required, post_object: unit_contract, UI enabled)
- **القيمة المالية** (contract_sell): مبلغ الدفعة (required, text)
- **حالة التحصيل** (contract_status): حالة الدفعة (required, button_group, horizontal)
  - `collected` (تم التحصيل)
  - `worth_collecting` (تستحق التحصيل)
  - `delayed` (المؤجلة)
  - `overdue` (تجاوزة المدة)

**حقول التواريخ مع المنطق الشرطي:**
- **بداية التاريخ** (collection_date_start): تاريخ البداية (date_picker: d/m/Y, conditional)
- **الي تاريخ** (collection_date_end): تاريخ النهاية (date_picker: d/m/Y, conditional)
- **تاريخ التحصيل** (payment_date): تاريخ السداد الفعلي (date_picker: Y-m-d, conditional: collected only)

**حقول حالة التأجيل:**
- **سبب التاجيل** (delay_reason): سبب التأجيل (textarea, conditional: delayed only)
- **مدة التاجيل بالايام** (delay_duration): مدة التأجيل (text, conditional: delayed only)

**حقول الحالات الخاصة:**
- **ملاحظات تجاوز المدة** (notes_of_exceeding_payment_period): ملاحظات للمتأخرات (textarea, conditional: overdue only)

**الحقول المخفية (نظام):**
- **notification_date**: تاريخ الإشعار (acfe_hidden)
- **unit_id**: معرف الوحدة (acfe_hidden)
- **owner**: المالك (acfe_hidden)

##### 5.1.4.2 المنطق الشرطي المعقد
الملف يحتوي على منطق شرطي متقدم:
- حقول التواريخ تظهر للحالات: collected, worth_collecting, أو empty
- حقول التأجيل تظهر فقط عند حالة delayed
- حقول المتأخرات تظهر فقط عند حالة overdue
- تاريخ السداد يظهر فقط عند حالة collected

##### 5.1.4.3 Filament Resource Schema المطلوب
```php
// CollectionPaymentResource.php
Section::make('بيانات الدفعة الأساسية')
    ->schema([
        TextInput::make('title')
            ->label('اسم دفعة التحصيل')
            ->required()
            ->columnSpan(['md' => 6]),
            
        Select::make('contract_id')
            ->label('العقد')
            ->relationship('unitContract', 'name')
            ->searchable()
            ->preload()
            ->required()
            ->columnSpan(['md' => 6]),
            
        TextInput::make('amount')
            ->label('القيمة المالية')
            ->required()
            ->numeric()
            ->minValue(1)
            ->prefix('ر.س')
            ->columnSpan(['md' => 6]),
            
        Radio::make('status')
            ->label('حالة التحصيل')
            ->required()
            ->options([
                'collected' => 'تم التحصيل',
                'worth_collecting' => 'تستحق التحصيل',
                'delayed' => 'المؤجلة',
                'overdue' => 'تجاوزة المدة',
            ])
            ->inline()
            ->columnSpan(['md' => 12]),
    ]),

Section::make('تواريخ الدفعة')
    ->schema([
        DatePicker::make('start_date')
            ->label('بداية التاريخ')
            ->required()
            ->displayFormat('d/m/Y')
            ->visible(fn (Get $get) => in_array($get('status'), ['collected', 'worth_collecting', null]))
            ->columnSpan(['md' => 6]),
            
        DatePicker::make('end_date')
            ->label('الي تاريخ')
            ->required()
            ->displayFormat('d/m/Y')
            ->visible(fn (Get $get) => in_array($get('status'), ['collected', 'worth_collecting', null]))
            ->columnSpan(['md' => 6]),
            
        DatePicker::make('payment_date')
            ->label('تاريخ التحصيل')
            ->displayFormat('Y-m-d')
            ->visible(fn (Get $get) => $get('status') === 'collected')
            ->columnSpan(['md' => 12]),
    ]),

Section::make('تفاصيل التأجيل')
    ->schema([
        Textarea::make('delay_reason')
            ->label('سبب التاجيل')
            ->required()
            ->visible(fn (Get $get) => $get('status') === 'delayed')
            ->columnSpan(['md' => 6]),
            
        TextInput::make('delay_duration')
            ->label('مدة التاجيل بالايام')
            ->required()
            ->numeric()
            ->minValue(1)
            ->visible(fn (Get $get) => $get('status') === 'delayed')
            ->columnSpan(['md' => 6]),
    ]),

Section::make('ملاحظات المتأخرات')
    ->schema([
        Textarea::make('overdue_notes')
            ->label('ملاحظات في حالة تجاوز مدة الدفع')
            ->visible(fn (Get $get) => $get('status') === 'overdue')
            ->columnSpan(['md' => 12]),
    ]),
```

##### 5.1.4.4 الاختبارات المطلوبة للنمط الشرطي
- **Unit**: `CollectionPaymentResourceTest::test_conditional_field_visibility()`
- **Unit**: `CollectionPaymentResourceTest::test_status_based_validation()`
- **Unit**: `CollectionPaymentResourceTest::test_date_format_handling()`
- **Feature**: `CollectionPaymentResourceTest::test_status_workflow()`
- **Integration**: `CollectionPaymentResourceTest::test_contract_relationship()`

##### 5.1.4.5 أنماط Filament الجديدة المكتشفة
1. **Radio Buttons**: استخدام Radio بدلاً من Select للحالات المتعددة
2. **Complex Conditional Logic**: منطق شرطي متعدد المستويات باستخدام `visible()`
3. **Multiple Date Formats**: تنسيقات مختلفة للتواريخ حسب الغرض
4. **Section-based Organization**: تنظيم الحقول في أقسام منطقية
5. **Dynamic Validation**: التحقق المشروط حسب الحالة

### 5.2 دفعات التوريد
#### 5.2.1 تحليل ملف group_631da28be0c0a.json - دفعات توريد
##### 5.2.1.1 تحليل الحقول الموجودة من النظام القديم
تم تحليل ملف دفعات التوريد الذي يحتوي على 8 حقول رئيسية + 2 حقل مخفي:

**الحقول الأساسية:**
- **الاسم** (post_title): اسم دفعة التوريد (required, text)
- **العقد** (contract): العقد المرتبط (post_object: property_contract, UI enabled, read-only)
- **القيمة المالية** (contract_sell): مبلغ الدفعة (required, text)
- **حالة التوريد** (supply_status): حالة الدفعة (required, button_group, horizontal)
  - `pending` (قيد الانتظار)
  - `worth_collecting` (تستحق التوريد)
  - `collected` (تم التوريد)

**حقول التواريخ مع المنطق الشرطي:**
- **تاريخ التوريد** (supply_date): تاريخ التوريد الفعلي (date_picker: Y-m-d, conditional: collected only)
- **تاريخ الاستحقاق** (supply_date_worthy): تاريخ الاستحقاق (date_picker: Y-m-d, conditional: pending/worth_collecting, read-only)

**حقول الإقرار:**
- **رسالة الإقرار** (message): رسالة تظهر عند التوريد (message field, conditional: collected only)
- **اقرار** (acknowledgment_commitment): موافقة المالك (true_false with Arabic labels: موافق/غير موافق, conditional: collected only)

**الحقول المخفية (نظام):**
- **property**: معرف العقار (acfe_hidden)
- **owner**: معرف المالك (acfe_hidden)

##### 5.2.1.2 المنطق الشرطي المكتشف
الملف يحتوي على منطق شرطي معقد:
- حقول التوريد تظهر فقط عند حالة `collected`
- حقل تاريخ الاستحقاق يظهر للحالات: `pending` و `worth_collecting`
- رسالة وإقرار الموافقة يظهران فقط عند `collected`
- بعض الحقول محددة كـ read-only لمنع التعديل

##### 5.2.1.3 Filament Resource Schema المطلوب
```php
// SupplyPaymentResource.php
Section::make('بيانات الدفعة الأساسية')
    ->schema([
        TextInput::make('title')
            ->label('اسم دفعة التوريد')
            ->required()
            ->columnSpan(['md' => 6]),
            
        Select::make('property_contract_id')
            ->label('العقد')
            ->relationship('propertyContract', 'name')
            ->searchable()
            ->preload()
            ->required()
            ->disabled(fn (?Model $record) => $record !== null) // Read-only after creation
            ->columnSpan(['md' => 6]),
            
        TextInput::make('amount')
            ->label('القيمة المالية')
            ->required()
            ->numeric()
            ->minValue(1)
            ->prefix('ر.س')
            ->columnSpan(['md' => 6]),
            
        Radio::make('status')
            ->label('حالة التوريد')
            ->required()
            ->options([
                'pending' => 'قيد الانتظار',
                'worth_collecting' => 'تستحق التوريد',
                'collected' => 'تم التوريد',
            ])
            ->inline()
            ->columnSpan(['md' => 6]),
    ]),

Section::make('تواريخ الدفعة')
    ->schema([
        DatePicker::make('due_date')
            ->label('تاريخ الاستحقاق')
            ->required()
            ->displayFormat('Y-m-d')
            ->disabled() // Read-only as per old system
            ->visible(fn (Get $get) => in_array($get('status'), ['pending', 'worth_collecting']))
            ->columnSpan(['md' => 6]),
            
        DatePicker::make('supply_date')
            ->label('تاريخ التوريد')
            ->displayFormat('Y-m-d')
            ->visible(fn (Get $get) => $get('status') === 'collected')
            ->columnSpan(['md' => 6]),
    ]),

Section::make('إقرار ما بعد التوريد')
    ->schema([
        Placeholder::make('acknowledgment_message')
            ->label('اقرار ما بعد التوريد')
            ->content('تم تسليم المبلغ المستحق للمالك حسب الاتفاقية')
            ->visible(fn (Get $get) => $get('status') === 'collected'),
            
        Toggle::make('acknowledgment_commitment')
            ->label('اقرار')
            ->onLabel('موافق')
            ->offLabel('غير موافق')
            ->visible(fn (Get $get) => $get('status') === 'collected')
            ->columnSpan(['md' => 12]),
    ]),
```

##### 5.2.1.4 الاختبارات المطلوبة للنمط الشرطي
- **Unit**: `SupplyPaymentResourceTest::test_conditional_field_visibility()`
- **Unit**: `SupplyPaymentResourceTest::test_status_based_validation()`
- **Unit**: `SupplyPaymentResourceTest::test_readonly_fields_after_creation()`
- **Feature**: `SupplyPaymentResourceTest::test_supply_workflow()`
- **Integration**: `SupplyPaymentResourceTest::test_property_contract_relationship()`

##### 5.2.1.5 أنماط Filament الجديدة المكتشفة
1. **Toggle with Custom Arabic Labels**: استخدام Toggle مع تسميات عربية مخصصة
2. **Conditional Read-only Fields**: حقول للقراءة فقط بناءً على الحالة
3. **Placeholder Messages**: استخدام Placeholder لعرض رسائل إعلامية
4. **Disabled Field Logic**: منطق تعطيل الحقول بعد الإنشاء
5. **Complex Radio Button Groups**: مجموعات Radio معقدة مع حالات متعددة

#### 5.2.2 جدول دفعات التوريد المحسن
- **الحقول الجديدة**: bank_transfer_reference, deduction_details, approval_status, approved_by, approved_at
- **الدوال**: `calculateNetAmount()`, `getDeductionBreakdown()`, `requiresApproval()`
- **Migration**: `add_fields_to_supply_payments_table.php`
- **الاختبارات**:
  - Unit: `SupplyPaymentModelTest::test_net_calculation()`
  - Unit: `SupplyPaymentModelTest::test_approval_workflow()`

#### 5.2.3 خدمة التوريد
- **الكلاس**: `SupplyService.php`
- **الدوال**: `calculateOwnerPayment()`, `applyDeductions()`, `processApproval()`, `executeBankTransfer()`
- **الاختبارات**:
  - Unit: `SupplyServiceTest::test_payment_calculation()`
  - Unit: `SupplyServiceTest::test_deduction_application()`
  - Integration: `SupplyServiceTest::test_bank_transfer()`

### 5.3 الصيانة
#### 5.3.1 جدول الصيانة المحسن
- **الحقول الجديدة**: priority, assigned_to, scheduled_date, completion_date, vendor_id, warranty_claim, recurring_schedule
- **الحالات**: reported, scheduled, in_progress, completed, cancelled
- **Migration**: `add_fields_to_property_repairs_table.php`
- **الاختبارات**:
  - Unit: `PropertyRepairModelTest::test_priority_levels()`
  - Unit: `PropertyRepairModelTest::test_warranty_check()`

#### 5.3.2 خدمة الصيانة
- **الكلاس**: `MaintenanceService.php`
- **الدوال**: `createMaintenanceRequest()`, `assignToVendor()`, `trackProgress()`, `processWarrantyClaim()`
- **الاختبارات**:
  - Unit: `MaintenanceServiceTest::test_request_creation()`
  - Unit: `MaintenanceServiceTest::test_vendor_assignment()`
  - Integration: `MaintenanceServiceTest::test_warranty_claim()`

#### 5.3.3 جدولة الصيانة
- **الكلاس**: `PreventiveMaintenanceScheduler.php`
- **المهام**: SchedulePreventiveMaintenance, MaintenanceReminder
- **الاختبارات**:
  - Unit: `MaintenanceSchedulerTest::test_schedule_generation()`
  - Integration: `MaintenanceSchedulerTest::test_reminder_sending()`

## 6. إدارة عقود الملاك (Property Owner Contracts)
### 6.1 تحليل ملف group_630c9940e912f.json - عقد ملاك
#### 6.1.1 تحليل الحقول الموجودة
تم تحليل ملف العقد الخاص بالملاك والذي يحتوي على 9 حقول رئيسية:

- **اسم العقد** (post_title): اسم العقد (required, text, width: 60%)
- **العقار** (property): العقار المرتبط (required, post_object: alh_property, width: 40%)
- **تاريخ انشاء العقد** (date_creation_contract): تاريخ إنشاء العقد (required, date_picker: Y-m-d, width: 50%)
- **مدة التعاقد بالشهر** (contract_duration_per_month): مدة العقد بالأشهر (required, number, default: 12, width: 50%)
- **المالك** (owner): المالك المرتبط بالعقد (required, user field, width: 50%)
- **النسبة المتفق عليها** (agreed_to_rate): نسبة الإدارة % (required, number, min: 1, max: 100, width: 50%)
- **سداد الدفعات** (payment_payments): جدول سداد الدفعات (required, select: شهريا/ربع سنوي/نصف سنوي/سنوي, width: 50%)
- **ملف صوره العقد** (contract_image_file): ملف العقد PDF (required, file: PDF only, width: 50%)
- **ملاحظات اخري** (other_note): ملاحظات إضافية (optional, textarea, full width)

#### 6.1.2 جدول عقود الملاك المطلوب
- **الجدول**: `property_owner_contracts`
- **الحقول الأساسية**: 
  - id, title, property_id, owner_id, start_date, duration_months, management_percentage, payment_schedule, contract_file_path, notes, status, created_at, updated_at
- **Migration**: `create_property_owner_contracts_table.php`
- **النموذج**: `PropertyOwnerContract.php`
- **العلاقات**: 
  - belongsTo(Property::class)
  - belongsTo(User::class, 'owner_id')
- **الاختبارات**:
  - Unit: `PropertyOwnerContractModelTest::test_contract_validation()`
  - Unit: `PropertyOwnerContractModelTest::test_percentage_range_validation()`
  - Unit: `PropertyOwnerContractModelTest::test_payment_schedule_options()`
  - Integration: `PropertyOwnerContractModelTest::test_property_owner_relationships()`

#### 6.1.3 تصميم Filament Form Schema لعقود الملاك
```php
Section::make('بيانات العقد الأساسية')
    ->schema([
        Grid::make(2)->schema([
            TextInput::make('title')
                ->label('اسم العقد')
                ->required()
                ->columnSpan(1),
            Select::make('property_id')
                ->label('العقار')
                ->relationship('property', 'name')
                ->searchable()
                ->required()
                ->columnSpan(1),
        ]),
        Grid::make(2)->schema([
            DatePicker::make('start_date')
                ->label('تاريخ انشاء العقد')
                ->required()
                ->displayFormat('Y-m-d')
                ->columnSpan(1),
            TextInput::make('duration_months')
                ->label('مدة التعاقد بالشهر')
                ->numeric()
                ->default(12)
                ->required()
                ->columnSpan(1),
        ]),
        Grid::make(2)->schema([
            Select::make('owner_id')
                ->label('المالك')
                ->relationship('owner', 'name')
                ->searchable()
                ->required()
                ->columnSpan(1),
            TextInput::make('management_percentage')
                ->label('النسبة المتفق عليها %')
                ->numeric()
                ->minValue(1)
                ->maxValue(100)
                ->required()
                ->columnSpan(1),
        ]),
        Grid::make(2)->schema([
            Select::make('payment_schedule')
                ->label('سداد الدفعات')
                ->options([
                    'month' => 'شهريا',
                    'three_month' => 'ربع سنوي',
                    'six_month' => 'نصف سنوي',
                    'year' => 'سنوي'
                ])
                ->required()
                ->columnSpan(1),
            FileUpload::make('contract_file_path')
                ->label('ملف صوره العقد')
                ->acceptedFileTypes(['application/pdf'])
                ->required()
                ->columnSpan(1),
        ]),
        Textarea::make('notes')
            ->label('ملاحظات اخري')
            ->rows(3)
            ->columnSpanFull(),
    ])
```

#### 6.1.4 خدمة عقود الملاك
- **الكلاس**: `PropertyOwnerContractService.php`
- **الدوال**: 
  - `createOwnerContract()`: إنشاء عقد جديد مع المالك
  - `calculateEndDate()`: حساب تاريخ انتهاء العقد
  - `validateContractTerms()`: التحقق من شروط العقد
  - `generateContractStatement()`: إنشاء كشف حساب العقد
  - `renewContract()`: تجديد العقد
  - `terminateContract()`: إنهاء العقد مبكراً
- **الاختبارات**:
  - Unit: `PropertyOwnerContractServiceTest::test_contract_creation()`
  - Unit: `PropertyOwnerContractServiceTest::test_end_date_calculation()`
  - Unit: `PropertyOwnerContractServiceTest::test_renewal_process()`
  - Integration: `PropertyOwnerContractServiceTest::test_contract_termination()`

#### 6.1.5 Filament Resource لعقود الملاك
- **الكلاس**: `PropertyOwnerContractResource.php`
- **الجداول**: 
  - عرض جميع العقود مع تصفية حسب المالك والعقار
  - إظهار حالة العقد (نشط، منتهي، ملغي)
  - تصدير العقود بصيغة PDF وExcel
- **Actions**:
  - View Contract Details
  - Renew Contract
  - Terminate Contract
  - Download Contract File
- **الاختبارات**:
  - Feature: `PropertyOwnerContractResourceTest::test_contract_listing()`
  - Feature: `PropertyOwnerContractResourceTest::test_contract_filtering()`
  - Feature: `PropertyOwnerContractResourceTest::test_contract_actions()`

#### 6.1.6 التحديات الخاصة بالملف
- **File Upload Security**: رفع ملفات PDF فقط مع التحقق من الأمان
- **Date Validation**: التأكد من صحة تواريخ البداية والنهاية
- **Percentage Validation**: التحقق من النسبة (1-100%)
- **Business Logic**: ربط العقد بالمالك والعقار بشكل صحيح
- **Payment Schedule Logic**: حساب مواعيد الدفع حسب الجدول المحدد
- **Contract Status**: إدارة حالات العقد (نشط، منتهي، مجدد)
- **المكونات المطلوبة**:
  - `OwnerContractFormSchema.php`: Schema العقد
  - `ContractFileUpload.php`: رفع ملفات العقود بأمان
  - `PaymentScheduleCalculator.php`: حساب مواعيد الدفع
  - `ContractStatusManager.php`: إدارة حالات العقد

### 6.2 النماذج والعلاقات المطلوبة
#### 6.2.1 نموذج PropertyOwnerContract
```php
class PropertyOwnerContract extends Model
{
    protected $fillable = [
        'title', 'property_id', 'owner_id', 'start_date', 
        'duration_months', 'management_percentage', 'payment_schedule',
        'contract_file_path', 'notes', 'status'
    ];

    protected $casts = [
        'start_date' => 'date',
        'management_percentage' => 'decimal:2'
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function getEndDateAttribute(): Carbon
    {
        return $this->start_date->addMonths($this->duration_months);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date->isFuture();
    }
}
```

#### 6.2.2 التكامل مع النماذج الموجودة
- **Property Model**: `hasMany(PropertyOwnerContract::class)`
- **User Model**: `hasMany(PropertyOwnerContract::class, 'owner_id')`
- **إضافة Scopes للبحث والتصفية**
- **الاختبارات**:
  - Unit: `PropertyOwnerContractModelTest::test_model_relationships()`
  - Unit: `PropertyOwnerContractModelTest::test_contract_status_logic()`
  - Integration: `PropertyOwnerContractModelTest::test_property_contract_cascade()`

## 7. الاستيراد والتكامل
### 7.1 محرك الاستيراد
#### 7.1.1 أمر الاستيراد
- **الأمر**: `php artisan import:wordpress --type=owners --batch-size=100 --dry-run`
- **الكلاس**: `ImportFromWordPressCommand.php`
- **الدوال**: `validateConnection()`, `mapDataStructure()`, `processInBatches()`, `handleErrors()`
- **الاختبارات**:
  - Unit: `ImportCommandTest::test_connection_validation()`
  - Unit: `ImportCommandTest::test_batch_processing()`
  - Integration: `ImportCommandTest::test_dry_run()`

#### 7.1.2 خدمة الاستيراد
- **الكلاس**: `DataImportService.php`
- **الدوال**: `connectToWordPress()`, `extractData()`, `transformData()`, `loadData()`, `validateImport()`
- **الاختبارات**:
  - Unit: `DataImportServiceTest::test_data_extraction()`
  - Unit: `DataImportServiceTest::test_data_transformation()`
  - Integration: `DataImportServiceTest::test_full_import_cycle()`

#### 7.1.3 محولات البيانات
- **الكلاسات**: OwnerMapper, TenantMapper, PropertyMapper, UnitMapper, ContractMapper, PaymentMapper
- **الاختبارات**:
  - Unit: `MapperTest::test_owner_mapping()`
  - Unit: `MapperTest::test_property_mapping()`
  - Integration: `MapperTest::test_relationship_mapping()`

#### 7.1.4 سجل الاستيراد
- **الجدول**: `import_logs` (type, status, records_total, records_imported, errors, started_at, completed_at)
- **النموذج**: `ImportLog.php`
- **الاختبارات**:
  - Unit: `ImportLogTest::test_log_creation()`
  - Integration: `ImportLogTest::test_error_tracking()`

### 7.2 التحقق من البيانات
#### 7.2.1 خدمة التحقق
- **الكلاس**: `DataValidationService.php`
- **الدوال**: `validateUserIntegrity()`, `validatePropertyRelations()`, `validateFinancialRecords()`, `generateValidationReport()`
- **الاختبارات**:
  - Unit: `DataValidationServiceTest::test_user_validation()`
  - Unit: `DataValidationServiceTest::test_financial_validation()`
  - Integration: `DataValidationServiceTest::test_full_validation()`

#### 7.2.2 المدققات
- **الكلاسات**: UserValidator, PropertyValidator, ContractValidator, PaymentValidator
- **الاختبارات**:
  - Unit: `ValidatorTest::test_duplicate_detection()`
  - Unit: `ValidatorTest::test_missing_data_detection()`
  - Integration: `ValidatorTest::test_relationship_validation()`

#### 6.2.3 التقارير
- **التقارير**: DataIntegrityReport, MissingDataReport, DuplicateRecordsReport
- **الاختبارات**:
  - Unit: `ReportTest::test_report_generation()`
  - Integration: `ReportTest::test_report_accuracy()`

## 7. تحليل ACF Field Groups وتحويلها إلى Filament 4 Forms
### 7.1 تحليل ملف group_6306387af3214.json - اضافة عقار
#### 7.1.1 تحليل الحقول الموجودة
- **post_title**: اسم العقار (required, text, width: 60%)
- **owner**: المالك (required, user field, role: alh_owner, width: 40%)
- **propery_status**: حالة العقار (required, taxonomy: prop_status, width: 33%)
- **property_type**: نوع العقار (required, taxonomy: prop_type, width: 33%)
- **property_lable**: تصنيف العقار (required, text, width: 33%)
- **number_of_parking**: عدد المواقف (optional, number, width: 33%)
- **number_of_lifts**: عدد المصاعد (optional, number, width: 33%)
- **elevator_maintenance_contract**: عقد صيانة المصاعد (optional, file: PDF, width: 33%)
- **property_address**: رقم المبنى اسم الشارع (required, text, width: 60%)
- **postal_code**: الرمز البريدي (optional, text, width: 20%)
- **property_state**: المنطقة (required, custom field: taxonmy_level_selector, page_type: 3)
- **property_city**: المدينة (required, custom field: taxonmy_level_selector, page_type: 4)
- **city_center**: المركز (required, custom field: taxonmy_level_selector, page_type: 5)
- **property_area**: الحي (required, custom field: taxonmy_level_selector, page_type: 8)
- **special_note**: ملاحظة خاصة (optional, textarea)

#### 7.1.2 تصميم Filament Form Schema للعقارات
- **Schema Structure**: استخدام `Filament\Schemas\Schema` بدلاً من `Form`
- **Layout Components**: `Section`, `Grid`, `Tabs` من `Filament\Schemas\Components`
- **Form Fields**: `TextInput`, `Select`, `Textarea`, `FileUpload` من `Filament\Forms\Components`
- **Validation Rules**: Laravel validation rules مع required fields
- **Location Cascade**: Custom component للمواقع الهرمية
- **الاختبارات**:
  - Unit: `PropertyFormSchemaTest::test_schema_structure()`
  - Unit: `PropertyFormSchemaTest::test_validation_rules()`
  - Integration: `PropertyFormTest::test_location_cascade_functionality()`

#### 7.1.3 التحديات الخاصة بالملف
- **Custom Field Type**: `taxonmy_level_selector` يحتاج custom Filament component
- **Dynamic Loading**: Location fields تحتاج AJAX loading based on parent selection
- **File Validation**: PDF file upload for elevator maintenance contract
- **Width Layout**: ACF wrapper widths need conversion to Filament grid system
- **Arabic Labels**: All labels in Arabic need proper RTL support
- **المكونات المطلوبة**:
  - `LocationCascadeSelect.php`: Custom Filament field component
  - `PropertyFormSchema.php`: Main form schema class
  - `PropertyValidationRules.php`: Validation rules class

#### 7.1.4 كود Filament 4 المطلوب تنفيذه
```php
// PropertyResource form method
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
                        ->required()
                        ->columnSpan(1),
                ]),
                // ... rest of schema
            ]),
        Section::make('الموقع')
            ->schema([
                LocationCascadeSelect::make('location')
                    ->required(),
            ]),
    ]);
}
```

### 7.2 تحليل ملف group_630c853edd12a.json - اضافة وحدة
#### 7.2.1 تحليل الحقول الموجودة
- **post_title**: عنوان الوحدة (required, text, width: 33%)
- **unit_in_property**: تابعة إلى عقار (required, post_object: alh_property, width: 33%)
- **apartment_tenant**: المستأجر (required, user field, role: alh_tenant, width: 33%)
- **rant_price**: سعر الإيجار (required, text, width: 33%)
- **unit_type**: نوع الوحدة (required, taxonomy: prop_type, width: 33%)
- **unit_status**: تصنيف الوحدة (required, taxonomy: prop_status, width: 33%)
- **rooms_number**: عدد الغرف (optional, number, width: 25%)
- **bathroom_number**: عدد دورات المياه (optional, number, width: 25%)
- **balconies_number**: عدد الشرفات (optional, number, width: 25%)
- **unit_floor**: طابق الوحدة (optional, text, width: 25%)
- **Laundry_Rooة**: غرفة غسيل (optional, number, width: 25%)
- **Insurance**: التأمين (optional, number, width: 25%)
- **Electricity_account_number**: رقم حساب الكهرباء (optional, number, width: 25%)
- **water_expense**: مصروف المياه (optional, number, width: 25%)
- **storehouse**: مستودع (optional, select: لا يوجد/يوجد, width: 50%)
- **Balance**: ميزان (optional, select: لا يوجد/يوجد, width: 50%)
- **unit_features**: المميزات (optional, custom field: acfe_taxonomy_terms, taxonomy: prop_feature)
- **unit_plan**: مخطط الوحدة (optional, image upload)

#### 7.2.2 تصميم Filament Form Schema للوحدات
- **Property Relationship**: Select field with property options
- **Tenant Assignment**: User selection with alh_tenant role filter
- **Conditional Fields**: Some fields might be conditional based on unit type
- **Feature Selection**: Checkbox group for unit features
- **Image Upload**: Unit plan image with preview
- **الاختبارات**:
  - Unit: `UnitFormSchemaTest::test_property_relationship()`
  - Unit: `UnitFormSchemaTest::test_tenant_assignment()`
  - Integration: `UnitFormTest::test_feature_selection()`

#### 7.2.3 التحديات الخاصة بالملف
- **Property-Unit Relationship**: Dynamic unit creation based on selected property
- **Tenant Role Filter**: Only users with alh_tenant role should be selectable
- **Custom Features Field**: `acfe_taxonomy_terms` needs custom implementation
- **Mixed Field Types**: Numbers, selects, checkboxes in same form
- **Arabic Field Names**: Some field names have Arabic characters (Laundry_Rooة)
- **المكونات المطلوبة**:
  - `UnitFormSchema.php`: Main unit form schema
  - `PropertyUnitRelationship.php`: Property-unit relationship component
  - `TenantSelector.php`: Role-filtered user selector

### 7.3 تحليل ملف group_630c8f600ac1a.json - اضافة مالك
#### 7.3.1 تحليل الحقول الموجودة
- **first_name**: الاسم الأول (required, special type: first_name, width: 50%)
- **last_name**: الاسم الثاني (required, special type: last_name, width: 50%)
- **username**: اسم المستخدم (required, special type: username, width: 50%, allow_edit: 0)
- **owner_email**: الإيميل (optional, text, width: 50%)
- **owner_id_file**: ملف الهوية (optional, file: PDF only, width: 50%)
- **user_password**: الباسورد (required, password field, width: 50%)
- **owner_phone**: رقم الهاتف 1 (required, text, width: 50%)
- **owner_phone_2**: رقم الهاتف 2 (optional, text, width: 50%)

#### 7.3.2 تصميم Filament Form Schema للملاك
- **User Creation Form**: Special handling for user account creation
- **Password Security**: Proper password field with validation
- **File Upload Security**: PDF file validation for ID documents
- **Email Validation**: Optional but validated email format
- **Phone Validation**: Required primary phone, optional secondary
- **الاختبارات**:
  - Unit: `OwnerFormSchemaTest::test_user_creation()`
  - Unit: `OwnerFormSchemaTest::test_password_validation()`
  - Integration: `OwnerFormTest::test_file_upload_security()`

#### 7.3.3 التحديات الخاصة بالملف
- **Special Field Types**: first_name, last_name, username are custom ACF types
- **User Account Integration**: Form creates WordPress user, need Laravel User model
- **Role Assignment**: Automatic assignment of owner role after creation
- **File Security**: PDF upload with proper storage and access control
- **Username Uniqueness**: Username field with uniqueness validation
- **المكونات المطلوبة**:
  - `OwnerCreationForm.php`: User creation with owner role
  - `SecureFileUpload.php`: PDF file upload component
  - `UserValidationRules.php`: Username and email validation

### 7.4 تحليل ملف group_630c941bf169f.json - اضافة مستأجر
#### 7.4.1 تحليل الحقول الموجودة
- **first_name**: الاسم الأول (required, special type: first_name, width: 50%)
- **last_name**: الاسم الثاني (required, special type: last_name, width: 50%)
- **user_email**: الإيميل (required, email validation, width: 50%)
- **tenant_phone**: رقم الهاتف 1 (required, text, width: 50%)
- **tenant_phone_2**: رقم الهاتف 2 (optional, text, width: 50%)
- **tenant_id_file**: ملف الهوية (optional, file: PDF only, width: 50%)

#### 7.4.2 تصميم Filament Form Schema للمستأجرين
- **Email Required**: Unlike owner, tenant email is mandatory
- **Role-based User Creation**: Automatic tenant role assignment
- **Document Management**: ID file upload with secure storage
- **Contact Validation**: Phone number format validation
- **الاختبارات**:
  - Unit: `TenantFormSchemaTest::test_email_requirement()`
  - Unit: `TenantFormSchemaTest::test_role_assignment()`
  - Integration: `TenantFormTest::test_document_upload()`

#### 7.4.3 التحديات الخاصة بالملف
- **Post Type Difference**: Applied to "property_renter" not standard user
- **Email Mandatory**: Different validation rules from owner form
- **Similar Structure**: Much like owner but with different requirements
- **المكونات المطلوبة**:
  - `TenantCreationForm.php`: User creation with tenant role
  - `EmailValidationComponent.php`: Required email validation
  - `ContactInformationSection.php`: Phone number management

### 7.5 تحليل ملف group_630cb0f029b36.json - اضافة صيانة
#### 7.5.1 تحليل الحقول الموجودة
- **post_title**: اسم الصيانة (required, text)
- **maintenance_type**: نوع الصيانة (required, select: general_maintenance/special_maintenance)
- **maintenance_property**: صيانة العقار (required, post_object: alh_property, width: 25%)
- **maintenance_unit**: صيانة الوحدة (required when special_maintenance, post_object: alh_unit, width: 25%)
- **total_maintenance_cost**: إجمالي تكلفة الصيانة (required, number, width: 50%)
- **maintenance_date**: تاريخ الصيانة (optional, date_picker, format: d/m/Y)
- **purchase_invoice_image_file**: ملف صورة فاتورة المشتريات (optional, file: PDF)
- **hand_work_invoice_image_file**: ملف صورة فاتورة عمل اليد (optional, file: PDF)
- **post_content**: وصف الصيانة (required, textarea)

#### 7.5.2 Conditional Logic المطلوب
- **Unit Field**: يظهر فقط عندما يكون maintenance_type = "special_maintenance"
- **Property Relationship**: Property selection affects available units
- **File Organization**: Multiple PDF uploads for different invoice types

#### 7.5.3 تصميم Filament Form Schema للصيانة
```php
Section::make('بيانات الصيانة')
    ->schema([
        TextInput::make('title')->required(),
        Select::make('maintenance_type')
            ->options([
                'general_maintenance' => 'عملية عامة',
                'special_maintenance' => 'عملية خاصة'
            ])
            ->reactive()
            ->required(),
        Select::make('property_id')
            ->relationship('property', 'name')
            ->reactive()
            ->required(),
        Select::make('unit_id')
            ->relationship('unit', 'name')
            ->visible(fn ($get) => $get('maintenance_type') === 'special_maintenance')
            ->required(fn ($get) => $get('maintenance_type') === 'special_maintenance'),
    ])
```

#### 7.5.4 التحديات الخاصة بالملف
- **Conditional Fields**: Complex conditional logic based on maintenance type
- **Multiple File Uploads**: Different PDF files for different invoice types
- **Property-Unit Relationship**: Dynamic unit loading based on selected property
- **Date Format**: Arabic date format (d/m/Y) needs proper localization
- **Cost Calculation**: Numerical validation for maintenance costs
- **المكونات المطلوبة**:
  - `MaintenanceFormSchema.php`: Form with conditional logic
  - `PropertyUnitSelector.php`: Cascading property-unit selection
  - `MultipleFileUpload.php`: Component for multiple PDF uploads

### 7.6 تحليل ملف group_631d6b61ba505.json - لوحة التحكم العامة
#### 7.6.1 تحليل الحقول الموجودة (Options Page)
- **ratio_manage_property**: نسبة إدارة العقار (optional, number, default: 10, min: 1, max: 100)
- **time_period_delay_payments**: مدة سماح تأخير الدفعات (optional, number, default: 10, min: 1, max: 100)
- **day_owner_deserves_the_monthly_payment**: في أي يوم من الشهر يستحق المالك الدفعة الشهرية (optional, number, default: 10, min: 1, max: 31)
- **invoice_logo**: لوجو الفاتورة (optional, image upload, full preview)

#### 7.6.2 طبيعة الملف الخاصة
- **Options Page**: Not applied to post type, but to options page "alhayah_dashboard"
- **Global Settings**: System-wide configuration values
- **Default Values**: All fields have meaningful defaults
- **Validation Ranges**: Specific min/max values for business rules

#### 7.6.3 تصميم Filament Settings Form
```php
// In AdminPanelProvider or dedicated Settings page
public function form(Form $form): Form
{
    return $form->schema([
        Section::make('إعدادات النظام العامة')
            ->schema([
                TextInput::make('property_management_rate')
                    ->label('نسبة إدارة العقار (%)')
                    ->numeric()
                    ->default(10)
                    ->minValue(1)
                    ->maxValue(100)
                    ->suffix('%'),
                TextInput::make('payment_grace_period')
                    ->label('مدة سماح تأخير الدفعات (أيام)')
                    ->numeric()
                    ->default(10)
                    ->minValue(1)
                    ->maxValue(100),
                TextInput::make('monthly_payment_due_day')
                    ->label('يوم استحقاق الدفعة الشهرية')
                    ->numeric()
                    ->default(10)
                    ->minValue(1)
                    ->maxValue(31),
                FileUpload::make('invoice_logo')
                    ->label('لوجو الفاتورة')
                    ->image()
                    ->directory('invoice-logos'),
            ])
    ]);
}
```

#### 7.6.4 التحديات الخاصة بالملف
- **Global Settings Storage**: Need Laravel config or settings table
- **Business Rules Validation**: Percentage and day ranges are business-critical
- **Logo Upload**: Image upload with proper size and format validation
- **Settings Access**: Global access across application for calculations

### 7.7 تحليل ملف group_6347b8bf21a23.json - unit contract filter
#### 7.7.1 تحليل الحقول الموجودة (Filter Form)
- **searchstring**: اسم العقد (optional, text, placeholder: "البحث على اسم العقد")
- **unit_name**: الوحدة (optional, post_object: alh_unit, width: 50%)
- **property_name**: العقار (optional, post_object: alh_property, width: 50%)
- **tenant_name**: المستأجر (optional, user field, role: "renter", width: 33%)
- **contract_price**: السعر (optional, number, width: 33%)
- **end_date**: التاريخ (optional, date_picker, format: d/m/Y, width: 33%)

#### 7.7.2 طبيعة الملف الخاصة
- **Filter Form**: This is a search/filter form, not a data entry form
- **Post Type Generic**: Applied to "post" which suggests it's a widget/filter component
- **Search Functionality**: Designed for filtering contract lists
- **Multiple Criteria**: Allows filtering by various contract attributes

#### 7.7.3 تصميم Filament Filter Components
```php
// In UnitContractResource table filters
public static function table(Table $table): Table
{
    return $table
        ->filters([
            TextFilter::make('search')
                ->label('البحث على اسم العقد')
                ->placeholder('اسم العقد'),
            SelectFilter::make('unit_id')
                ->label('الوحدة')
                ->relationship('unit', 'name'),
            SelectFilter::make('property_id')
                ->label('العقار')
                ->relationship('property', 'name'),
            SelectFilter::make('tenant_id')
                ->label('المستأجر')
                ->relationship('tenant', 'name')
                ->query(function ($query) {
                    return $query->whereHas('roles', function ($q) {
                        $q->where('name', 'tenant');
                    });
                }),
            NumericFilter::make('price')
                ->label('السعر'),
            DateFilter::make('end_date')
                ->label('تاريخ الانتهاء'),
        ]);
}
```

#### 7.7.4 التحديات الخاصة بالملف
- **Role-Based User Filter**: Only users with "renter" role should appear
- **Complex Search**: Multiple field search combinations
- **Date Range Filtering**: End date filtering for contract expiry
- **Price Range**: Numerical filtering for contract prices
- **Relationship Filters**: Property and unit relationship filtering

### 7.8 تحليل ملف group_634cf8e8d4f57.json - اضافة عنوان
#### 7.8.1 تحليل الحقول الموجودة (Location Management)
- **city_state**: المنطقة/المدينة (optional, custom field: acfe_taxonomy_terms, taxonomy: alh_locations)
- **update_term**: تحديث (المنطقة - المدينة - الحي) (optional, text)

#### 7.8.2 طبيعة الملف الخاصة
- **Taxonomy Management**: Applied to taxonomy "alh_locations"
- **Location Hierarchy**: Manages the 4-level location structure
- **Simple Interface**: Only 2 fields for location management
- **Update Functionality**: Suggests ability to update existing location terms

#### 7.8.3 تصميم Filament Location Management
```php
// LocationResource for managing hierarchical locations
public static function form(Schema $schema): Schema
{
    return $schema->schema([
        Section::make('إدارة المواقع')
            ->schema([
                Select::make('parent_id')
                    ->label('المنطقة/المدينة الرئيسية')
                    ->relationship('parent', 'name')
                    ->nullable(),
                TextInput::make('name')
                    ->label('اسم الموقع')
                    ->required(),
                Select::make('level')
                    ->label('مستوى الموقع')
                    ->options([
                        1 => 'منطقة',
                        2 => 'مدينة', 
                        3 => 'مركز',
                        4 => 'حي'
                    ])
                    ->required(),
                TextInput::make('update_notes')
                    ->label('ملاحظات التحديث')
                    ->placeholder('المنطقة - المدينة - الحي'),
            ])
    ]);
}
```

#### 7.8.4 التحديات الخاصة بالملف
- **Hierarchical Structure**: 4-level location taxonomy needs proper tree structure
- **Custom Taxonomy Field**: `acfe_taxonomy_terms` requires custom Filament implementation
- **Update Tracking**: Update field suggests version control or change tracking
- **Tree View**: Location management needs tree/hierarchical display

### 7.9 تحليل ملف group_631bdeda69e45.json - لوحة التحكم (Legacy Settings)
#### 7.9.1 تحليل الحقول الموجودة (Legacy Global Settings)
- **ratio_manage_property**: نسبة ادارة العقار (optional, text field, no validation)

#### 7.9.2 طبيعة الملف الخاصة
- **Legacy Settings**: Simple version of global settings functionality
- **Post Type Application**: Applied to generic "post" type instead of options page
- **Basic Implementation**: No validation, defaults, or formatting
- **Duplicate Functionality**: Overlaps with `group_631d6b61ba505.json` (General Dashboard)

#### 7.9.3 الفرق مع الملف المتقدم
| الخاصية | الملف القديم (631bdeda69e45) | الملف المتقدم (631d6b61ba505) |
|---------|---------------------------|------------------------------|
| نوع التطبيق | Post Type | Options Page |
| Validation | لا يوجد | Min/Max values |
| Default Values | لا يوجد | موجود |
| Field Type | Text | Number |
| Additional Fields | حقل واحد فقط | 4 حقول شاملة |

#### 7.9.4 قرار التطوير
- **Skip Implementation**: هذا الملف يمثل نسخة قديمة ومبسطة
- **Use Advanced Version**: الاعتماد على `group_631d6b61ba505.json` كمرجع
- **Migration Strategy**: دمج البيانات من النسخة القديمة إلى الجديدة
- **Code Cleanup**: إزالة الحقول المكررة والقديمة

#### 7.9.5 التحديات الخاصة بالملف
- **Data Migration**: نقل البيانات الموجودة من النظام القديم
- **Backward Compatibility**: التأكد من عدم فقدان البيانات
- **Field Mapping**: تحديد مقابل الحقل في النسخة المتقدمة
- **Legacy Code Cleanup**: تنظيف الكود المكرر

## 8. ملخص التحليل الشامل لملفات ACF
### 8.1 إحصائيات التحليل
- **عدد الملفات المحللة**: 9 ملفات من أصل 32 ملف ACF JSON
- **أنواع الحقول المكتشفة**: 15 نوع حقل مختلف
- **المكونات المخصصة المطلوبة**: 12 مكون Filament مخصص
- **التحديات التقنية الرئيسية**: 8 تحديات معقدة

### 8.2 أنواع الحقول المكتشفة والمقابل في Filament 4
| نوع الحقل في ACF | المقابل في Filament 4 | ملاحظات |
|-----------------|---------------------|----------|
| `post_title` | `TextInput` | حقل عنوان المنشور |
| `user` | `Select` with relationship | اختيار المستخدمين بناءً على الأدوار |
| `taxonomy` | `Select` with options | تصنيفات WordPress → Laravel Models |
| `post_object` | `Select` with relationship | علاقات المنشورات → Eloquent Relations |
| `text` | `TextInput` | حقول نصية عادية |
| `number` | `TextInput` with numeric | حقول رقمية مع validation |
| `textarea` | `Textarea` | النصوص الطويلة |
| `file` | `FileUpload` | رفع الملفات مع MIME validation |
| `image` | `FileUpload` with image | رفع الصور |
| `date_picker` | `DatePicker` | تواريخ بصيغة عربية |
| `select` | `Select` | قوائم الاختيار |
| `email` | `TextInput` with email rule | البريد الإلكتروني |
| `password` | `TextInput` with password | كلمات المرور |
| `taxonmy_level_selector` | `Select` with relationship | اختيار المواقع الهرمية - يدعم 6 مستويات |
| `acfe_taxonomy_terms` | `CheckboxList` | اختيار متعدد للتصنيفات |

### 8.3 المكونات المخصصة المطلوب تطويرها
1. **LocationCascadeSelect.php**: للمواقع الهرمية 6 مستويات (تحديث: اكتشف دعم للمستوى 6)
2. **PropertyUnitSelector.php**: اختيار الوحدة بناءً على العقار
3. **TenantSelector.php**: اختيار المستأجرين بناءً على الدور
4. **SecureFileUpload.php**: رفع آمن للمستندات PDF
5. **MultipleFileUpload.php**: رفع متعدد للفواتير
6. **ConditionalFieldsComponent.php**: منطق الحقول الشرطية
7. **SettingsFormComponent.php**: نموذج الإعدادات العامة
8. **FilterTableComponent.php**: مكونات البحث والتصفية
9. **UserCreationForm.php**: إنشاء المستخدمين مع الأدوار
10. **PropertyFormSchema.php**: نموذج العقارات الشامل
11. **UnitFormSchema.php**: نموذج الوحدات
12. **MaintenanceFormSchema.php**: نموذج الصيانة

### 8.4 التحديات التقنية الرئيسية
1. **الحقول الشرطية**: تطبيق المنطق الشرطي في Filament
2. **المواقع الهرمية**: 6 مستويات من المواقع المترابطة مع تصنيف alh_locations
3. **أدوار المستخدمين**: تصفية المستخدمين حسب الأدوار
4. **رفع الملفات الآمن**: التحقق من نوع الملف والأمان
5. **التخطيط العربي**: دعم RTL والخطوط العربية
6. **التواريخ العربية**: تنسيق التواريخ بالصيغة العربية
7. **العلاقات المعقدة**: ربط العقارات بالوحدات والمستأجرين
8. **الإعدادات العامة**: تخزين واسترجاع إعدادات النظام

### 8.5 خطة التنفيذ المرحلية
#### المرحلة الأولى: الحقول الأساسية (الأسبوع 1-2)
- [ ] تحويل حقول النصوص والأرقام البسيطة
- [ ] تطوير مكونات رفع الملفات الآمنة
- [ ] إعداد النماذج الأساسية للعقارات والوحدات

#### المرحلة الثانية: الحقول المعقدة (الأسبوع 3-4)
- [ ] تطوير مكون المواقع الهرمية
- [ ] تطبيق المنطق الشرطي للحقول
- [ ] تطوير مكونات اختيار المستخدمين بناءً على الأدوار

#### المرحلة الثالثة: التكامل والتحسين (الأسبوع 5-6)
- [ ] دمج جميع المكونات في النماذج
- [ ] تطبيق التصفية المتقدمة
- [ ] تحسين واجهة المستخدم العربية

#### المرحلة الرابعة: الاختبار والنشر (الأسبوع 7-8)
- [ ] اختبار شامل لجميع النماذج
- [ ] اختبار الأداء والأمان
- [ ] توثيق المكونات المخصصة

## 9. استكمال تحليل ملفات ACF المتبقية (23 ملف)
تم تحليل جميع ملفات ACF JSON بالتفصيل. تم نقل ملف group_630c9940e912f.json إلى القسم 6 لإدارة عقود الملاك.

### 9.1 تحليل ملف group_630ca4c5c2a48.json - عقد مستأجر (Tenant Contract)
#### 9.1.1 تحليل الحقول الموجودة
- **post_title**: اسم العقد (required, post_title, width: 50%)
- **contract_status**: حالة العقد (optional, true_false, default: نشط)
- **unit**: الوحدة (required, post_object: alh_unit, width: 50%)
- **unit_property**: العقار (required, post_object: alh_property, width: 50%)
- **tenant**: المستأجر (required, user field, role: alh_tenant)
- **date_creation_contract**: تاريخ بدأ العقد (required, date_picker, format: d/m/Y)
- **contract_duration_per_month**: مدة التعاقد بالشهر (required, number, default: 12, width: 33%)
- **rent_price**: قيمة الإيجار بالشهر (required, number, min: 1, width: 33%)
- **payment_payments**: سداد الدفعات (required, select: شهريا/ربع سنوي/نصف سنوي/سنوي, width: 33%)
- **contract_image**: صورة العقد (optional, image/PDF upload)
- **other_nots**: ملاحظات اخري (optional, textarea)

#### 9.1.2 التحديات الخاصة
- **Dual Property Relationship**: العقد مرتبط بالوحدة والعقار منفصلين
- **Tenant Role Filter**: المستأجر محدد بدور alh_tenant
- **Contract Status Toggle**: حالة العقد كمفتاح تشغيل/إيقاف
- **Date Format Difference**: تنسيق تاريخ مختلف عن عقد المالك
- **Image vs File Upload**: رفع صورة بدلاً من ملف PDF

### 9.2 تحليل ملفات إدارة المواقع الهرمية
#### 9.2.1 ملف group_630ce1f97d81e.json - المناطق (Regions)
- **add_state**: اضافة منطقة (optional, text)
- **Applied to**: post type "post"

#### 9.2.2 ملف group_630ce23459841.json - المدن (Cities)
- **city_area**: المنطقة التابعة (required, taxonmy_level_selector, page_type: 3)
- **add_city**: اضافة مدينة (optional, text)

#### 9.2.3 ملف group_630ce2547deb6.json - الاحياء (Districts) ✅ [تم المراجعة]
- **city_center**: المركز (required, taxonmy_level_selector, page_type: 5, custom_type: 6, placeholder: "اختار مدينة")
- **area**: حي (optional, text)
- **Applied to**: taxonomy "alh_locations"

**تحليل تفصيلي:**
- نوع حقل مخصص: `taxonmy_level_selector` للاختيار الهرمي
- مستوى الهرمية: المستوى 6 (custom_type: 6)
- مرتبط بتصنيف: `alh_locations`
- دعم لواجهة UI تفاعلية
- مطلوب تطوير مكون Filament مخصص للاختيار الهرمي

**متطلبات التنفيذ في Filament 4:**
```php
// LocationResource في LocationCluster
Select::make('city_center')
    ->label(__('المركز'))
    ->relationship('parent', 'name', function ($query) {
        return $query->where('level', 5); // المستوى 5 للمراكز
    })
    ->required()
    ->placeholder(__('اختار مدينة'))
    ->searchable()
    ->preload(),

TextInput::make('area')
    ->label(__('حي'))
    ->maxLength(255),
```

#### 9.2.4 ملف group_639ee4281793f.json - المركز (Centers)
- **city_area**: المدينة التابعة (required, taxonmy_level_selector, page_type: 4)
- **add_city_center**: مركز (optional, text)

### 9.3 تحليل ملفات إدارة التصنيفات
#### 9.3.1 ملف group_630ce268ccd4f.json - حالة العقار (Property Status)
- **add_property_status**: اضافة حالة للعقار (optional, text)

#### 9.3.2 ملف group_630ce28530fc2.json - نوع العقار (Property Type)
- **add_new_property**: تحديث / اضافة نوع عقار (optional, text)

#### 9.3.3 ملف group_630ce4b725722.json - مميزات العقار (Property Features)
- **add_property_feature**: تحديث / اضافة ميزة للعقار (optional, text)

#### 9.3.4 ملف group_66af715725688.json - نوع الوحدة (Unit Type)
- **تعديل__اضافة_نوع_الوحدة**: تعديل / اضافة نوع الوحدة (optional, text)

#### 9.3.5 ملف group_66af718d5f1f7.json - تصنيف الوحدة (Unit Classification)
- **تعديل__اضافة_تصنيف_الوحدة**: تعديل / اضافة تصنيف الوحدة (optional, text)

### 9.5 تحليل ملفات النظام المالي المتقدم
#### 9.5.1 ملف group_631d9e384ad89.json - دفعات تحصيل (Collection Payments)
- **post_title**: الاسم (required, post_title)
- **contract**: العقد (required, post_object: unit_contract, width: 50%)
- **contract_sell**: القيمة المالية (required, text, width: 50%)
- **contract_status**: حالة التحصيل (required, button_group: تم التحصيل/تستحق التحصيل/المؤجلة/تجاوزة المدة)
- **collection_date_start**: بداية التاريخ (conditional, date_picker, format: d/m/Y)
- **collection_date_end**: الي تاريخ (conditional, date_picker, format: d/m/Y)
- **payment_date**: تاريخ التحصيل (conditional when collected, date_picker, format: Y-m-d)
- **delay_reason**: سبب التاجيل (conditional when delayed, textarea)
- **delay_duration**: مدة التاجيل بالايام (conditional when delayed, text)
- **notes_of_exceeding_the_payment_period**: ملاحظات في حالة تجاوز مدة الدفع (conditional when overdue, textarea)
- **notification_date**: تاريخ الإشعار (hidden field)
- **uint_id**: معرف الوحدة (hidden field)
- **owner**: المالك (hidden field)

#### 9.5.2 المنطق الشرطي المعقد للدفعات
```php
// Conditional logic for collection payments
when contract_status == 'collected':
  - Show: collection_date_start, collection_date_end, payment_date
when contract_status == 'delayed':
  - Show: delay_reason, delay_duration
when contract_status == 'overdue':
  - Show: notes_of_exceeding_the_payment_period
when contract_status == 'worth_collecting':
  - Show: collection_date_start, collection_date_end
```

#### 9.5.3 ملف group_631da28be0c0a.json - دفعات توريد (Supply Payments)
- **post_title**: الاسم (required, post_title)
- **contract**: العقد (optional, post_object: property_contract, read_only)
- **contract_sell**: القيمة المالية (required, text)
- **supply_status**: حالة التوريد (required, button_group: قيد الانتظار/تستحق التوريد/تم التوريد, default: pending)
- **supply_date**: تاريخ التوريد (conditional when collected, date_picker)
- **supply_date_worthy**: تاريخ الاستحقاق (conditional when pending/worth_collecting, date_picker, read_only)
- **property**: العقار (hidden field)
- **acknowledgment_commitment**: اقرار (conditional when collected, true_false: موافق/غير موافق)
- **owner**: المالك (hidden field)

#### 9.5.4 ملف group_63a825f6efcf7.json - التزام حكومي (Government Payment) ✅ [تم المراجعة]
##### 9.5.4.1 تحليل الحقول الموجودة من النظام القديم
تم تحليل ملف الالتزامات الحكومية الذي يحتوي على 4 حقول رئيسية:

**الحقول الأساسية:**
- **الاسم** (post_title): اسم الالتزام الحكومي (required, post_title)
- **الوحدة** (unit_id): الوحدة المرتبطة (required, post_object: alh_unit, UI enabled, width: 50%)
- **المبلغ** (total_pay): مبلغ الالتزام (required, text, width: 50%)
- **تاريخ الدفع** (payment_date): تاريخ السداد الفعلي (conditional, date_picker: Y-m-d)

**المنطق الشرطي المكتشف:**
- تاريخ الدفع يظهر فقط عند title == 'collected'
- نمط جديد: استخدام حقل العنوان كمؤشر حالة

##### 9.5.4.2 Filament Resource Schema المطلوب
```php
// GovernmentPaymentResource.php
Section::make('بيانات الالتزام الحكومي')
    ->schema([
        TextInput::make('title')
            ->label('الاسم')
            ->required()
            ->columnSpan(['md' => 6]),
            
        Select::make('unit_id')
            ->label('الوحدة')
            ->relationship('unit', 'name')
            ->searchable()
            ->preload()
            ->required()
            ->columnSpan(['md' => 6]),
            
        TextInput::make('total_pay')
            ->label('المبلغ')
            ->required()
            ->numeric()
            ->minValue(1)
            ->prefix('ر.س')
            ->columnSpan(['md' => 6]),
            
        DatePicker::make('payment_date')
            ->label('تاريخ الدفع')
            ->displayFormat('Y-m-d')
            ->visible(fn (Get $get) => $get('title') === 'collected')
            ->columnSpan(['md' => 6]),
    ])
```

##### 9.5.4.3 النموذج المطلوب
```php
// GovernmentPayment.php
class GovernmentPayment extends Model
{
    protected $fillable = ['title', 'unit_id', 'total_pay', 'payment_date'];
    protected $casts = ['payment_date' => 'date', 'total_pay' => 'decimal:2'];
    
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
```

##### 9.5.4.4 الأنماط الجديدة المكتشفة
1. **Status-Based Conditional Logic**: استخدام العنوان كمؤشر حالة
2. **Simple Government Structure**: بنية مبسطة من 4 حقول فقط
3. **Unit-Only Relationship**: مرتبط بالوحدات فقط وليس العقارات
4. **Regulatory Compliance**: دفعات التزام حكومية وليس تشغيلية

##### 9.5.4.5 الاختبارات المطلوبة
- **Unit**: `GovernmentPaymentModelTest::test_model_fields()`
- **Unit**: `GovernmentPaymentModelTest::test_unit_relationship()`
- **Unit**: `GovernmentPaymentResourceTest::test_form_validation()`
- **Unit**: `GovernmentPaymentResourceTest::test_conditional_date_field()`
- **Feature**: `GovernmentPaymentResourceTest::test_create_payment_workflow()`
- **Integration**: `GovernmentPaymentTest::test_unit_payment_association()`

### 9.6 تحليل ملفات التصفية والبحث المتقدم
#### 9.6.1 ملف group_6344194007cfd.json - property filter
- **searchstring**: الاسم (optional, text, placeholder: "")
- **state**: المنطقة (optional, taxonmy_level_selector, page_type: 3, width: 25%)
- **city**: المدينة (optional, taxonmy_level_selector, page_type: 4, width: 25%)
- **city_center**: المركز (optional, taxonmy_level_selector, page_type: 5, width: 25%)
- **area**: الحي (optional, taxonmy_level_selector, page_type: 8, width: 25%)

#### 9.6.2 ملف group_63452111dd96c.json - unit filter
- **unit_name**: اسم الوحدة (optional, text, width: 50%)
- **property**: العقار (optional, post_object: alh_property, width: 50%)
- **rooms_number**: عدد الغرف (optional, number, width: 25%)
- **bathrooms_number**: عدد دورات المياة (optional, number, width: 25%)
- **min_rant_price**: السعر : حد ادني (optional, number, width: 25%)
- **max_rant_price**: السعر : حد اقصي (optional, number, width: 25%)

#### 9.6.3 ملف group_6345351e7e1cb.json - property contract filter
- **contract_name**: اسم العقد (optional, text, width: 50%)
- **contract_owner**: اسم المالك (optional, user, role: owner, width: 50%)
- **contract_property**: العقار (optional, post_object: alh_property, width: 50%)
- **contract_price**: السعر (optional, number, width: 50%)

#### 9.6.4 ملف group_6347d472b488b.json - collection payment filter
- **searchstring**: اسم البيان (optional, text)
- **unit_contract**: عقد الوحدة (optional, post_object: unit_contract, width: 50%)
- **unit_contract_price**: السعر (optional, number, width: 50%)
- **unit_payment_status**: حالة البيان (optional, select: تم التحصيل/تستحق التحصيل/المؤجلة/تجاوزة المدة, width: 50%)
- **payment_date**: تاريخ البيان (optional, date_picker, format: d/m/Y, width: 50%)

#### 9.6.5 ملف group_6347e50b61fee.json - supply payment filter
- **searchstring**: اسم البيان (optional, text)
- **property_contract**: عقد الوحدة (optional, post_object: property_contract, width: 50%)
- **property_contract_price**: السعر (optional, number, width: 50%)
- **property_payment_status**: حالة البيان (optional, select: في الانتظار/تم التوريد/تستحق التوريد, width: 50%)
- **payment_date**: تاريخ الاستحقاق (optional, date_picker, format: Y-m-d, width: 50%)

#### 9.6.6 ملف group_6347ec28c836d.json - property repair filter
- **searchstring**: بحث الصيانة (optional, text, placeholder: "بحث بالاسم في عمليات الصيانة")
- **maintenance_type**: نوع الصيانة (optional, select: عملية عامة/عملية خاصة, width: 50%)
- **maintenance_property**: العقار (conditional when general_maintenance, post_object: alh_property, width: 50%)
- **maintenance_unit**: الوحدة (conditional when special_maintenance, post_object: alh_unit, width: 50%)
- **total_maintenance_cost**: اجمالي التكلفة (optional, number, width: 50%)
- **maintenance_date**: التاريخ (optional, date_picker, format: d/m/Y, width: 50%)

#### 9.6.7 ملف group_63d8bfbb43ae9.json - User filter
- **searchstring**: الاسم (optional, text, width: 33%)
- **user_unit**: الوحدة (optional, post_object: alh_unit, width: 33%)
- **user_property**: العقار (optional, post_object: alh_property, width: 33%)

### 9.7 تحليل ملفات تعديل المستخدمين
#### 9.7.1 ملف group_63512a89ec7b2.json - تعديل مستأجر (Edit Tenant)
- **first_name**: الاسم الاول (required, first_name, width: 50%)
- **last_name**: الاسم الثاني (required, last_name, width: 50%)
- **username**: اسم المستخدم (optional, text, read_only, width: 50%)
- **user_email**: الايميل (optional, email, width: 100%)
- **tenant_id_file**: ملف الهوية (required, file: PDF, width: 50%)
- **user_password**: الباسورد (optional, password, width: 50%)
- **tenant_phone**: رقم الهاتف 1 (required, text, width: 50%)
- **tenant_phone_2**: رقم الهاتف 2 (optional, text, width: 50%)
- **tenant_adress**: رقم المبني , اسم الشارع , الحي (optional, text, width: 50%)
- **tenant_city**: المدينة (optional, taxonmy_level_selector, page_type: 4, width: 22%)
- **tenant_postal_code**: الرمز البريدي (optional, text, width: 15%)
- **Applied to**: "property_renter" post type

#### 9.7.2 ملف group_635e3a32c2356.json - تعديل مالك (Edit Owner)
- **first_name**: الاسم الاول (required, first_name, width: 50%)
- **last_name**: الاسم الثاني (required, last_name, width: 50%)
- **username**: اسم المستخدم (optional, username, read_only, width: 50%)
- **user_email**: الايميل (required, email, width: 50%)
- **owner_id_file**: ملف الهوية (optional, file: PDF, width: 50%)
- **user_password**: الباسورد (optional, password, width: 50%)
- **owner_phone**: رقم الهاتف 1 (required, text, width: 50%)
- **owner_phone_2**: رقم الهاتف 2 (optional, text, width: 50%)
- **owner_adress**: رقم المبني , اسم الشارع , الحي (required, text, width: 40%)
- **owner_city**: المدينة (optional, taxonmy_level_selector, page_type: 4, width: 22%)
- **owner_postal_code**: الرمز البريدي (optional, text, width: 15%)
- **number**: الرقم الاضافي (optional, text, width: 50%)
- **Applied to**: widget "all"

### 9.8 ملف الإعدادات المكرر
#### 9.8.1 ملف group_631bdeda69e45.json - لوحة التحكم (Duplicate Dashboard)
- **ratio_manage_property**: نسبة ادارة العقار (optional, text)
- **Note**: هذا الملف مكرر لملف group_631d6b61ba505.json المحلل مسبقاً

## 10. الإحصائيات النهائية لتحليل ACF الشامل
### 10.1 إحصائيات التحليل الكاملة
- **عدد الملفات المحللة**: 32 ملف ACF JSON (100% مكتمل)
- **أنواع الحقول المكتشفة**: 23 نوع حقل مختلف
- **المكونات المخصصة المطلوبة**: 21 مكون Filament مخصص
- **التحديات التقنية الرئيسية**: 15 تحدياً معقداً
- **ملفات التصفية**: 8 ملفات تصفية متخصصة
- **ملفات إدارة التصنيفات**: 8 ملفات لإدارة التصنيفات
- **ملفات النظام المالي**: 4 ملفات للدفعات والمالية
- **ملفات إدارة المواقع**: 4 ملفات للمواقع الهرمية

### 10.2 أنواع الحقول المكتشفة والمقابل في Filament 4 (محدث)
| نوع الحقل في ACF | المقابل في Filament 4 | ملاحظات |
|-----------------|---------------------|----------|
| `post_title` | `TextInput` | حقل عنوان المنشور |
| `user` | `Select` with relationship | اختيار المستخدمين بناءً على الأدوار |
| `taxonomy` | `Select` with options | تصنيفات WordPress → Laravel Models |
| `post_object` | `Select` with relationship | علاقات المنشورات → Eloquent Relations |
| `text` | `TextInput` | حقول نصية عادية |
| `number` | `TextInput` with numeric | حقول رقمية مع validation |
| `textarea` | `Textarea` | النصوص الطويلة |
| `file` | `FileUpload` | رفع الملفات مع MIME validation |
| `image` | `FileUpload` with image | رفع الصور |
| `date_picker` | `DatePicker` | تواريخ بصيغة عربية |
| `select` | `Select` | قوائم الاختيار |
| `email` | `TextInput` with email rule | البريد الإلكتروني |
| `password` | `TextInput` with password | كلمات المرور |
| `taxonmy_level_selector` | `Select` with relationship | اختيار المواقع الهرمية - يدعم 6 مستويات |
| `acfe_taxonomy_terms` | `CheckboxList` | اختيار متعدد للتصنيفات |
| `true_false` | `Toggle` | مفتاح تشغيل/إيقاف |
| `button_group` | `Radio` | اختيار من مجموعة أزرار |
| `message` | `Placeholder` | رسائل توضيحية |
| `acfe_hidden` | `Hidden` | حقول مخفية |
| `first_name` | `TextInput` with validation | الاسم الأول مع validation خاص |
| `last_name` | `TextInput` with validation | اسم العائلة مع validation خاص |
| `username` | `TextInput` with unique rule | اسم المستخدم مع فحص التفرد |
| `filename` | `TextInput` | اسم الملف |

### 10.3 المكونات المخصصة المطلوب تطويرها (محدث)
1. **LocationCascadeSelect.php**: للمواقع الهرمية 4 مستويات (المنطقة → المدينة → المركز → الحي)
2. **PropertyUnitSelector.php**: اختيار الوحدة بناءً على العقار المحدد
3. **TenantSelector.php**: اختيار المستأجرين بناءً على دور alh_tenant
4. **OwnerSelector.php**: اختيار الملاك بناءً على دور owner
5. **SecureFileUpload.php**: رفع آمن للمستندات PDF مع التحقق
6. **MultipleFileUpload.php**: رفع متعدد للفواتير والوثائق
7. **ConditionalFieldsComponent.php**: منطق الحقول الشرطية المعقد
8. **SettingsFormComponent.php**: نموذج الإعدادات العامة
9. **FilterTableComponent.php**: مكونات البحث والتصفية المتقدمة
10. **UserCreationForm.php**: إنشاء المستخدمين مع الأدوار المحددة
11. **PropertyFormSchema.php**: نموذج العقارات الشامل
12. **UnitFormSchema.php**: نموذج الوحدات مع المميزات
13. **MaintenanceFormSchema.php**: نموذج الصيانة مع المنطق الشرطي
14. **PropertyOwnerContractFormSchema.php**: نموذج عقود الملاك
15. **ContractFormSchema.php**: نموذج العقود (مستأجرين)
16. **PaymentStatusManager.php**: إدارة حالات الدفعات المعقدة
17. **CollectionPaymentForm.php**: نموذج دفعات التحصيل مع المنطق الشرطي
18. **SupplyPaymentForm.php**: نموذج دفعات التوريد
19. **GovernmentPaymentForm.php**: نموذج الالتزامات الحكومية
20. **TaxonomyManagementForm.php**: نماذج إدارة التصنيفات
21. **UserEditForm.php**: نماذج تعديل المستخدمين (ملاك ومستأجرين)

### 10.4 التحديات التقنية الرئيسية (محدث)
1. **الحقول الشرطية المعقدة**: تطبيق منطق شرطي معقد لدفعات التحصيل والتوريد
2. **المواقع الهرمية**: 4 مستويات من المواقع المترابطة مع page_type مختلفة
3. **أدوار المستخدمين المتعددة**: تصفية المستخدمين حسب الأدوار (alh_tenant, owner, renter)
4. **رفع الملفات الآمن**: التحقق من نوع الملف (PDF فقط) والأمان
5. **التخطيط العربي المتقدم**: دعم RTL والخطوط العربية مع أحجام متغيرة
6. **التواريخ بصيغ متعددة**: d/m/Y و Y-m-d حسب نوع الحقل
7. **العلاقات المعقدة**: ربط العقارات بالوحدات والمستأجرين والعقود
8. **الإعدادات العامة**: تخزين واسترجاع إعدادات النظام من options page
9. **حالات الدفعات المتعددة**: إدارة 4 حالات مختلفة للتحصيل و 3 للتوريد
10. **المنطق الشرطي للصيانة**: عرض حقول مختلفة حسب نوع الصيانة
11. **التصفية المتقدمة**: 8 أنواع مختلفة من التصفية والبحث
12. **إدارة التصنيفات**: 8 أنواع مختلفة من التصنيفات
13. **Custom ACF Field Types**: first_name, last_name, username, taxonmy_level_selector
14. **Button Group vs Select**: اختيار النوع المناسب حسب السياق
15. **Hidden Fields Management**: إدارة الحقول المخفية للعلاقات

### 10.5 إدارة البيانات القديمة والترحيل (جديد)
#### 10.5.1 ملفات ACF المكررة المكتشفة
- **group_631bdeda69e45.json**: نسخة مبسطة من لوحة التحكم (تم اكتشافها حديثاً)
- **group_631d6b61ba505.json**: النسخة المتقدمة من لوحة التحكم
- **تحدي الترحيل**: دمج البيانات من النسخة القديمة إلى الجديدة
- **ملاحظة**: قد توجد ملفات مكررة أخرى تحتاج للاكتشاف

#### 10.5.2 استراتيجية التعامل مع الملفات القديمة
1. **تحديد الملفات المكررة**: فحص جميع الملفات لاكتشاف التكرار والازدواجية
2. **تحليل الفروق**: مقارنة البيانات والحقول بين النسخ المختلفة
3. **خطة الدمج**: تصميم خطة لدمج البيانات دون فقدان أي معلومات
4. **تنظيف البيانات**: إزالة الحقول المكررة بعد الترحيل الناجح
5. **أرشفة الملفات القديمة**: الاحتفاظ بنسخة احتياطية للملفات القديمة

#### 10.5.3 مكونات الترحيل المطلوبة
- **LegacyDataMigration.php**: فئة رئيسية لترحيل البيانات القديمة
- **DuplicateFieldDetector.php**: كشف الحقول والملفات المكررة
- **DataMergeService.php**: خدمة دمج البيانات من المصادر المختلفة
- **MigrationValidator.php**: التحقق من سلامة وصحة الترحيل
- **ACFFieldMapper.php**: ربط حقول ACF القديمة بحقول Laravel الجديدة

#### 10.5.4 الاختبارات المطلوبة للترحيل
- **Migration Test**: اختبار ترحيل البيانات كاملة
- **Data Integrity Test**: اختبار سلامة البيانات بعد الترحيل
- **Duplicate Detection Test**: اختبار كشف التكرار
- **Field Mapping Test**: اختبار ربط الحقول بصحة

### 10.6 خطة التنفيذ المرحلية المحدثة
#### المرحلة الأولى: الأساسيات والمواقع (الأسبوع 1-2)
- [ ] **كشف البيانات المكررة**: تطوير DuplicateFieldDetector لفحص جميع ملفات ACF
- [ ] **تحليل البيانات القديمة**: مقارنة وتحليل الفروق بين الملفات المكررة  
- [ ] تطوير مكون المواقع الهرمية LocationCascadeSelect
- [ ] تحويل حقول النصوص والأرقام البسيطة
- [ ] تطوير مكونات رفع الملفات الآمنة
- [ ] إعداد أنواع الحقول المخصصة (first_name, last_name, username)

#### المرحلة الثانية: النماذج الأساسية (الأسبوع 3-4)
- [ ] تطوير نماذج العقارات والوحدات
- [ ] تطوير نماذج المستخدمين (إنشاء وتعديل)
- [ ] تطبيق المنطق الشرطي الأساسي
- [ ] تطوير مكونات اختيار المستخدمين بناءً على الأدوار

#### المرحلة الثالثة: العقود والمالية (الأسبوع 5-6)
- [ ] تطوير نماذج العقود (ملاك ومستأجرين)
- [ ] تطوير النظام المالي المعقد (دفعات التحصيل والتوريد)
- [ ] تطبيق الحقول الشرطية المعقدة للدفعات
- [ ] تطوير نموذج الالتزامات الحكومية

#### المرحلة الرابعة: الصيانة والتصفية (الأسبوع 7-8)
- [ ] تطوير نموذج الصيانة مع المنطق الشرطي
- [ ] تطوير جميع مكونات التصفية والبحث (8 أنواع)
- [ ] تطوير نماذج إدارة التصنيفات (8 أنواع)
- [ ] تطوير نموذج الإعدادات العامة

#### المرحلة الخامسة: التكامل والتحسين (الأسبوع 9-10)
- [ ] دمج جميع المكونات في النماذج
- [ ] تطبيق التصفية المتقدمة في الجداول
- [ ] تحسين واجهة المستخدم العربية
- [ ] تطبيق جميع القواعد الشرطية
- [ ] **تطوير مكونات الترحيل**: LegacyDataMigration, DataMergeService, ACFFieldMapper

#### المرحلة السادسة: ترحيل البيانات والاختبار (الأسبوع 11-12)
- [ ] **ترحيل البيانات الفعلي**: تنفيذ عملية ترحيل البيانات من النظام القديم
- [ ] **اختبار سلامة البيانات**: التحقق من صحة البيانات المرحلة
- [ ] **حل تضارب البيانات**: دمج البيانات المكررة وحل التضاربات
- [ ] اختبار شامل لجميع النماذج والمكونات
- [ ] اختبار الأداء والأمان
- [ ] اختبار المنطق الشرطي المعقد
- [ ] **اختبار الترحيل**: تشغيل جميع اختبارات الترحيل والتحقق
- [ ] توثيق المكونات المخصصة وآلية عملها

### 10.6 تصنيف الملفات حسب الوظيفة
#### ملفات إدارة البيانات الرئيسية (8 ملفات)
1. group_6306387af3214.json - اضافة عقار ✅
2. group_630c853edd12a.json - اضافة وحدة ✅
3. group_630c8f600ac1a.json - اضافة مالك ✅
4. group_630c941bf169f.json - اضافة مستأجر ✅
5. group_630c9940e912f.json - عقد ملاك ✅
6. group_630ca4c5c2a48.json - عقد مستأجر ✅
7. group_630cb0f029b36.json - اضافة صيانة ✅
8. group_63a825f6efcf7.json - التزام حكومي ✅

#### ملفات تعديل المستخدمين (2 ملف)
1. group_63512a89ec7b2.json - تعديل مستأجر ✅
2. group_635e3a32c2356.json - تعديل مالك ✅

#### ملفات النظام المالي (2 ملف)
1. group_631d9e384ad89.json - دفعات تحصيل ✅
2. group_631da28be0c0a.json - دفعات توريد ✅

#### ملفات إدارة المواقع الهرمية (4 ملفات)
1. group_630ce1f97d81e.json - المناطق ✅
2. group_630ce23459841.json - المدن ✅
3. group_630ce2547deb6.json - الاحياء ✅
4. group_639ee4281793f.json - المركز ✅

#### ملفات إدارة التصنيفات (6 ملفات)
1. group_630ce268ccd4f.json - حالة العقار ✅
2. group_630ce28530fc2.json - نوع العقار ✅
3. group_630ce4b725722.json - مميزات العقار ✅
4. group_630cea3fa5da9.json - اضافة حالة عقار (مكرر) ✅
5. group_66af715725688.json - نوع الوحدة ✅
6. group_66af718d5f1f7.json - تصنيف الوحدة ✅

#### ملفات الإعدادات العامة (3 ملفات)
1. group_631d6b61ba505.json - لوحة التحكم العامة ✅
2. group_631bdeda69e45.json - لوحة التحكم (مكرر) ✅
3. group_634cf8e8d4f57.json - اضافة عنوان ✅

#### ملفات التصفية والبحث (8 ملفات)
1. group_6344194007cfd.json - property filter ✅
2. group_63452111dd96c.json - unit filter ✅
3. group_6345351e7e1cb.json - property contract filter ✅
4. group_6347b8bf21a23.json - unit contract filter ✅
5. group_6347d472b488b.json - collection payment filter ✅
6. group_6347e50b61fee.json - supply payment filter ✅
7. group_6347ec28c836d.json - property repair filter ✅
8. group_63d8bfbb43ae9.json - User filter ✅

### 10.7 جدول التطابق النهائي: ACF إلى Filament 4
| وظيفة النظام | ملفات ACF | مكونات Filament 4 المطلوبة | الأولوية |
|-------------|-----------|---------------------------|----------|
| إدارة العقارات والوحدات | 3 ملفات | PropertyFormSchema, UnitFormSchema, LocationCascadeSelect | عالية |
| إدارة المستخدمين | 4 ملفات | UserCreationForm, UserEditForm, TenantSelector, OwnerSelector | عالية |
| إدارة العقود | 2 ملف | PropertyOwnerContractFormSchema, ContractFormSchema, PropertyUnitSelector | عالية |
| النظام المالي | 3 ملفات | CollectionPaymentForm, SupplyPaymentForm, PaymentStatusManager | متوسطة |
| إدارة المواقع | 4 ملفات | LocationCascadeSelect, TaxonomyManagementForm | متوسطة |
| إدارة التصنيفات | 6 ملفات | TaxonomyManagementForm | منخفضة |
| البحث والتصفية | 8 ملفات | FilterTableComponent | منخفضة |
| الإعدادات | 3 ملفات | SettingsFormComponent | منخفضة |
| الصيانة | 2 ملف | MaintenanceFormSchema, ConditionalFieldsComponent | متوسطة |

**إجمالي**: 32 ملف ACF → 21 مكون Filament 4 مخصص + مكونات Filament الأساسية

### 10.8 ملاحظات مهمة للتطوير
1. **الملفات المكررة**: تم اكتشاف ملفين مكررين (لوحة التحكم وحالة العقار)
2. **تنسيقات التاريخ المختلطة**: يجب التعامل مع d/m/Y و Y-m-d حسب السياق
3. **الحقول الشرطية المعقدة**: خاصة في دفعات التحصيل والتوريد والصيانة
4. **أنواع الملفات**: معظم الرفع مقيد بـ PDF فقط ما عدا صور العقود
5. **الأدوار المختلطة**: أدوار مختلفة (alh_tenant, owner, renter) تحتاج معالجة دقيقة
6. **المواقع الهرمية**: page_type مختلف لكل مستوى (3,4,5,8)
7. **العرض الشرطي**: كثير من الحقول تظهر/تختفي حسب الحالة
8. **التصنيفات المتداخلة**: علاقات معقدة بين العقارات والوحدات والمستخدمين

## 11. واجهة المستخدم ولوحة التحكم (UI/Dashboard Migration)
### 11.1 تحليل البنية الحالية للواجهة
#### 11.1.1 نتائج تحليل النظام الحالي (WordPress Theme)
تم تحليل جميع ملفات القوالب في WordPress لفهم أنماط UI الحالية:

**ملفات القوالب المحللة:**
- `index.php` - الصفحة الرئيسية مع widget الإحصائيات
- `header.php` - الهيدر مع التنقل والإشعارات
- `dashboard-page.php` - صفحة لوحة التحكم مع ACF forms
- `author.php` - صفحات المستخدمين مع التقارير
- `widgets/alhiaa-statistics.php` - widget الإحصائيات الرئيسي
- `template-part/property_charts.php` - المخططات والرسوم البيانية
- `template-part/author/content-report-owner.php` - تقارير المالك
- `template-part/archive/content.php` - صفحات الأرشيف والقوائم

#### 11.1.2 أنماط واجهة المستخدم المكتشفة
**1. Layout Structure:**
- Header مع navigation role-based
- Breadcrumbs navigation system
- User welcome section مع notifications
- Main content area مع sidebar support
- RTL layout مع Tajawal/Cairo fonts

**2. Dashboard Widgets:**
- Multi-table statistics views
- Chart integration (ApexCharts)
- Print functionality لكل table
- Real-time notifications system
- Role-based content display

**3. Data Tables:**
- DataTables integration للقوائم
- Bootstrap table styling
- Advanced filtering والبحث
- Export/Print capabilities
- Responsive design

**4. Chart Visualizations:**
- Pie charts for status distribution
- Property/Unit analytics
- Financial reports visualization
- Canvas.js و ApexCharts integration

### 11.2 مهام ترحيل واجهة المستخدم إلى Filament 4
#### 11.2.1 Dashboard الرئيسي
- **المكون**: `DashboardPage.php` (Filament Page)
- **Widgets المطلوبة**:
  - `DelayedPaymentsWidget` - جدول المدفوعات المؤجلة
  - `EmptyPropertiesWidget` - جدول العقارات الفارغة  
  - `PendingCollectionsWidget` - جدول التحصيلات المستحقة
  - `ExpiredContractsWidget` - جدول العقود المنتهية
  - `SystemStatsWidget` - إحصائيات النظام
- **الاختبارات**:
  - Unit: `DashboardPageTest::test_widgets_load_correctly()`
  - Unit: `DashboardWidgetTest::test_data_accuracy()`
  - Integration: `DashboardTest::test_role_based_widget_visibility()`

#### 11.2.2 Property Charts والتصورات المرئية
- **المكون**: `PropertyChartsWidget.php`
- **Features**:
  - Unit status pie charts
  - Property type distribution
  - Revenue vs maintenance charts
  - Interactive property reports
- **Chart Libraries**: Charts.js integration مع Filament
- **الاختبارات**:
  - Unit: `PropertyChartsTest::test_chart_data_generation()`
  - Integration: `PropertyChartsTest::test_chart_interactivity()`

#### 11.2.3 Reports System
- **المكونات**:
  - `OwnerReportPage.php` - تقارير المالك
  - `PropertyReportPage.php` - تقارير العقار
  - `FinancialReportPage.php` - التقارير المالية
- **Features**:
  - PDF export functionality
  - Print-friendly layouts
  - Date range filtering
  - Multi-format exports (PDF, Excel)
- **الاختبارات**:
  - Unit: `ReportGenerationTest::test_pdf_export()`
  - Integration: `ReportTest::test_data_accuracy()`

#### 11.2.4 Data Tables المتقدمة
- **المكون**: `AdvancedTableComponent.php`
- **Features**:
  - Multi-column filtering
  - Export capabilities
  - Real-time search
  - Bulk actions
  - Custom column rendering
- **الاختبارات**:
  - Unit: `DataTableTest::test_filtering_functionality()`
  - Integration: `DataTableTest::test_export_features()`

#### 11.2.5 Notification System
- **المكون**: `NotificationWidget.php`
- **Features**:
  - Real-time notifications
  - Payment due alerts
  - Contract expiry warnings
  - Maintenance reminders
- **الاختبارات**:
  - Unit: `NotificationTest::test_alert_generation()`
  - Integration: `NotificationTest::test_real_time_updates()`

### 11.3 Arabic RTL Support
#### 11.3.1 Localization Setup
- **التكوين**: Arabic RTL layout configuration
- **Fonts**: Cairo/Tajawal font integration
- **Date Formats**: Arabic date formatting
- **الاختبارات**:
  - Unit: `LocalizationTest::test_rtl_layout()`
  - Unit: `LocalizationTest::test_arabic_fonts()`

#### 11.3.2 Custom CSS والتصميم
- **ملفات التصميم**:
  - `rtl.css` - RTL specific styles
  - `custom-dashboard.css` - Dashboard customizations
  - `print-styles.css` - Print-friendly styles
- **الاختبارات**:
  - Unit: `CssTest::test_rtl_styles_applied()`

### 11.4 Role-Based UI
#### 11.4.1 Admin Interface
- **الميزات**: Full system access, user management, reports
- **Panels**: AdminPanel مع all clusters
- **Navigation**: Complete menu structure

#### 11.4.2 Owner Interface  
- **الميزات**: Property reports, financial statements
- **Panels**: OwnerPanel مع limited access
- **Navigation**: Property-focused menu

#### 11.4.3 Manager Interface
- **الميزات**: Operations management, tenant relations
- **Panels**: ManagerPanel مع operational tools
- **Navigation**: Management-focused menu

### 11.5 Integration Requirements
#### 11.5.1 Chart Integration
- **Libraries**: ApexCharts.js for Filament
- **Custom Charts**: Property analytics, financial trends
- **الاختبارات**:
  - Unit: `ChartIntegrationTest::test_apex_charts_loading()`

#### 11.5.2 Export/Print Features
- **PDF Generation**: Reports export to PDF
- **Excel Export**: Data tables to Excel
- **Print Styling**: Print-optimized layouts
- **الاختبارات**:
  - Unit: `ExportTest::test_pdf_generation()`
  - Unit: `ExportTest::test_excel_export()`

#### 11.5.3 Search والتصفية
- **Global Search**: Across all entities
- **Advanced Filters**: Multi-criteria filtering
- **Saved Searches**: User-defined search preferences
- **الاختبارات**:
  - Unit: `SearchTest::test_global_search_functionality()`

### 11.6 Performance Optimization
#### 11.6.1 Dashboard Performance
- **Caching**: Widget data caching
- **Lazy Loading**: Large datasets
- **Optimization**: Query optimization for charts
- **الاختبارات**:
  - Unit: `PerformanceTest::test_dashboard_load_time()`

#### 11.6.2 Mobile Responsiveness
- **Responsive Design**: Mobile-first approach
- **Touch Optimization**: Mobile interactions
- **Performance**: Mobile performance optimization
- **الاختبارات**:
  - Unit: `ResponsiveTest::test_mobile_layout()`

### 11.7 نظام الصفحات الثابتة (Static Pages System)
#### 11.7.1 تحليل page.php من النظام القديم
- **الملف المحلل**: `page.php` - قالب صفحات WordPress الأساسي
- **النموذج القديم**: Twenty Fourteen based template with standard WordPress Loop
- **العناصر الأساسية**:
  - Front page detection with featured posts: `is_front_page() && twentyfourteen_has_featured_posts()`
  - Content template part loading: `get_template_part('content', 'page')`
  - Comment system integration: `comments_open() || get_comments_number()`
  - Sidebar support: `get_sidebar('content')` and `get_sidebar()`
  - Featured content template: `get_template_part('featured-content')`

#### 11.7.2 متطلبات الترحيل إلى Filament 4
- **Filament Custom Pages**: إنشاء صفحات مخصصة للمحتوى الثابت
- **Content Management**: نظام إدارة المحتوى للصفحات
- **Page Hierarchy**: دعم التسلسل الهرمي للصفحات
- **Comment System**: بديل لنظام التعليقات (إذا لزم الأمر)

#### 11.7.3 مكونات Filament المطلوبة
```php
// app/Filament/Pages/StaticPage.php
class StaticPage extends Page
{
    protected static string $view = 'filament.pages.static-page';
    protected static ?string $navigationGroup = 'المحتوى';
    
    public function mount(): void
    {
        // Load page content logic
    }
}

// app/Models/Page.php
class Page extends Model
{
    protected $fillable = [
        'title',
        'content', 
        'slug',
        'is_featured',
        'allow_comments',
        'meta_title',
        'meta_description'
    ];
}
```

#### 11.7.4 Page Management Resource
- **المكون**: `PageResource.php` لإدارة الصفحات
- **Features**:
  - CRUD operations للصفحات
  - Rich text editor للمحتوى
  - SEO meta fields
  - Page visibility settings
  - Featured content management
- **الاختبارات**:
  - Unit: `PageResourceTest::test_page_creation()`
  - Unit: `PageModelTest::test_page_slug_generation()`
  - Integration: `PageDisplayTest::test_front_page_detection()`

#### 11.7.5 Frontend Page Display
- **Route Definition**: تعريف routes للصفحات الثابتة
- **Template Rendering**: عرض قوالب الصفحات
- **SEO Integration**: تحسين محركات البحث
- **الاختبارات**:
  - Feature: `PageDisplayTest::test_page_renders_correctly()`
  - Feature: `SEOTest::test_meta_tags_display()`

#### 11.7.6 التحديات التقنية
- **WordPress Loop Migration**: ترحيل WordPress Loop إلى Eloquent queries
- **Featured Content Logic**: نقل منطق Featured Content
- **Template Part System**: بديل لنظام template parts
- **Comment System**: قرار بشأن الحاجة لنظام التعليقات

#### 11.7.7 الأولوية في التطوير
- **أولوية منخفضة**: الصفحات الثابتة بسيطة ولا تؤثر على العمليات الأساسية
- **المرحلة**: بعد إكمال Core functionality والAuthentication
- **التبعيات**: User Management, Basic UI Components

## 12. إدارة المدن والمواقع (Cities Management)
### 12.1 تحليل ملف group_630ce23459841.json - المدن
#### 12.1.1 تحليل الحقول الموجودة
تم تحليل ملف إدارة المدن والذي يحتوي على حقلين رئيسيين:

- **المنطقة التابعة** (city_area): حقل مخصص للاختيار من المواقع الهرمية
  - Type: `taxonmy_level_selector` (حقل ACF مخصص)
  - Post Type: `alh_locations`
  - Required field مع واجهة مستخدم محسنة
  - Custom type: 6
  - يسمح بالقيم الفارغة (allow_null: true)
- **اضافة مدينة** (add_city): حقل نصي لإضافة مدينة جديدة
  - Type: `text`
  - حقل اختياري لإضافة مدن أثناء التشغيل
  - مدخل نصي بسيط بدون تحقق خاص

#### 12.1.2 أنماط التطوير المكتشفة
1. **Custom Taxonomy Level Selector**: نوع حقل مخصص يحتاج ترجمة إلى Filament
2. **Hierarchical Location System**: نظام مواقع هرمي مع `alh_locations`
3. **Dynamic City Addition**: إضافة المدن ديناميكياً أثناء العمل
4. **Arabic Interface**: واجهة عربية كاملة مع دعم RTL

#### 12.1.3 متطلبات التطوير في Filament
- **نموذج Location المحسن**: تحسين نموذج المواقع لدعم النظام الهرمي
  - إضافة حقول: `level`, `path`, `custom_type`
  - دعم العلاقات الهرمية الكاملة
- **مكون TaxonomyLevelSelector**: مكون Filament مخصص
  - دعم الاختيار الهرمي من المواقع
  - تصفية حسب النوع المخصص
  - واجهة مستخدم محسنة للاختيار
- **خدمة LocationHierarchy**: خدمة إدارة النظام الهرمي
  - بناء شجرة المواقع
  - البحث في المسارات
  - إدارة المستويات المختلفة

#### 12.1.4 التطبيق في Filament 4
```php
// في LocationResource أو CityResource
Select::make('parent_location_id')
    ->label('المنطقة التابعة')
    ->relationship('parentLocation', 'name')
    ->searchable()
    ->getOptionLabelFromRecordUsing(fn ($record) => 
        $record->getHierarchicalName())
    ->required(),

TextInput::make('new_city_name')
    ->label('اضافة مدينة')
    ->afterStateUpdated(function ($state, callable $set) {
        if ($state) {
            // Logic to create new city dynamically
        }
    })
```

#### 12.1.5 الاختبارات المطلوبة
- **Unit Tests**:
  - `LocationHierarchyTest::test_taxonomic_level_structure()`
  - `CityManagementTest::test_dynamic_city_creation()`
  - `LocationSelectorTest::test_hierarchical_selection()`
- **Integration Tests**:
  - `CityResourceTest::test_location_hierarchy_display()`
  - `LocationServiceTest::test_path_generation()`

#### 12.1.6 مكونات Filament المطلوبة
- **TaxonomyLevelSelectorField.php**: مكون اختيار المواقع الهرمي
- **LocationHierarchyService.php**: خدمة إدارة التسلسل الهرمي
- **CityDynamicCreator.php**: مكون إنشاء المدن ديناميكياً
- **LocationPathBuilder.php**: بناء مسارات المواقع

## 13. نموذج تعديل المستأجر (Tenant Edit Form)
### 13.1 تحليل ملف group_63512a89ec7b2.json - تعديل مستأجر
#### 13.1.1 تحليل الحقول الموجودة من النظام القديم
تم تحليل ملف تعديل المستأجر والذي يحتوي على 10 حقول رئيسية + 1 رسالة قسم:

**حقول البيانات الشخصية:**
- **الاسم الاول** (first_name): نوع حقل مخصص `first_name` (required, width: 50%)
- **الاسم الثاني** (last_name): نوع حقل مخصص `last_name` (required, width: 50%)
- **اسم المستخدم** (username): حقل نصي (read_only mode, width: 50%)
- **الايميل** (user_email): حقل بريد إلكتروني (edit mode, width: 100%)
- **الباسورد** (user_password): حقل كلمة مرور (edit mode, width: 50%)

**حقول الاتصال:**
- **رقم الهاتف 1** (tenant_phone): حقل نصي (required, edit mode, width: 50%)
- **رقم الهاتف 2** (tenant_phone_2): حقل نصي (اختياري, edit mode, width: 50%)

**حقول المستندات:**
- **ملف الهوية** (tenant_id_file): حقل ملف (required, PDF only, return: array)

**حقول العنوان:**
- **العنوان** (message): رسالة قسم (width: 13% كفاصل)
- **رقم المبني , اسم الشارع , الحي** (tenant_adress): حقل نصي (width: 50%)
- **المدينة** (tenant_city): نوع حقل مخصص `taxonmy_level_selector`
  - Post Type: `alh_locations`
  - Page Type: 4 (مستوى المدينة)
  - Custom Type: 6
  - Allow null: true
  - UI enabled: true
  - Width: 22%
- **الرمز البريدي** (tenant_postal_code): حقل نصي (width: 15%)

#### 13.1.2 أنماط التطوير المكتشفة الجديدة
1. **Custom Field Types**: أنواع حقول مخصصة للأسماء (`first_name`, `last_name`)
2. **File Upload with MIME Restrictions**: رفع ملفات مع قيود النوع (PDF فقط)
3. **Frontend Admin Display Modes**: أوضاع عرض مختلفة (read_only, edit)
4. **Section Dividers**: استخدام message fields كفواصل أقسام
5. **Complex Layout System**: نظام تخطيط معقد بعروض مختلفة (13%, 15%, 22%, 50%, 100%)
6. **Location Integration**: تكامل عميق مع نظام المواقع الهرمي

#### 13.1.3 متطلبات التطوير في Filament
- **TenantEditResource**: مورد Filament لتعديل المستأجرين
  - دعم حقول الأسماء المخصصة
  - رفع ملفات الهوية مع قيود النوع
  - نظام العناوين الهرمي المتكامل
  - أوضاع العرض المختلفة للحقول

#### 13.1.4 التطبيق في Filament 4
```php
// TenantEditResource.php
Section::make('البيانات الشخصية')
    ->schema([
        TextInput::make('first_name')
            ->label('الاسم الاول')
            ->required()
            ->columnSpan(['md' => 6]),
            
        TextInput::make('last_name')
            ->label('الاسم الثاني')
            ->required()
            ->columnSpan(['md' => 6]),
            
        TextInput::make('username')
            ->label('اسم المستخدم')
            ->disabled()
            ->columnSpan(['md' => 6]),
            
        TextInput::make('email')
            ->label('الايميل')
            ->email()
            ->columnSpan(['md' => 12]),
            
        TextInput::make('password')
            ->label('الباسورد')
            ->password()
            ->columnSpan(['md' => 6]),
    ]),

Section::make('بيانات الاتصال')
    ->schema([
        TextInput::make('phone')
            ->label('رقم الهاتف 1')
            ->required()
            ->tel()
            ->columnSpan(['md' => 6]),
            
        TextInput::make('phone_2')
            ->label('رقم الهاتف 2')
            ->tel()
            ->columnSpan(['md' => 6]),
    ]),

Section::make('المستندات')
    ->schema([
        FileUpload::make('id_file')
            ->label('ملف الهوية')
            ->required()
            ->acceptedFileTypes(['application/pdf'])
            ->directory('tenant-documents')
            ->visibility('private')
            ->columnSpan(['md' => 12]),
    ]),

Section::make('العنوان')
    ->schema([
        TextInput::make('address')
            ->label('رقم المبني , اسم الشارع , الحي')
            ->columnSpan(['md' => 6]),
            
        Select::make('city_id')
            ->label('المدينة')
            ->relationship('city', 'name')
            ->searchable()
            ->preload()
            ->columnSpan(['md' => 4]),
            
        TextInput::make('postal_code')
            ->label('الرمز البريدي')
            ->columnSpan(['md' => 2]),
    ]),
```

#### 13.1.5 الاختبارات المطلوبة
- **Unit Tests**:
  - `TenantEditResourceTest::test_personal_data_validation()`
  - `TenantEditResourceTest::test_file_upload_restrictions()`
  - `TenantEditResourceTest::test_address_hierarchy_integration()`
  - `TenantEditResourceTest::test_phone_validation()`
  - `TenantEditResourceTest::test_readonly_field_behavior()`
- **Feature Tests**:
  - `TenantEditWorkflowTest::test_complete_tenant_update()`
  - `TenantEditWorkflowTest::test_document_upload_workflow()`
- **Integration Tests**:
  - `TenantLocationTest::test_city_selection_integration()`
  - `TenantDocumentTest::test_pdf_storage_security()`

#### 13.1.6 مكونات Filament المطلوبة
- **TenantDocumentUpload.php**: مكون رفع مستندات الهوية
- **LocationHierarchySelector.php**: مكون اختيار المواقع الهرمي المحسن
- **TenantContactValidator.php**: مكون التحقق من بيانات الاتصال
- **SecureFileStorage.php**: نظام تخزين آمن للمستندات

#### 13.1.7 ملاحظات أمنية
- **حماية المستندات**: ملفات الهوية يجب تخزينها بشكل آمن مع تشفير
- **التحقق من الهوية**: التأكد من صحة ملفات PDF ومنع رفع ملفات ضارة
- **خصوصية البيانات**: حقول كلمات المرور والمستندات تحتاج حماية إضافية
- **صلاحيات الوصول**: تحديد من يمكنه الوصول لمستندات المستأجرين

## 14. فلتر البحث عن العقارات (Property Search Filter)
### 14.1 تحليل ملف group_6344194007cfd.json - property filter
#### 13.1.1 تحليل الحقول الموجودة
تم تحليل ملف فلتر البحث عن العقارات والذي يحتوي على 5 حقول رئيسية لنظام البحث والتصفية:

- **الاسم** (searchstring): حقل نصي للبحث النصي
  - Type: `text`
  - استخدام: البحث في أسماء العقارات والوصف
  - حقل اختياري للبحث الحر في النصوص
- **المنطقة** (state): اختيار المنطقة من النظام الهرمي
  - Type: `taxonmy_level_selector`
  - Post Type: `alh_locations`
  - Page Type: 3 (مستوى المنطقة)
  - Custom Type: 6
  - Width: 25% (في تخطيط الشبكة)
- **المدينة** (city): اختيار المدينة من النظام الهرمي
  - Type: `taxonmy_level_selector`
  - Post Type: `alh_locations`
  - Page Type: 4 (مستوى المدينة)
  - Custom Type: 6
  - Width: 25%
- **المركز** (city_center): اختيار المركز من النظام الهرمي
  - Type: `taxonmy_level_selector`
  - Post Type: `alh_locations`
  - Page Type: 5 (مستوى المركز)
  - Custom Type: 6
  - Width: 25%
- **الحي** (area): اختيار الحي من النظام الهرمي
  - Type: `taxonmy_level_selector`
  - Post Type: `alh_locations`
  - Page Type: 8 (مستوى الحي)
  - Custom Type: 6
  - Width: 25%

#### 13.1.2 أنماط التطوير المكتشفة
1. **Advanced Search Form**: نموذج بحث متقدم مع تصفية متعددة المعايير
2. **Hierarchical Location Filtering**: تصفية المواقع الهرمية المتدرجة (منطقة → مدينة → مركز → حي)
3. **Grid Layout System**: نظام تخطيط شبكي مع عرض 25% لكل حقل موقع
4. **Conditional Logic**: Logic for cascading location selection
5. **Frontend Integration**: تصميم للاستخدام في الواجهة الأمامية (frontend_admin_display_mode: edit)

#### 13.1.3 متطلبات التطوير في Filament
- **PropertyFilterResource**: مورد Filament للبحث والتصفية
  - نموذج للبحث المتقدم
  - دعم تصفية المواقع الهرمية
  - تكامل مع PropertyResource table
- **CascadingLocationFilter**: مكون Filament مخصص للتصفية الهرمية
  - دعم التتابع: منطقة → مدينة → مركز → حي
  - تحديث ديناميكي للخيارات المتاحة
  - واجهة مستخدم محسنة للاختيار المتتالي
- **SearchService**: خدمة البحث المتقدم
  - دمج البحث النصي مع تصفية المواقع
  - استعلامات محسنة للأداء
  - دعم البحث الضبابي والذكي

#### 13.1.4 التطبيق في Filament 4
```php
// في PropertyResource جدول table method
public static function table(Table $table): Table
{
    return $table
        ->columns([...])
        ->filters([
            Filter::make('searchstring')
                ->form([
                    TextInput::make('searchstring')
                        ->label('البحث في الاسم')
                        ->placeholder('ابحث في أسماء العقارات...')
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when($data['searchstring'], 
                        fn (Builder $query, $search): Builder => 
                            $query->where('name', 'like', "%{$search}%")
                                  ->orWhere('description', 'like', "%{$search}%")
                    );
                }),
            
            CascadingLocationFilter::make('location_hierarchy')
                ->form([
                    Select::make('state')
                        ->label('المنطقة')
                        ->relationship('locations', 'name')
                        ->where('level', 3)
                        ->reactive()
                        ->afterStateUpdated(fn (callable $set) => $set('city', null)),
                    
                    Select::make('city')
                        ->label('المدينة')
                        ->relationship('locations', 'name')
                        ->where('level', 4)
                        ->when(fn (callable $get) => $get('state'))
                        ->reactive(),
                    
                    Select::make('city_center')
                        ->label('المركز')
                        ->relationship('locations', 'name')
                        ->where('level', 5),
                    
                    Select::make('area')
                        ->label('الحي')
                        ->relationship('locations', 'name')
                        ->where('level', 8)
                ])
                ->columns(4)
        ]);
}
```

#### 13.1.5 مكونات Filament المطلوبة
- **CascadingLocationFilter.php**: مكون تصفية المواقع المتتالية
- **PropertySearchService.php**: خدمة البحث المتقدم في العقارات
- **LocationFilterWidget.php**: widget للتصفية السريعة
- **SearchFormComponent.php**: مكون نموذج البحث المتقدم

#### 13.1.6 الاختبارات المطلوبة
- **Unit Tests**:
  - `PropertyFilterTest::test_text_search_functionality()`
  - `LocationFilterTest::test_cascading_location_filter()`
  - `SearchServiceTest::test_advanced_search_query()`
- **Integration Tests**:
  - `PropertyResourceTest::test_filter_integration()`
  - `CascadingFilterTest::test_location_hierarchy_filtering()`
- **Feature Tests**:
  - `PropertySearchTest::test_combined_search_filters()`
  - `LocationFilterTest::test_filter_persistence()`

#### 13.1.7 UI/UX Requirements
- **Responsive Design**: تصميم متجاوب للفلاتر على الأجهزة المختلفة
- **Real-time Search**: بحث فوري أثناء الكتابة
- **Filter Persistence**: حفظ معايير البحث في الجلسة
- **Clear Filters**: زر مسح جميع الفلاتر
- **Search Results Count**: عرض عدد النتائج المطابقة

## 14. فلتر العقود (Contract Filter)
### 14.1 تحليل ملف group_6345351e7e1cb.json - property contract filter
#### 14.1.1 تحليل الحقول الموجودة
تم تحليل ملف فلتر العقود والذي يحتوي على 4 حقول أساسية لنظام تصفية العقود:

- **اسم العقد** (contract_name): حقل نصي للبحث
  - Type: `text`
  - Width: 50%
  - استخدام: البحث في أسماء العقود
  - حقل اختياري للبحث الحر في النصوص

- **اسم المالك** (contract_owner): اختيار المالك من قائمة المستخدمين
  - Type: `user`
  - Role Filter: limited to "owner" role
  - Allow null: Yes
  - Return format: "id"
  - Single selection (multiple: 0)
  - Width: 50%

- **العقار** (contract_property): اختيار العقار من قائمة العقارات
  - Type: `post_object`
  - Post Type Filter: limited to "alh_property"
  - Allow null: Yes
  - Return format: "id"
  - Single selection with UI enabled
  - Width: 50%

- **السعر** (contract_price): حقل رقمي للسعر
  - Type: `number`
  - Width: 50%
  - No min/max validation
  - No step defined

#### 14.1.2 أنماط التطوير المكتشفة
1. **User Role-Based Selection**: اختيار المستخدمين مع تصفية حسب الدور
2. **Post Object Relationships**: ربط العقود بالعقارات
3. **Contract Search Filter**: نموذج بحث مخصص للعقود
4. **Grid Layout System**: نظام تخطيط شبكي مع عرض 50% لكل حقل
5. **Multi-Field Contract Filtering**: تصفية متعددة المعايير للعقود

#### 14.1.3 متطلبات التطوير في Filament
- **ContractFilterResource**: مورد Filament للبحث والتصفية في العقود
  - نموذج للبحث المتقدم في العقود
  - دعم تصفية حسب المالك والعقار والسعر
  - تكامل مع ContractResource table
- **UserRoleSelector**: مكون Filament لاختيار المستخدمين حسب الدور
  - دعم تصفية المستخدمين حسب Role
  - واجهة مستخدم محسنة للاختيار
  - تكامل مع نظام Spatie Permissions
- **PropertySelector**: مكون اختيار العقارات
  - ربط العقود بالعقارات
  - البحث في العقارات
  - عرض تفاصيل مبسطة للعقار
- **ContractSearchService**: خدمة البحث المتقدم في العقود
  - دمج البحث النصي مع تصفية المالك والعقار
  - استعلامات محسنة للأداء
  - دعم البحث المركب

#### 14.1.4 التطبيق في Filament 4
```php
// في ContractResource جدول table method
public static function table(Table $table): Table
{
    return $table
        ->columns([...])
        ->filters([
            Filter::make('contract_search')
                ->form([
                    TextInput::make('contract_name')
                        ->label('اسم العقد')
                        ->placeholder('ابحث في أسماء العقود...')
                        ->columnSpan(2),
                    
                    Select::make('contract_owner')
                        ->label('اسم المالك')
                        ->relationship('owner', 'name')
                        ->searchable()
                        ->getOptionLabelFromRecordUsing(fn ($record) => 
                            $record->name . ' (' . $record->email . ')')
                        ->columnSpan(2),
                    
                    Select::make('contract_property')
                        ->label('العقار')
                        ->relationship('property', 'name')
                        ->searchable()
                        ->getOptionLabelFromRecordUsing(fn ($record) => 
                            $record->name . ' - ' . $record->location->name)
                        ->columnSpan(2),
                    
                    TextInput::make('contract_price')
                        ->label('السعر')
                        ->numeric()
                        ->placeholder('أدخل السعر...')
                        ->columnSpan(2),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['contract_name'], 
                            fn (Builder $query, $name): Builder => 
                                $query->where('name', 'like', "%{$name}%"))
                        ->when($data['contract_owner'],
                            fn (Builder $query, $owner): Builder =>
                                $query->where('owner_id', $owner))
                        ->when($data['contract_property'],
                            fn (Builder $query, $property): Builder =>
                                $query->where('property_id', $property))
                        ->when($data['contract_price'],
                            fn (Builder $query, $price): Builder =>
                                $query->where('price', '>=', $price));
                })
                ->columns(2)
        ]);
}
```

#### 14.1.5 مكونات Filament المطلوبة
- **UserRoleSelector.php**: مكون اختيار المستخدمين حسب الدور
- **PropertySelectorField.php**: مكون اختيار العقارات مع البحث
- **ContractSearchService.php**: خدمة البحث المتقدم في العقود
- **ContractFilterWidget.php**: widget للتصفية السريعة للعقود

#### 14.1.6 الاختبارات المطلوبة
- **Unit Tests**:
  - `ContractFilterTest::test_name_search_functionality()`
  - `UserRoleSelectorTest::test_owner_role_filtering()`
  - `PropertySelectorTest::test_property_relationship()`
  - `ContractSearchServiceTest::test_multi_criteria_search()`
- **Integration Tests**:
  - `ContractResourceTest::test_filter_integration()`
  - `UserRoleFilterTest::test_role_based_user_selection()`
- **Feature Tests**:
  - `ContractSearchTest::test_combined_search_filters()`
  - `ContractFilterTest::test_filter_persistence()`

#### 14.1.7 UI/UX Requirements
- **Role-Based User Display**: عرض المستخدمين مع الأدوار والمعلومات التعريفية
- **Property Preview**: معاينة سريعة للعقار عند الاختيار
- **Price Range Filter**: تصفية نطاق السعر (من - إلى)
- **Advanced Search Form**: نموذج بحث متقدم قابل للطي
- **Filter Shortcuts**: اختصارات سريعة للفلاتر الشائعة

### 14.2 تحليل ملف group_6347d472b488b.json - collection payment filter
#### 14.2.1 تحليل الحقول الموجودة
- **searchstring**: اسم البيان (optional, text) - حقل بحث نصي للبيانات المالية
- **unit_contract**: عقد الوحدة (optional, post_object: unit_contract, width: 50%) - علاقة مع عقود الوحدات
- **unit_contract_price**: السعر (optional, number, width: 50%) - سعر عقد الوحدة
- **unit_payment_status**: حالة البيان (optional, select, width: 50%) - حالة دفعة التحصيل
  - Options: collected, worth_collecting, delayed, overdue
- **payment_date**: تاريخ البيان (optional, date_picker, width: 50%) - تاريخ الدفعة/البيان

#### 14.2.2 تصميم Filament Filter/Search Schema
- **Text Search Filter**: للبحث النصي في البيانات المالية
- **Contract Relationship Filter**: تصفية حسب عقود الوحدات المحددة
- **Price Range Filter**: تصفية نطاق السعر (من - إلى)
- **Status Multi-Select Filter**: تصفية متعددة لحالات الدفع
- **Date Range Filter**: تصفية نطاق التاريخ (من - إلى)

#### 14.2.3 مكونات Filament المطلوبة
- **CollectionPaymentFilterWidget.php**: Widget للتصفية السريعة للدفعات
- **PaymentStatusFilter.php**: مكون تصفية حالة الدفع
- **UnitContractSelector.php**: مكون اختيار عقود الوحدات
- **DateRangeFilter.php**: مكون تصفية النطاق الزمني
- **PriceRangeFilter.php**: مكون تصفية النطاق السعري

#### 14.2.4 كود Filament 4 المطلوب تنفيذه

## 15. نموذج تعديل المالك (Owner Edit Form)
### 15.1 تحليل ملف group_635e3a32c2356.json - تعديل مالك
#### 15.1.1 تحليل الحقول الموجودة من النظام القديم
تم تحليل ملف تعديل المالك والذي يحتوي على 12 حقل رئيسي + 1 رسالة قسم:

**حقول البيانات الشخصية (Custom Field Types):**
- **الاسم الاول** (first_name): نوع حقل مخصص `first_name` (required, width: 50%)
- **الاسم الثاني** (last_name): نوع حقل مخصص `last_name` (required, width: 50%)
- **اسم المستخدم** (username): نوع حقل مخصص `username` (read_only, frontend_only, width: 50%)
- **الايميل** (user_email): حقل بريد إلكتروني (required, edit mode, width: 50%)
- **الباسورد** (user_password): نوع حقل مخصص `password` (edit mode, width: 50%)

**حقول الاتصال:**
- **رقم الهاتف 1** (owner_phone): حقل نصي (required, edit mode, width: 50%)
- **رقم الهاتف 2** (owner_phone_2): حقل نصي (اختياري, edit mode, width: 50%)

**حقول المستندات:**
- **ملف الهوية** (owner_id_file): حقل ملف (اختياري, PDF only, return: array)
  - MIME Types: "pdf"
  - Return Format: "array"
  - Library: "all"
  - Width: 50%

**حقول العنوان المعقدة:**
- **العنوان** (message): رسالة قسم كفاصل (width: 13%)
- **رقم المبني , اسم الشارع , الحي** (owner_adress): حقل نصي مفصل (required, width: 40%)
- **المدينة** (owner_city): نوع حقل مخصص `taxonmy_level_selector`
  - Post Type: `alh_locations`
  - Page Type: 4 (مستوى المدينة)
  - Custom Type: 6
  - Allow null: true
  - UI enabled: true
  - Width: 22%
- **الرمز البريدي** (owner_postal_code): حقل نصي (اختياري, width: 15%)
- **الرقم الاضافي** (number): حقل نصي إضافي (اختياري, width: 50%)

#### 15.1.2 أنماط التطوير المكتشفة الجديدة
1. **Custom ACF Field Types**: أنواع حقول مخصصة متخصصة
   - `first_name` / `last_name` types (not standard text)
   - `username` type with read-only display
   - `password` type for secure input
2. **Frontend Admin Display Modes**: أوضاع عرض متخصصة
   - `read_only`: للحقول المحمية
   - `edit`: للحقول القابلة للتعديل
   - `hidden`: للحقول المخفية
3. **Frontend-Only Fields**: حقول خاصة بالواجهة الأمامية (`only_front: 1`)
4. **Complex Layout System**: نظام تخطيط معقد ومتنوع
   - عروض متنوعة: 13%, 15%, 22%, 40%, 50%
   - تخصيص العرض حسب نوع المحتوى
5. **Widget Integration**: تكامل مع النظام كاملاً (`widget` location rule)
6. **File Upload Security**: قيود أمنية متقدمة للملفات
   - قيود MIME type صارمة (PDF only)
   - Return format محدد (array)

#### 15.1.3 متطلبات التطوير في Filament 4
- **OwnerEditResource**: مورد Filament لتعديل الملاك
  - دعم حقول الأسماء المخصصة مع التحقق
  - رفع ملفات الهوية مع الأمان المطلوب
  - نظام العناوين الهرمي المتكامل
  - أوضاع العرض المختلفة للحقول (read-only/editable)
  - تكامل مع user management system
- **CustomFieldComponents**: مكونات Filament مخصصة
  - FirstNameField.php: حقل الاسم الأول مع validation خاص
  - LastNameField.php: حقل الاسم الثاني مع validation خاص
  - UsernameDisplayField.php: حقل اسم المستخدم للعرض فقط
  - SecurePasswordField.php: حقل كلمة المرور الآمن
- **SecureDocumentUpload**: نظام رفع المستندات الآمن
  - تشفير الملفات
  - التحقق من صحة PDF
  - منع الملفات الضارة

#### 15.1.4 التطبيق في Filament 4
```php
// OwnerEditResource.php
Section::make('البيانات الشخصية')
    ->description('تعديل البيانات الأساسية للمالك')
    ->schema([
        TextInput::make('first_name')
            ->label('الاسم الاول')
            ->required()
            ->string()
            ->minLength(2)
            ->maxLength(50)
            ->regex('/^[\p{Arabic}\s]+$/u')
            ->columnSpan(['md' => 6]),
            
        TextInput::make('last_name')
            ->label('الاسم الثاني')
            ->required()
            ->string()
            ->minLength(2)
            ->maxLength(50)
            ->regex('/^[\p{Arabic}\s]+$/u')
            ->columnSpan(['md' => 6]),
            
        TextInput::make('username')
            ->label('اسم المستخدم')
            ->disabled()
            ->dehydrated(false)
            ->helperText('لا يمكن تعديل اسم المستخدم')
            ->columnSpan(['md' => 6]),
            
        TextInput::make('email')
            ->label('الايميل')
            ->required()
            ->email()
            ->unique(ignoreRecord: true)
            ->columnSpan(['md' => 6]),
            
        TextInput::make('password')
            ->label('الباسورد')
            ->password()
            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
            ->dehydrated(fn ($state) => filled($state))
            ->helperText('اتركه فارغاً لعدم التغيير')
            ->columnSpan(['md' => 6]),
    ]),

Section::make('بيانات الاتصال')
    ->description('أرقام الهاتف ووسائل التواصل')
    ->schema([
        TextInput::make('owner_phone')
            ->label('رقم الهاتف 1')
            ->required()
            ->tel()
            ->regex('/^[0-9\+\-\s\(\)]+$/')
            ->minLength(10)
            ->maxLength(15)
            ->columnSpan(['md' => 6]),
            
        TextInput::make('owner_phone_2')
            ->label('رقم الهاتف 2')
            ->tel()
            ->regex('/^[0-9\+\-\s\(\)]+$/')
            ->minLength(10)
            ->maxLength(15)
            ->columnSpan(['md' => 6]),
    ]),

Section::make('المستندات الرسمية')
    ->description('ملفات الهوية والمستندات القانونية')
    ->schema([
        FileUpload::make('owner_id_file')
            ->label('ملف الهوية')
            ->acceptedFileTypes(['application/pdf'])
            ->directory('owner-documents')
            ->visibility('private')
            ->maxSize(5120) // 5MB
            ->helperText('يُقبل ملفات PDF فقط، الحد الأقصى 5 ميجابايت')
            ->columnSpan(['md' => 12]),
    ]),

Section::make('العنوان التفصيلي')
    ->description('معلومات العنوان والموقع الجغرافي')
    ->schema([
        TextInput::make('owner_adress')
            ->label('رقم المبني , اسم الشارع , الحي')
            ->required()
            ->string()
            ->minLength(10)
            ->maxLength(200)
            ->placeholder('مثال: رقم 123، شارع الملك فهد، حي النزهة')
            ->columnSpan(['md' => 8]),
            
        Select::make('owner_city_id')
            ->label('المدينة')
            ->relationship('city', 'name')
            ->searchable()
            ->preload()
            ->createOptionForm([
                TextInput::make('name')
                    ->label('اسم المدينة')
                    ->required(),
                Select::make('parent_id')
                    ->label('المنطقة')
                    ->relationship('parent', 'name')
                    ->required(),
            ])
            ->columnSpan(['md' => 4]),
            
        TextInput::make('owner_postal_code')
            ->label('الرمز البريدي')
            ->numeric()
            ->length(5)
            ->placeholder('12345')
            ->columnSpan(['md' => 3]),
            
        TextInput::make('number')
            ->label('الرقم الاضافي')
            ->string()
            ->maxLength(50)
            ->placeholder('رقم إضافي أو معلومات تكميلية')
            ->columnSpan(['md' => 9]),
    ]),
```

#### 15.1.5 الاختبارات المطلوبة
- **Unit Tests**:
  - `OwnerEditResourceTest::test_personal_data_validation()`
  - `OwnerEditResourceTest::test_arabic_name_validation()`
  - `OwnerEditResourceTest::test_phone_number_validation()`
  - `OwnerEditResourceTest::test_email_uniqueness_check()`
  - `OwnerEditResourceTest::test_password_hashing()`
  - `OwnerEditResourceTest::test_username_readonly_behavior()`
  - `OwnerEditResourceTest::test_pdf_upload_restrictions()`
  - `OwnerEditResourceTest::test_address_validation()`
  - `OwnerEditResourceTest::test_postal_code_format()`
- **Feature Tests**:
  - `OwnerEditWorkflowTest::test_complete_owner_update()`
  - `OwnerEditWorkflowTest::test_document_upload_workflow()`
  - `OwnerEditWorkflowTest::test_password_update_workflow()`
- **Integration Tests**:
  - `OwnerLocationTest::test_city_selection_integration()`
  - `OwnerDocumentTest::test_pdf_storage_security()`
  - `OwnerValidationTest::test_arabic_text_support()`

#### 15.1.6 مكونات Filament المطلوبة
- **ArabicNameField.php**: مكون حقل الاسم العربي مع validation خاص
- **SecureUsernameDisplay.php**: مكون عرض اسم المستخدم للقراءة فقط
- **OwnerDocumentUpload.php**: مكون رفع مستندات المالك الآمن
- **PhoneNumberValidator.php**: مكون التحقق من أرقام الهاتف
- **AddressComponentGroup.php**: مجموعة مكونات العنوان المتكاملة
- **CityDynamicSelector.php**: مكون اختيار المدينة مع إنشاء ديناميكي

#### 15.1.7 ملاحظات أمنية وتقنية
- **حماية كلمات المرور**: استخدام Hash::make() وعدم dehydration إلا عند التغيير
- **حماية المستندات**: تخزين آمن مع تشفير وvalidation صارم للـ PDF
- **التحقق من الهوية**: فحص ملفات PDF ومنع رفع ملفات ضارة
- **خصوصية البيانات**: حماية بيانات المالك الحساسة
- **دعم اللغة العربية**: regex patterns خاصة للنصوص العربية
- **صلاحيات الوصول**: تحديد من يمكنه تعديل بيانات الملاك
- **تدقيق العمليات**: تسجيل جميع عمليات التعديل للمراجعة

#### 15.1.8 تكامل مع النظام الحالي
- **User Model Extension**: إضافة الحقول الجديدة لجدول users
- **Role Integration**: تكامل مع نظام الأدوار (Spatie Permissions)
- **Notification System**: إشعار المالك عند تعديل بياناته
- **Audit Trail**: تسجيل تاريخ التعديلات للمراجعة
- **Location Hierarchy**: تكامل مع نظام المواقع الهرمي الموجود
```php
// CollectionPaymentResource filters
public static function getTableFilters(): array
{
    return [
        Tables\Filters\TextFilter::make('searchstring')
            ->label('البحث في البيانات'),
        
        Tables\Filters\SelectFilter::make('unit_contract_id')
            ->label('عقد الوحدة')
            ->relationship('unitContract', 'title')
            ->searchable(),
            
        Tables\Filters\RangeFilter::make('unit_contract_price')
            ->label('نطاق السعر')
            ->from('price_from')
            ->to('price_to'),
            
        Tables\Filters\SelectFilter::make('unit_payment_status')
            ->label('حالة البيان')
            ->options([
                'collected' => 'تم التحصيل',
                'worth_collecting' => 'تستحق التحصيل',
                'delayed' => 'المؤجلة',
                'overdue' => 'تجاوزة المدة',
            ])
            ->multiple(),
            
        Tables\Filters\DateRangeFilter::make('payment_date')
            ->label('تاريخ البيان')
            ->displayFormat('d/m/Y'),
    ];
}
```

#### 14.2.5 الاختبارات المطلوبة
- **Unit Tests**:
  - `CollectionPaymentFilterTest::test_text_search_functionality()`
  - `PaymentStatusFilterTest::test_status_filtering()`
  - `PriceRangeFilterTest::test_price_range_validation()`
  - `DateRangeFilterTest::test_date_filtering()`
- **Integration Tests**:
  - `CollectionPaymentResourceTest::test_filter_integration()`
  - `UnitContractFilterTest::test_contract_relationship_filtering()`
- **Feature Tests**:
  - `CollectionPaymentSearchTest::test_combined_filters()`
  - `PaymentFilterPersistenceTest::test_filter_state_persistence()`

#### 14.2.6 خصائص النموذج المطلوبة
- **Status Constants**: تعريف ثوابت لحالات الدفع
- **Payment Date Scoping**: Query scopes للتصفية الزمنية
- **Unit Contract Relationship**: علاقة مع نموذج UnitContract
- **Search Scope**: نطاق البحث النصي في الحقول المرتبطة

## 15. نظام إدارة الصيانة
### 15.1 تحليل ملف group_6347ec28c836d.json - فلتر الصيانة

#### 15.1.1 تحليل الحقول الموجودة من النظام القديم
تم تحليل ملف فلتر الصيانة الذي يحتوي على 6 حقول لتصفية عمليات الصيانة:

**الحقول الأساسية:**
- **بحث الصيانة** (searchstring): بحث نصي في عمليات الصيانة (text input)
- **نوع الصيانة** (maintenance_type): اختيار نوع الصيانة (select dropdown)
  - خيارات: "عملية عامة" (general_maintenance), "عملية خاصة" (special_maintenance)
- **العقار** (maintenance_property): اختيار العقار (post_object → alh_property)
  - **شرطي**: يظهر فقط عند اختيار "عملية عامة"
- **الوحدة** (maintenance_unit): اختيار الوحدة (post_object → alh_unit)
  - **شرطي**: يظهر فقط عند اختيار "عملية خاصة"
- **اجمالي التكلفة** (total_maintenance_cost): قيمة رقمية للتكلفة (number input)
- **التاريخ** (maintenance_date): تاريخ الصيانة (date_picker، Y-m-d)

#### 15.1.2 مواصفات Filament 4 المطلوبة للصيانة

##### 15.1.2.1 هيكل البيانات المطلوب
```php
// جدول الصيانة
Schema::create('maintenance_requests', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->enum('type', ['general_maintenance', 'special_maintenance']);
    $table->foreignId('property_id')->nullable()->constrained();
    $table->foreignId('unit_id')->nullable()->constrained();
    $table->decimal('total_cost', 10, 2)->nullable();
    $table->date('maintenance_date')->nullable();
    $table->text('description')->nullable();
    $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled']);
    $table->timestamps();
});
```

##### 15.1.2.2 نموذج Laravel المطلوب
- **النموذج**: `MaintenanceRequest.php`
- **العلاقات**: 
  - `belongsTo(Property::class)`
  - `belongsTo(Unit::class)`
- **Scopes**: 
  - `scopeByType($query, $type)`
  - `scopeByDateRange($query, $from, $to)`
  - `scopeByProperty($query, $propertyId)`
  - `scopeByUnit($query, $unitId)`
  - `scopeByCostRange($query, $min, $max)`

##### 15.1.2.3 Filament Resource للصيانة
- **الكلاس**: `MaintenanceRequestResource.php`
- **فلتر الصيانة المتقدم**:
  - `TextInput` للبحث النصي (searchstring)
  - `Select` لنوع الصيانة مع تفعيل UI
  - `Select` للعقار مع conditional logic
  - `Select` للوحدة مع conditional logic
  - `TextInput` رقمي للتكلفة مع validation
  - `DatePicker` للتاريخ مع format عربي

##### 15.1.2.4 مكونات Filament المخصصة
```php
// في MaintenanceRequestResource.php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('title')->label('عنوان الصيانة'),
            TextColumn::make('type')->label('نوع الصيانة')
                ->formatStateUsing(fn ($state) => match($state) {
                    'general_maintenance' => 'عملية عامة',
                    'special_maintenance' => 'عملية خاصة',
                }),
            TextColumn::make('property.name')->label('العقار'),
            TextColumn::make('unit.name')->label('الوحدة'),
            TextColumn::make('total_cost')->label('اجمالي التكلفة')
                ->money('SAR'),
            TextColumn::make('maintenance_date')->label('التاريخ')
                ->date('d/m/Y'),
        ])
        ->filters([
            Filter::make('maintenance_filter')
                ->form([
                    TextInput::make('searchstring')
                        ->label('بحث الصيانة')
                        ->placeholder('بحث بالاسم في عمليات الصيانة'),
                    
                    Select::make('maintenance_type')
                        ->label('نوع الصيانة')
                        ->options([
                            'general_maintenance' => 'عملية عامة',
                            'special_maintenance' => 'عملية خاصة',
                        ])
                        ->live()
                        ->columnSpan(1),
                    
                    Select::make('maintenance_property')
                        ->label('العقار')
                        ->relationship('property', 'name')
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get) => $get('maintenance_type') === 'general_maintenance')
                        ->columnSpan(1),
                    
                    Select::make('maintenance_unit')
                        ->label('الوحدة')
                        ->relationship('unit', 'name')
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get) => $get('maintenance_type') === 'special_maintenance')
                        ->columnSpan(1),
                    
                    TextInput::make('total_maintenance_cost')
                        ->label('اجمالي التكلفة')
                        ->numeric()
                        ->prefix('ر.س')
                        ->columnSpan(1),
                    
                    DatePicker::make('maintenance_date')
                        ->label('التاريخ')
                        ->displayFormat('d/m/Y')
                        ->columnSpan(1),
                ])
                ->columns(2)
        ]);
}
```

##### 15.1.2.5 خدمة الصيانة
- **الكلاس**: `MaintenanceService.php`
- **الدوال**:
  - `createMaintenanceRequest($data)`
  - `assignToProperty($maintenanceId, $propertyId)`
  - `assignToUnit($maintenanceId, $unitId)`
  - `updateMaintenanceStatus($maintenanceId, $status)`
  - `calculateMaintenanceCost($maintenanceId)`
  - `getMaintenanceHistory($propertyId = null, $unitId = null)`

#### 15.1.3 الاختبارات المطلوبة
- **Unit Tests**:
  - `MaintenanceRequestModelTest::test_maintenance_type_validation()`
  - `MaintenanceRequestModelTest::test_property_unit_conditional_logic()`
  - `MaintenanceRequestModelTest::test_cost_calculation()`
  - `MaintenanceServiceTest::test_property_assignment()`
  - `MaintenanceServiceTest::test_unit_assignment()`

- **Feature Tests**:
  - `MaintenanceRequestResourceTest::test_filter_form_conditional_logic()`
  - `MaintenanceRequestResourceTest::test_property_unit_filtering()`
  - `MaintenanceRequestResourceTest::test_date_cost_filtering()`

- **Integration Tests**:
  - `MaintenanceFilterIntegrationTest::test_acf_to_filament_field_mapping()`
  - `MaintenanceFilterIntegrationTest::test_conditional_logic_behavior()`

#### 15.1.4 خصائص النموذج المطلوبة
- **Type Constants**: تعريف ثوابت لأنواع الصيانة
- **Conditional Validation**: قواعد التحقق الشرطية للعقار/الوحدة
- **Cost Formatting**: تنسيق العملة السعودية
- **Date Localization**: تنسيق التاريخ العربي
- **Search Scope**: نطاق البحث النصي في حقول الصيانة

## 16. فلتر المستخدمين (User Filter)
### 16.1 تحليل ملف group_63d8bfbb43ae9.json - User filter
#### 16.1.1 تحليل الحقول الموجودة
تم تحليل ملف فلتر المستخدمين والذي يحتوي على 3 حقول أساسية لنظام تصفية المستخدمين:

- **الاسم** (searchstring): حقل نصي للبحث
  - Type: `text`
  - Width: 33%
  - استخدام: البحث في أسماء المستخدمين
  - حقل اختياري للبحث الحر في النصوص

- **الوحدة** (user_unit): اختيار الوحدة من قائمة الوحدات
  - Type: `post_object`
  - Post Type: `alh_unit`
  - Width: 33%
  - Return Format: `id`
  - UI Enabled: true
  - Allow Null: true (اختياري)

- **العقار** (user_property): اختيار العقار من قائمة العقارات
  - Type: `post_object`
  - Post Type: `alh_property`
  - Width: 33%
  - Return Format: `id`
  - UI Enabled: true
  - Allow Null: true (اختياري)

#### 16.1.2 تطبيق Filament 4 Table Filters
```php
// في UserResource table filters
->filters([
    TextFilter::make('searchstring')
        ->label('الاسم')
        ->placeholder('البحث بالاسم')
        ->searchable(),
        
    SelectFilter::make('user_unit')
        ->label('الوحدة')
        ->relationship('unit', 'name')
        ->searchable()
        ->preload()
        ->multiple(false),
        
    SelectFilter::make('user_property')
        ->label('العقار')
        ->relationship('property', 'name')
        ->searchable()
        ->preload()
        ->multiple(false),
])
```

#### 16.1.3 مكونات Filament المطلوبة
- **UserFilterForm.php**: نموذج فلتر المستخدمين
- **RelationshipFilterComponent.php**: مكون فلتر العلاقات
- **UserSearchService.php**: خدمة البحث في المستخدمين
- **FilterPersistenceService.php**: خدمة حفظ معايير التصفية

#### 16.1.4 الاختبارات المطلوبة
- **Unit Tests**:
  - `UserFilterTest::test_name_search_functionality()`
  - `UserFilterTest::test_unit_relationship_filter()`
  - `UserFilterTest::test_property_relationship_filter()`
- **Integration Tests**:
  - `UserResourceTest::test_filter_integration()`
  - `RelationshipFilterTest::test_multiple_filters_combination()`
- **Feature Tests**:
  - `UserSearchTest::test_combined_filter_results()`
  - `FilterPersistenceTest::test_filter_state_saving()`

#### 16.1.5 UI/UX Requirements
- **3-Column Layout**: تخطيط ثلاثة أعمدة متساوية (33% لكل حقل)
- **Searchable Dropdowns**: قوائم منسدلة قابلة للبحث
- **Live Filtering**: تصفية فورية أثناء الاختيار
- **Clear All Filters**: زر مسح جميع الفلاتر
- **Filter Indicators**: مؤشرات الفلاتر النشطة

#### 16.1.6 التحديات التقنية
- **Post Object Relationships**: تحويل post_object إلى Eloquent relationships
- **Performance Optimization**: تحسين الأداء مع preload للعلاقات
- **Arabic Interface**: واجهة عربية مع RTL support
- **Filter State Management**: إدارة حالة الفلاتر عبر الجلسات

## 17. خلاصة التحليل الشامل
تم تحليل 38 ملف ACF JSON بنجاح واكتشاف 29 نوع حقل مختلف يتطلب تطوير 26 مكون Filament 4 مخصص. بالإضافة إلى ذلك، تم تحليل النظام الحالي للواجهة واكتشاف 16 مكون UI إضافي مطلوب للترحيل الكامل.

**الجديد من group_639ee4281793f.json:**
- حقل المواقع الهرمية المخصص `taxonmy_level_selector`
- نظام مراكز المدن مع اختيار المدينة التابعة
- مكون Filament Select للمواقع الهرمية
- خدمة HierarchicalLocationService للمواقع المتدرجة

**الجديد من group_63d8bfbb43ae9.json - User Filter:**
- نظام تصفية المستخدمين مع 3 معايير بحث
- فلتر نصي للبحث بالاسم
- فلتر اختيار الوحدة (relationship to alh_unit)
- فلتر اختيار العقار (relationship to alh_property)
- تخطيط 3 أعمدة متساوية (33% لكل حقل)

**ملخص المكونات المطلوبة:**
- 27 مكون ACF إلى Filament 4 (تم إضافة User Filter)
- 16 مكون UI/Dashboard  
- 8 widgets للوحة التحكم
- 5 أنواع تقارير
- 12 نوع chart ورسم بياني
- 1 نظام بحث وتصفية متقدم
- 6 مكونات تصفية مالية (تم إضافة User Filter)
- 1 نظام إدارة الصيانة مع فلترة شرطية

**التحديات الرئيسية:**
1. المواقع الهرمية والحقول الشرطية المعقدة (مع حقل taxonmy_level_selector مخصص)
2. النظام المالي مع دفعات التحصيل والتوريد
3. نظام الصيانة مع الحقول الشرطية (عقار/وحدة)
4. Role-based interfaces متعددة
5. Arabic RTL support الكامل
6. Chart integration والتصورات المرئية
7. Export/Print functionality
8. Real-time notifications
9. Mobile responsiveness
10. Advanced Filament 4 conditional logic implementation

## 18. القوالب والتخطيط العام - Footer Template
### 18.1 تحليل footer.php من النظام القديم
#### 18.1.1 هيكل Footer الأساسي
- **النموذج القديم**: footer.php - قالب footer بسيط مع WordPress hooks
- **العناصر الأساسية**:
  - Footer sidebar area: `get_sidebar('footer')`
  - Site info section: معلومات الموقع والحقوق
  - WordPress footer hooks: `wp_footer()` لتحميل Scripts
  - Theme credits: استخدام action hook `twentyfourteen_credits`

#### 18.1.2 متطلبات الترحيل إلى Filament 4
- **Filament Layout**: استخدام نظام Layout الخاص بـ Filament 4
- **Footer Components**: إنشاء مكونات footer مخصصة
- **RTL Support**: دعم كامل للغة العربية والاتجاه من اليمين لليسار
- **Responsive Design**: تصميم متجاوب للهواتف والأجهزة اللوحية

#### 18.1.3 مكونات Filament Footer المطلوبة
```php
// في AppPanelProvider.php
->footer(fn () => new FooterComponent())

// FooterComponent.php
class FooterComponent extends Component
{
    protected static string $view = 'filament.components.footer';
    
    public function render(): View
    {
        return view('filament.components.footer', [
            'companyInfo' => config('app.company'),
            'version' => config('app.version'),
            'currentYear' => date('Y')
        ]);
    }
}
```

#### 18.1.4 Footer Template Structure
- **Company Information**: معلومات الشركة والحقوق
- **System Version**: إصدار النظام ومعلومات التحديث
- **Quick Links**: روابط سريعة للدعم والمساعدة
- **Language Selector**: مفتاح تغيير اللغة (عربي/إنجليزي)
- **Theme Toggle**: مفتاح تغيير الثيم (فاتح/داكن) - اختياري

#### 18.1.5 CSS/Styling Requirements
- **Arabic Typography**: خطوط عربية مناسبة (Cairo, Tajawal)
- **RTL Layout**: تخطيط من اليمين لليسار
- **Color Scheme**: نظام ألوان متناسق مع باقي النظام
- **Responsive Breakpoints**: نقاط توقف متجاوبة للأجهزة المختلفة

#### 18.1.6 الاختبارات المطلوبة
- **Unit Tests**:
  - `FooterComponentTest::test_footer_renders_correctly()`
  - `FooterComponentTest::test_company_info_display()`
  - `FooterComponentTest::test_rtl_layout_support()`
- **Feature Tests**:
  - `FooterLayoutTest::test_footer_appears_on_all_pages()`
  - `ResponsiveTest::test_footer_mobile_layout()`
- **Browser Tests**:
  - `FooterUITest::test_footer_visual_elements()`
  - `RTLTest::test_footer_rtl_rendering()`

#### 18.1.7 التحديات التقنية
- **Filament Layout Integration**: دمج Footer مع نظام Filament Layout
- **WordPress Hooks Migration**: ترحيل WordPress hooks إلى Filament events
- **Widget Area Migration**: ترحيل Footer sidebar إلى Filament widgets
- **Theme Credits**: تحديد طريقة عرض معلومات النظام والتطوير

### 18.2 خطة تطوير Footer Component
#### 18.2.1 إنشاء المكونات الأساسية
1. **FooterComponent.php**: المكون الرئيسي للـ footer
2. **footer.blade.php**: قالب العرض الخاص بالـ footer
3. **FooterServiceProvider.php**: مقدم خدمة Footer إذا لزم الأمر
4. **FooterConfiguration.php**: إعدادات Footer قابلة للتخصيص

#### 18.2.2 تكامل مع نظام الإعدادات
- **Footer Settings**: إضافة إعدادات Footer في لوحة الإعدادات العامة
- **Customizable Content**: محتوى قابل للتخصيص من لوحة التحكم
- **Multi-language Support**: دعم متعدد اللغات للمحتوى
- **Dynamic Updates**: تحديث ديناميكي بدون إعادة تشغيل الخادم

#### 18.2.3 الأولوية في التطوير
- **أولوية منخفضة**: Footer بسيط ولا يحتوي على وظائف معقدة
- **بعد إكمال**: Core system, Authentication, وMain dashboard
- **مرحلة التشطيب**: ضمن مرحلة UI/UX polishing والتشطيبات النهائية

---

## 19. Single Post Display System - قالب عرض التفاصيل
### 19.1 تحليل single.php من النظام القديم
#### 19.1.1 هيكل Single Template الأساسي
- **النموذج القديم**: `single.php` - قالب عرض تفاصيل المنشورات
- **التدفق الرئيسي**:
  1. `acf_form_head()` - تحضير ACF form headers
  2. `get_header()` - تحميل header
  3. احتواء WordPress Loop للمحتوى
  4. تحميل `template-part/single/content.php` مع المعاملات
  5. `get_sidebar()` و `get_footer()` - الشريط الجانبي والفوتر

#### 19.1.2 ACF Form Integration
- **Dynamic Field Groups**: تحديد مجموعة الحقول تلقائياً حسب post_type
```php
$groups = acf_get_field_groups(['post_type' => get_post_type()]);
$args['group'] = $groups[0]['key']; // أول مجموعة حقول متاحة
```
- **Form Arguments**: تمرير المعاملات إلى template-part للتحكم في العرض
- **Post Type Detection**: فحص نوع المنشور تلقائياً لتطبيق الحقول المناسبة

#### 19.1.3 Content Template Logic (template-part/single/content.php)
- **Role-Based Display**: عرض محتوى مختلف حسب دور المستخدم
```php
if( current_user_can('administrator') || current_user_can('generalmanager') ) {
    // عرض أزرار الإدارة والتقارير
}
```
- **Dynamic Buttons**: أزرار ديناميكية للعمليات المختلفة
  - `Archive Button`: الرجوع لصفحة الأرشيف
  - `Edit Button`: تعديل المنشور
  - `Report Button`: عرض التقارير (للعقارات والوحدات فقط)

#### 19.1.4 Report View System
- **Conditional Report Display**: عرض تقارير مختلفة حسب نوع المنشور
```php
if( isset($_GET['view']) && $_GET['view'] === 'report' ) {
    if( 'alh_property' === get_post_type() || 'alh_unit' === get_post_type() ) {
        include( 'template-part/single/content-report.php' );
    }
    if( 'collection_payment' === get_post_type() ) {
        include( 'template-part/single/content-report-payment.php' );
    }
}
```
- **Report Types**:
  - **Property Reports**: تقارير العقارات (`content-report.php`)
  - **Payment Reports**: تقارير الدفعات (`content-report-payment.php`)

#### 19.1.5 Special Payment Handling
- **Supply Payment Logic**: معالجة خاصة لدفعات التوريد
```php
if( get_post_type(get_the_ID()) === 'supply_payment' ) {
    $contract_status = get_custom_table_field($table_name, 'supply_status', get_the_ID());
    if( $contract_status === 'collected' ) {
        $show_form = false;
        echo $invoice; // عرض الفاتورة بدلاً من النموذج
    }
}
```
- **Invoice Generation**: توليد فواتير تلقائية للدفعات المجمعة
- **Form Toggle**: إخفاء/إظهار النموذج حسب حالة الدفعة

#### 19.1.6 Print Functionality
- **Print Button**: زر طباعة لطباعة محتوى الصفحة
```javascript
onclick='printDiv("content-print")'
```
- **Print Area**: تحديد منطقة محددة للطباعة (`#content-print`)
- **RTL Print Support**: دعم الطباعة باللغة العربية

### 19.2 متطلبات الترحيل إلى Filament 4
#### 19.2.1 Filament View/Show Pages
- **Resource ViewRecord Pages**: صفحات عرض التفاصيل في Filament Resources
```php
// PropertyResource::getPages()
'view' => Pages\ViewProperty::route('/properties/{record}'),

// PropertyViewPage.php
class ViewProperty extends ViewRecord
{
    protected static string $resource = PropertyResource::class;
    
    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
            Action::make('report')
                ->label('تقرير')
                ->url(fn ($record) => route('filament.property.report', $record))
                ->visible(fn () => auth()->user()->can('view-reports')),
        ];
    }
}
```

#### 19.2.2 Dynamic Form Loading
- **Filament Form Schema**: تحديد schema الحقول ديناميكياً
```php
protected function form(Form $form): Form
{
    return $form->schema(
        $this->getResourceFormSchema()
    );
}

private function getResourceFormSchema(): array
{
    // تحديد الحقول حسب نوع الموارد ودور المستخدم
    return match($this->getResource()::getModel()) {
        Property::class => PropertyResource::getFormSchema(),
        Unit::class => UnitResource::getFormSchema(),
        // ...
    };
}
```

#### 19.2.3 Report Integration
- **Custom Report Pages**: صفحات تقارير مخصصة
```php
// PropertyReportPage.php
class PropertyReportPage extends Page
{
    protected static string $resource = PropertyResource::class;
    protected static string $view = 'filament.property.report';
    
    public Property $record;
    
    protected function getViewData(): array
    {
        return [
            'property' => $this->record,
            'financialSummary' => $this->getFinancialSummary(),
            'maintenanceHistory' => $this->getMaintenanceHistory(),
        ];
    }
}
```

#### 19.2.4 Role-Based Actions
- **Permission-Based Actions**: أزرار وعمليات حسب الصلاحيات
```php
protected function getActions(): array
{
    return [
        Action::make('edit')
            ->visible(fn () => auth()->user()->can('update', $this->record)),
        Action::make('report')
            ->visible(fn () => auth()->user()->can('view-reports')),
        Action::make('print')
            ->action(fn () => $this->js('window.print()'))
    ];
}
```

#### 19.2.5 Invoice System Integration
- **Payment Status Handling**: معالجة حالات الدفع والفواتير
```php
protected function getViewData(): array
{
    $data = parent::getViewData();
    
    if ($this->record instanceof SupplyPayment) {
        if ($this->record->status === 'collected') {
            $data['invoice'] = $this->record->generateInvoice();
            $data['showForm'] = false;
        }
    }
    
    return $data;
}
```

### 19.3 المكونات المطلوبة للترحيل
#### 19.3.1 Base View Pages
1. **PropertyViewPage**: عرض تفاصيل العقار
2. **UnitViewPage**: عرض تفاصيل الوحدة  
3. **OwnerViewPage**: عرض تفاصيل المالك
4. **TenantViewPage**: عرض تفاصيل المستأجر
5. **ContractViewPage**: عرض تفاصيل العقد
6. **PaymentViewPage**: عرض تفاصيل الدفعة
7. **RepairViewPage**: عرض تفاصيل الصيانة

#### 19.3.2 Report Pages
1. **PropertyReportPage**: تقرير العقار الشامل
2. **UnitReportPage**: تقرير الوحدة
3. **OwnerReportPage**: تقرير المالك
4. **TenantReportPage**: تقرير المستأجر
5. **PaymentReportPage**: تقرير الدفعة
6. **FinancialReportPage**: التقرير المالي العام

#### 19.3.3 Specialized Components
1. **PrintableViewComponent**: مكون القابلية للطباعة
2. **InvoiceDisplayComponent**: مكون عرض الفواتير
3. **ReportActionComponent**: مكون أزرار التقارير
4. **StatusBadgeComponent**: مكون عرض الحالات
5. **DocumentViewerComponent**: مكون عرض المستندات

### 19.4 الاختبارات المطلوبة
#### 19.4.1 Unit Tests
- **ViewPageTest**: اختبار صفحات العرض الأساسية
```php
test('property view page displays correctly', function () {
    $property = Property::factory()->create();
    
    actingAs(User::factory()->admin()->create())
        ->get(PropertyResource::getUrl('view', ['record' => $property]))
        ->assertSuccessful()
        ->assertSee($property->title);
});
```

#### 19.4.2 Feature Tests  
- **ReportAccessTest**: اختبار صلاحيات الوصول للتقارير
- **PrintFunctionalityTest**: اختبار وظائف الطباعة
- **InvoiceGenerationTest**: اختبار توليد الفواتير
- **RoleBasedDisplayTest**: اختبار العرض حسب الأدوار

#### 19.4.3 Browser Tests
- **ViewPageUITest**: اختبار واجهة صفحات العرض
- **PrintUITest**: اختبار واجهة الطباعة
- **ReportUITest**: اختبار واجهة التقارير

### 19.5 التحديات التقنية
#### 19.5.1 ACF Form Migration
- **Dynamic Field Loading**: تحويل نظام تحميل الحقول الديناميكي
- **Form State Management**: إدارة حالة النماذج المعقدة
- **Validation Rules**: نقل قواعد التحقق من ACF إلى Filament

#### 19.5.2 Report System Integration
- **PDF Generation**: نظام توليد ملفات PDF للتقارير
- **Chart Integration**: دمج مكتبات الرسوم البيانية
- **Data Aggregation**: تجميع البيانات للتقارير المعقدة

#### 19.5.3 Permission System
- **Granular Permissions**: صلاحيات دقيقة لكل عنصر
- **Dynamic UI**: واجهة متغيرة حسب الصلاحيات
- **Audit Trail**: تتبع العمليات والتغييرات

### 19.6 الأولوية في التطوير
- **أولوية عالية**: صفحات العرض الأساسية ضرورية للوظائف الأساسية
- **المرحلة**: بعد إكمال النماذج والموارد الأساسية
- **التبعيات**: Authentication, Permissions, Base Resources
- **التكامل**: مع نظام التقارير والإشعارات

---

## 20. نظام السجلات والتدقيق (Audit Logs System)

### 20.1 تحليل النظام القديم
#### 20.1.1 ملف single-log.php الحالي
من تحليل الملف `D:\Server\crm\wp-content\themes\alhiaa-system\single-log.php`:

```php
// الملف الحالي بسيط ويحتوي على:
$slug = pods_v( 'last', 'url' );
$pod_name = pods_v( 0, 'url');
$pods = pods( 'log', $slug );

// عرض اسم السجل
echo $pods->display('name');

// نموذج ACF للدفعات
acfe_form('apartments-payment');
```

#### 20.1.2 المتطلبات المستخرجة
- **عرض تفاصيل السجل**: عرض اسم وتفاصيل كل سجل
- **تكامل مع النماذج**: ربط السجلات بنماذج الدفعات  
- **نظام URL**: آلية URL لعرض السجلات الفردية
- **واجهة بسيطة**: عرض نظيف ومبسط للمعلومات

### 20.2 تصميم نظام التدقيق في Laravel
#### 20.2.1 جدول audit_logs
```php
// Migration: create_audit_logs_table.php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->string('user_type')->nullable(); // User model class
    $table->unsignedBigInteger('user_id')->nullable();
    $table->string('event'); // created, updated, deleted, viewed, etc.
    $table->string('auditable_type'); // Model class being audited
    $table->unsignedBigInteger('auditable_id');
    $table->json('old_values')->nullable(); // Original values
    $table->json('new_values')->nullable(); // New values
    $table->string('url')->nullable(); // Request URL
    $table->string('ip_address')->nullable();
    $table->string('user_agent')->nullable();
    $table->json('tags')->nullable(); // Custom tags for categorization
    $table->timestamps();
    
    $table->index(['auditable_type', 'auditable_id']);
    $table->index(['user_type', 'user_id']);
    $table->index('event');
    $table->index('created_at');
});
```

#### 20.2.2 نموذج AuditLog
```php
// app/Models/AuditLog.php
class AuditLog extends Model
{
    protected $fillable = [
        'user_type', 'user_id', 'event', 'auditable_type', 
        'auditable_id', 'old_values', 'new_values', 
        'url', 'ip_address', 'user_agent', 'tags'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'tags' => 'array',
    ];

    // العلاقات
    public function user()
    {
        return $this->morphTo();
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    // Scope للبحث
    public function scopeForModel($query, $model)
    {
        return $query->where('auditable_type', get_class($model))
                    ->where('auditable_id', $model->id);
    }

    public function scopeForUser($query, $user)
    {
        return $query->where('user_type', get_class($user))
                    ->where('user_id', $user->id);
    }

    public function scopeForEvent($query, $event)
    {
        return $query->where('event', $event);
    }
}
```

### 20.3 Trait للنماذج القابلة للتدقيق
#### 20.3.1 Auditable Trait
```php
// app/Traits/Auditable.php
trait Auditable
{
    protected static function bootAuditable()
    {
        static::created(function ($model) {
            $model->auditEvent('created');
        });

        static::updated(function ($model) {
            $model->auditEvent('updated');
        });

        static::deleted(function ($model) {
            $model->auditEvent('deleted');
        });
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function auditEvent($event, $tags = [])
    {
        AuditLog::create([
            'user_type' => auth()->check() ? get_class(auth()->user()) : null,
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => get_class($this),
            'auditable_id' => $this->id,
            'old_values' => $this->getOriginal(),
            'new_values' => $this->getAttributes(),
            'url' => request()->fullUrl(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'tags' => $tags,
        ]);
    }

    public function getAuditName()
    {
        return class_basename($this) . ' #' . $this->id;
    }
}
```

### 20.4 Filament Resource للسجلات
#### 20.4.1 AuditLogResource
```php
// app/Filament/Resources/AuditLogResource.php
class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'سجلات التدقيق';
    protected static ?string $modelLabel = 'سجل التدقيق';
    protected static ?string $pluralModelLabel = 'سجلات التدقيق';
    protected static ?string $navigationGroup = 'إدارة النظام';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('رقم السجل')
                    ->sortable(),
                
                TextColumn::make('event')
                    ->label('النشاط')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning', 
                        'deleted' => 'danger',
                        'viewed' => 'info',
                        default => 'gray',
                    }),
                
                TextColumn::make('auditable_type')
                    ->label('نوع العنصر')
                    ->formatStateUsing(fn (string $state): string => 
                        class_basename($state)
                    ),
                
                TextColumn::make('auditable_id')
                    ->label('رقم العنصر')
                    ->sortable(),
                
                TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->default('نظام')
                    ->sortable(),
                
                TextColumn::make('ip_address')
                    ->label('عنوان IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('created_at')
                    ->label('تاريخ العملية')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label('نوع النشاط')
                    ->options([
                        'created' => 'إنشاء',
                        'updated' => 'تحديث',
                        'deleted' => 'حذف',
                        'viewed' => 'عرض',
                    ]),
                
                SelectFilter::make('auditable_type')
                    ->label('نوع العنصر')
                    ->options([
                        'App\\Models\\Property' => 'عقار',
                        'App\\Models\\Unit' => 'وحدة',
                        'App\\Models\\Owner' => 'مالك',
                        'App\\Models\\Tenant' => 'مستأجر',
                        'App\\Models\\PropertyContract' => 'عقد ملاك',
                        'App\\Models\\UnitContract' => 'عقد مستأجر',
                        'App\\Models\\CollectionPayment' => 'دفعة تحصيل',
                        'App\\Models\\SupplyPayment' => 'دفعة توريد',
                    ]),
                
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('من تاريخ'),
                        DatePicker::make('created_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('معلومات السجل')
                    ->schema([
                        TextInput::make('event')
                            ->label('نوع النشاط')
                            ->disabled(),
                        
                        TextInput::make('auditable_type')
                            ->label('نوع العنصر')
                            ->disabled(),
                        
                        TextInput::make('auditable_id')
                            ->label('رقم العنصر')
                            ->disabled(),
                    ])->columns(3),
                
                Section::make('معلومات المستخدم')
                    ->schema([
                        TextInput::make('user.name')
                            ->label('المستخدم')
                            ->disabled(),
                        
                        TextInput::make('ip_address')
                            ->label('عنوان IP')
                            ->disabled(),
                        
                        Textarea::make('user_agent')
                            ->label('متصفح المستخدم')
                            ->disabled()
                            ->rows(2),
                    ])->columns(2),
                
                Section::make('التغييرات')
                    ->schema([
                        Textarea::make('old_values')
                            ->label('القيم السابقة')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                            ->rows(6),
                        
                        Textarea::make('new_values')
                            ->label('القيم الجديدة')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                            ->rows(6),
                    ])->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // لا يمكن إنشاء سجلات تدقيق يدوياً
    }

    public static function canEdit(Model $record): bool
    {
        return false; // لا يمكن تعديل سجلات التدقيق
    }

    public static function canDelete(Model $record): bool
    {
        return false; // لا يمكن حذف سجلات التدقيق
    }
}
```

### 20.5 تطبيق التدقيق على النماذج
#### 20.5.1 إضافة Trait للنماذج الرئيسية
```php
// تطبيق على جميع النماذج المهمة:

// app/Models/Property.php
class Property extends Model
{
    use Auditable;
    // باقي الكود...
}

// app/Models/Unit.php
class Unit extends Model
{
    use Auditable;
    // باقي الكود...
}

// app/Models/Owner.php, Tenant.php, PropertyContract.php, etc.
// نفس الطريقة لجميع النماذج
```

### 20.6 واجهة عرض السجل الفردي
#### 20.6.1 ViewAuditLog Page
```php
// app/Filament/Resources/AuditLogResource/Pages/ViewAuditLog.php
class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_related')
                ->label('عرض العنصر المرتبط')
                ->icon('heroicon-o-eye')
                ->url(fn (AuditLog $record): string => 
                    $this->getRelatedModelUrl($record)
                )
                ->visible(fn (AuditLog $record): bool => 
                    $this->hasRelatedModelUrl($record)
                ),
        ];
    }

    private function getRelatedModelUrl(AuditLog $record): ?string
    {
        $modelClass = $record->auditable_type;
        
        // تحديد Resource المناسب لكل نموذج
        $resourceMap = [
            'App\\Models\\Property' => PropertyResource::class,
            'App\\Models\\Unit' => UnitResource::class,
            'App\\Models\\Owner' => OwnerResource::class,
            'App\\Models\\Tenant' => TenantResource::class,
            // إضافة باقي النماذج...
        ];

        if (isset($resourceMap[$modelClass])) {
            $resourceClass = $resourceMap[$modelClass];
            return $resourceClass::getUrl('view', ['record' => $record->auditable_id]);
        }

        return null;
    }

    private function hasRelatedModelUrl(AuditLog $record): bool
    {
        return $this->getRelatedModelUrl($record) !== null;
    }
}
```

### 20.7 مكونات إضافية للتدقيق
#### 20.7.1 عرض السجلات في صفحات النماذج
```php
// إضافة RelationManager للسجلات في كل Resource
class AuditLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'auditLogs';
    protected static ?string $title = 'سجلات التدقيق';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event')
                    ->label('النشاط')
                    ->badge(),
                
                TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->default('نظام'),
                
                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime('Y-m-d H:i:s'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->paginated([10, 25, 50]);
    }
}

## 21. نظام الإشعارات والتنبيهات (Notification System)
### 21.1 تحليل النظام القديم وتحديد المشاكل
#### 21.1.1 تحليل ملف class-alhiaa-notifications.php
**ملف النظام القديم**: `D:\Server\crm\wp-content\themes\alhiaa-system\classes\class-alhiaa-notifications.php`
**عدد الأسطر**: 109 سطر
**الحالة**: نظام أساسي غير مكتمل مع مشاكل فنية خطيرة

#### 21.1.2 المشاكل المكتشفة في النظام القديم
1. **مشاكل هيكل قاعدة البيانات**:
   - اسم الجدول خاطئ: `unit_collection` بدلاً من `notifications`
   - خطأ في Primary Key: `PRIMARY KEY id (id)` بدلاً من `PRIMARY KEY (ID)`
   - تضارب في أسماء الأعمدة: `ID` vs `id`
   - نوع بيانات خاطئ: `user_id` كـ `varchar(255)` بدلاً من `bigint`

2. **وظائف محدودة**:
   - يدعم نوع واحد فقط: إشعارات تحصيل الدفعات
   - مربوط بحالة واحدة: `'worth_collecting'`
   - لا يوجد نظام تصنيف للإشعارات
   - لا يوجد قوالب للرسائل

3. **ميزات مفقودة أساسية**:
   - لا يوجد تفضيلات إشعارات للمستخدمين
   - لا يوجد طرق توصيل متعددة (بريد، SMS، داخل النظام)
   - لا يوجد إشعارات فورية (WebSocket/Pusher)
   - لا يوجد قوالب إشعارات قابلة للتخصيص
   - لا يوجد سجل تاريخي للإشعارات
   - لا يوجد إشعارات مجمعة
   - لا يوجد جدولة للإشعارات

4. **مشاكل أمنية وفنية**:
   - استعلامات SQL مباشرة بدون ORM
   - لا يوجد معالجة أخطاء مناسبة
   - ثغرات أمنية: SQL مباشر بدون sanitization
   - لا يوجد إدارة للعلاقات

### 21.2 تصميم نظام الإشعارات الجديد في Laravel
#### 21.2.1 هيكل قاعدة البيانات الجديدة
```php
// Migration للإشعارات الأساسية
Schema::create('notifications', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('type');
    $table->morphs('notifiable');
    $table->text('data');
    $table->timestamp('read_at')->nullable();
    $table->timestamps();
});

// Migration لأنواع الإشعارات
Schema::create('notification_types', function (Blueprint $table) {
    $table->id();
    $table->string('name_ar');
    $table->string('name_en');
    $table->string('slug')->unique();
    $table->text('template_email');
    $table->text('template_sms')->nullable();
    $table->text('template_database');
    $table->json('default_settings');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// Migration لتفضيلات المستخدمين
Schema::create('user_notification_preferences', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('notification_type_id')->constrained()->onDelete('cascade');
    $table->boolean('email_enabled')->default(true);
    $table->boolean('sms_enabled')->default(false);
    $table->boolean('database_enabled')->default(true);
    $table->json('custom_settings')->nullable();
    $table->timestamps();
    
    $table->unique(['user_id', 'notification_type_id']);
});

// Migration لسجل الإشعارات
Schema::create('notification_logs', function (Blueprint $table) {
    $table->id();
    $table->uuid('notification_id');
    $table->foreignId('user_id')->constrained();
    $table->string('channel'); // email, sms, database
    $table->string('status'); // sent, failed, pending
    $table->text('response')->nullable();
    $table->timestamp('sent_at')->nullable();
    $table->timestamps();
});
```

#### 21.2.2 النماذج (Models) المطلوبة
```php
// app/Models/NotificationType.php
class NotificationType extends Model
{
    protected $fillable = [
        'name_ar', 'name_en', 'slug', 'template_email',
        'template_sms', 'template_database', 'default_settings', 'is_active'
    ];

    protected $casts = [
        'default_settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function userPreferences()
    {
        return $this->hasMany(UserNotificationPreference::class);
    }
}

// app/Models/UserNotificationPreference.php
class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id', 'notification_type_id', 'email_enabled',
        'sms_enabled', 'database_enabled', 'custom_settings'
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'database_enabled' => 'boolean',
        'custom_settings' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notificationType()
    {
        return $this->belongsTo(NotificationType::class);
    }
}

// app/Models/NotificationLog.php
class NotificationLog extends Model
{
    protected $fillable = [
        'notification_id', 'user_id', 'channel', 'status', 'response', 'sent_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### 21.3 Laravel Notifications Implementation
#### 21.3.1 Notification Classes
```php
// app/Notifications/CollectionPaymentDueNotification.php
class CollectionPaymentDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CollectionPayment $payment,
        public array $customData = []
    ) {}

    public function via($notifiable): array
    {
        $preferences = $notifiable->getNotificationPreferences('collection_payment_due');
        
        $channels = [];
        if ($preferences->email_enabled) $channels[] = 'mail';
        if ($preferences->sms_enabled) $channels[] = 'sms';
        if ($preferences->database_enabled) $channels[] = 'database';
        
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تنبيه: دفعة تحصيل مستحقة')
            ->greeting('مرحباً ' . $notifiable->name)
            ->line('تذكير بدفعة تحصيل مستحقة')
            ->line('العقار: ' . $this->payment->unitContract->unit->property->name)
            ->line('الوحدة: ' . $this->payment->unitContract->unit->name)
            ->line('المبلغ: ' . number_format($this->payment->amount) . ' ريال')
            ->line('تاريخ الاستحقاق: ' . $this->payment->due_date->format('Y-m-d'))
            ->action('عرض التفاصيل', url('/admin/collection-payments/' . $this->payment->id))
            ->line('شكراً لك');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'collection_payment_due',
            'payment_id' => $this->payment->id,
            'unit_name' => $this->payment->unitContract->unit->name,
            'property_name' => $this->payment->unitContract->unit->property->name,
            'amount' => $this->payment->amount,
            'due_date' => $this->payment->due_date,
            'message' => 'دفعة تحصيل مستحقة بمبلغ ' . number_format($this->payment->amount) . ' ريال',
        ];
    }
}

// إشعارات إضافية للأنواع الأخرى:
// - SupplyPaymentDueNotification (استحقاق دفعة توريد)
// - ContractExpiryNotification (انتهاء صلاحية العقد)
// - MaintenanceRequestNotification (طلب صيانة)
// - PropertyStatusChangeNotification (تغيير حالة العقار)
// - NewTenantRegistrationNotification (تسجيل مستأجر جديد)
```

#### 21.3.2 Console Commands للإشعارات التلقائية
```php
// app/Console/Commands/SendCollectionPaymentNotifications.php
class SendCollectionPaymentNotifications extends Command
{
    protected $signature = 'notifications:send-collection-due {--days=1}';
    protected $description = 'Send notifications for due collection payments';

    public function handle(NotificationService $notificationService)
    {
        $days = $this->option('days');
        
        // البحث عن الدفعات المستحقة
        $duePayments = CollectionPayment::where('status', 'worth_collecting')
            ->whereDate('due_date', Carbon::now()->addDays($days))
            ->with(['unitContract.tenant', 'unitContract.unit.property'])
            ->get();

        $count = 0;
        foreach ($duePayments as $payment) {
            $tenant = $payment->unitContract->tenant;
            
            // إرسال إشعار للمستأجر
            $tenant->notify(new CollectionPaymentDueNotification($payment));
            
            // إشعار المدير أيضاً
            $admins = User::role('admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new CollectionPaymentDueNotification($payment, [
                    'notification_for' => 'admin',
                    'tenant_name' => $tenant->name
                ]));
            }
            
            $count++;
        }

        $this->info("تم إرسال إشعارات لـ {$count} دفعة مستحقة");
        
        // تسجيل في السجل
        \Log::info("Collection payment notifications sent", [
            'count' => $count,
            'days_ahead' => $days
        ]);

        return Command::SUCCESS;
    }
}
```

### 21.4 Filament Resources للإدارة
#### 21.4.1 NotificationTypeResource
```php
// app/Filament/Resources/NotificationTypeResource.php
class NotificationTypeResource extends Resource
{
    protected static ?string $model = NotificationType::class;
    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationLabel = 'أنواع الإشعارات';
    protected static ?string $navigationGroup = 'إعدادات النظام';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('البيانات الأساسية')
                ->schema([
                    TextInput::make('name_ar')
                        ->label('الاسم بالعربية')
                        ->required()
                        ->maxLength(255),
                    
                    TextInput::make('name_en')
                        ->label('الاسم بالإنجليزية')
                        ->required()
                        ->maxLength(255),
                    
                    TextInput::make('slug')
                        ->label('الرمز التعريفي')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                ]),
            
            Section::make('قوالب الرسائل')
                ->schema([
                    Textarea::make('template_email')
                        ->label('قالب البريد الإلكتروني')
                        ->required()
                        ->rows(6),
                    
                    Textarea::make('template_sms')
                        ->label('قالب الرسائل النصية')
                        ->rows(3),
                    
                    Textarea::make('template_database')
                        ->label('قالب الإشعار الداخلي')
                        ->required()
                        ->rows(4),
                ]),
            
            Section::make('الإعدادات')
                ->schema([
                    KeyValue::make('default_settings')
                        ->label('الإعدادات الافتراضية')
                        ->keyLabel('المفتاح')
                        ->valueLabel('القيمة'),
                    
                    Toggle::make('is_active')
                        ->label('نشط')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_ar')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('slug')
                    ->label('الرمز')
                    ->searchable()
                    ->badge(),
                
                BooleanColumn::make('is_active')
                    ->label('نشط')
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط'),
            ])
            ->recordActions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
```

#### 21.4.2 UserNotificationPreferenceResource
```php
// إدارة تفضيلات الإشعارات للمستخدمين
class UserNotificationPreferenceResource extends Resource
{
    protected static ?string $model = UserNotificationPreference::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'تفضيلات الإشعارات';
    protected static ?string $navigationGroup = 'إدارة المستخدمين';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('user_id')
                ->label('المستخدم')
                ->relationship('user', 'name')
                ->required()
                ->searchable(),
            
            Select::make('notification_type_id')
                ->label('نوع الإشعار')
                ->relationship('notificationType', 'name_ar')
                ->required(),
            
            Grid::make(3)
                ->schema([
                    Toggle::make('email_enabled')
                        ->label('البريد الإلكتروني')
                        ->default(true),
                    
                    Toggle::make('sms_enabled')
                        ->label('الرسائل النصية')
                        ->default(false),
                    
                    Toggle::make('database_enabled')
                        ->label('الإشعارات الداخلية')
                        ->default(true),
                ]),
            
            KeyValue::make('custom_settings')
                ->label('إعدادات مخصصة')
                ->keyLabel('المفتاح')
                ->valueLabel('القيمة'),
        ]);
    }
}
```

### 21.5 Notification Service للعمليات المتقدمة
```php
// app/Services/NotificationService.php
class NotificationService
{
    public function sendBulkNotification(
        Collection $users,
        string $notificationClass,
        array $data = []
    ): void {
        foreach ($users as $user) {
            $user->notify(new $notificationClass(...$data));
        }
    }

    public function getUserPreferences(User $user, string $notificationType): ?UserNotificationPreference
    {
        return $user->notificationPreferences()
            ->whereHas('notificationType', fn($q) => $q->where('slug', $notificationType))
            ->first();
    }

    public function scheduleNotification(
        User $user,
        string $notificationClass,
        Carbon $scheduledAt,
        array $data = []
    ): void {
        dispatch(new SendScheduledNotification($user, $notificationClass, $data))
            ->delay($scheduledAt);
    }

    public function getNotificationStats(): array
    {
        return [
            'total_sent' => NotificationLog::where('status', 'sent')->count(),
            'failed' => NotificationLog::where('status', 'failed')->count(),
            'pending' => NotificationLog::where('status', 'pending')->count(),
            'today_sent' => NotificationLog::where('status', 'sent')
                ->whereDate('sent_at', Carbon::today())->count(),
        ];
    }
}
```

### 21.6 Dashboard Widgets للمراقبة
#### 21.6.1 NotificationStatsWidget
```php
// app/Filament/Widgets/NotificationStatsWidget.php
class NotificationStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $service = app(NotificationService::class);
        $stats = $service->getNotificationStats();

        return [
            Stat::make('إجمالي المرسل', $stats['total_sent'])
                ->description('جميع الإشعارات المرسلة')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            
            Stat::make('اليوم', $stats['today_sent'])
                ->description('الإشعارات المرسلة اليوم')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
            
            Stat::make('الفاشلة', $stats['failed'])
                ->description('الإشعارات الفاشلة')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
            
            Stat::make('قيد الانتظار', $stats['pending'])
                ->description('في انتظار الإرسال')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
```

### 21.7 Queue Jobs والمعالجة في الخلفية
```php
// app/Jobs/SendScheduledNotification.php
class SendScheduledNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $notificationClass,
        public array $data = []
    ) {}

    public function handle(): void
    {
        try {
            $notification = new $this->notificationClass(...$this->data);
            $this->user->notify($notification);
            
            // تسجيل نجاح الإرسال
            NotificationLog::create([
                'user_id' => $this->user->id,
                'channel' => 'scheduled',
                'status' => 'sent',
                'sent_at' => now(),
                'response' => 'Successfully sent scheduled notification'
            ]);
            
        } catch (\Exception $e) {
            // تسجيل فشل الإرسال
            NotificationLog::create([
                'user_id' => $this->user->id,
                'channel' => 'scheduled',
                'status' => 'failed',
                'response' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
```

### 21.8 Laravel Scheduler Integration
```php
// في app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // إشعارات الدفعات المستحقة - يومياً الساعة 9 صباحاً
    $schedule->command('notifications:send-collection-due --days=1')
        ->dailyAt('09:00')
        ->withoutOverlapping()
        ->runInBackground();
    
    // إشعارات الدفعات المستحقة خلال 3 أيام - كل 3 أيام
    $schedule->command('notifications:send-collection-due --days=3')
        ->twiceDaily(9, 15)
        ->withoutOverlapping();
    
    // إشعارات انتهاء العقود - أسبوعياً
    $schedule->command('notifications:send-contract-expiry')
        ->weeklyOn(1, '10:00')
        ->withoutOverlapping();
    
    // تنظيف سجلات الإشعارات القديمة - شهرياً
    $schedule->command('notifications:cleanup-logs --days=90')
        ->monthlyOn(1, '02:00')
        ->withoutOverlapping();
}
```

### 21.9 Testing Strategy
#### 21.9.1 Unit Tests
```php
// tests/Unit/NotificationServiceTest.php
class NotificationServiceTest extends TestCase
{
    public function test_user_preferences_retrieval(): void
    {
        $user = User::factory()->create();
        $notificationType = NotificationType::factory()->create(['slug' => 'test_notification']);
        
        UserNotificationPreference::factory()->create([
            'user_id' => $user->id,
            'notification_type_id' => $notificationType->id,
            'email_enabled' => true,
            'sms_enabled' => false,
        ]);
        
        $service = new NotificationService();
        $preferences = $service->getUserPreferences($user, 'test_notification');
        
        $this->assertTrue($preferences->email_enabled);
        $this->assertFalse($preferences->sms_enabled);
    }

    public function test_notification_stats_calculation(): void
    {
        NotificationLog::factory()->count(5)->create(['status' => 'sent']);
        NotificationLog::factory()->count(2)->create(['status' => 'failed']);
        
        $service = new NotificationService();
        $stats = $service->getNotificationStats();
        
        $this->assertEquals(5, $stats['total_sent']);
        $this->assertEquals(2, $stats['failed']);
    }
}
```

#### 21.9.2 Feature Tests
```php
// tests/Feature/CollectionPaymentNotificationTest.php
class CollectionPaymentNotificationTest extends TestCase
{
    public function test_collection_payment_notification_sent(): void
    {
        Notification::fake();
        
        $tenant = User::factory()->tenant()->create();
        $payment = CollectionPayment::factory()->create([
            'due_date' => Carbon::tomorrow(),
            'status' => 'worth_collecting'
        ]);
        
        $this->artisan('notifications:send-collection-due --days=1')
            ->assertExitCode(0);
        
        Notification::assertSentTo(
            $tenant,
            CollectionPaymentDueNotification::class
        );
    }
}
```

### 21.10 Migration من النظام القديم
#### 21.10.1 Migration Command
```php
// app/Console/Commands/MigrateOldNotifications.php
class MigrateOldNotifications extends Command
{
    protected $signature = 'migrate:old-notifications';
    protected $description = 'Migrate notifications from old WordPress system';

    public function handle()
    {
        // إنشاء أنواع الإشعارات الأساسية
        $this->createDefaultNotificationTypes();
        
        // تحويل البيانات الموجودة (إن وجدت)
        $this->migrateExistingData();
        
        // إعداد التفضيلات الافتراضية للمستخدمين
        $this->setupDefaultUserPreferences();
        
        $this->info('تم ترحيل نظام الإشعارات بنجاح');
    }

    private function createDefaultNotificationTypes(): void
    {
        $types = [
            [
                'name_ar' => 'استحقاق دفعة تحصيل',
                'name_en' => 'Collection Payment Due',
                'slug' => 'collection_payment_due',
                'template_email' => 'تنبيه: دفعة تحصيل مستحقة بمبلغ {amount} ريال',
                'template_sms' => 'دفعة مستحقة: {amount} ريال - تاريخ الاستحقاق: {due_date}',
                'template_database' => 'دفعة تحصيل مستحقة للوحدة {unit_name} بمبلغ {amount} ريال',
                'default_settings' => ['remind_days' => [1, 3, 7]],
            ],
            [
                'name_ar' => 'استحقاق دفعة توريد',
                'name_en' => 'Supply Payment Due',
                'slug' => 'supply_payment_due',
                'template_email' => 'إشعار توريد: مبلغ {amount} ريال مستحق للعقار {property_name}',
                'template_sms' => 'توريد مستحق: {amount} ريال',
                'template_database' => 'دفعة توريد مستحقة بمبلغ {amount} ريال',
                'default_settings' => ['remind_days' => [1, 5]],
            ],
            // إضافة المزيد من الأنواع...
        ];

        foreach ($types as $type) {
            NotificationType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }

    private function setupDefaultUserPreferences(): void
    {
        $users = User::all();
        $notificationTypes = NotificationType::all();

        foreach ($users as $user) {
            foreach ($notificationTypes as $type) {
                UserNotificationPreference::updateOrCreate([
                    'user_id' => $user->id,
                    'notification_type_id' => $type->id,
                ], [
                    'email_enabled' => true,
                    'sms_enabled' => false,
                    'database_enabled' => true,
                ]);
            }
        }
    }
}
```

### 21.11 Pusher Integration للإشعارات الفورية
#### 21.11.1 إعداد Pusher
```php
// config/broadcasting.php - إعداد Pusher
'pusher' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY'),
    'secret' => env('PUSHER_APP_SECRET'),
    'app_id' => env('PUSHER_APP_ID'),
    'options' => [
        'cluster' => env('PUSHER_APP_CLUSTER'),
        'encrypted' => true,
    ],
],

// في .env
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=ap2
```

#### 21.11.2 Real-time Notification Broadcast
```php
// app/Notifications/RealTimeNotification.php
class RealTimeNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => $this->getTitle(),
            'message' => $this->getMessage(),
            'type' => $this->getType(),
            'data' => $this->getData(),
            'created_at' => now()->toISOString(),
        ]);
    }

    public function broadcastOn()
    {
        return new PrivateChannel('notifications.' . $this->notifiable->id);
    }
}
```

### 21.12 التحكم في الأمان والأداء
#### 21.12.1 Rate Limiting للإشعارات
```php
// app/Services/NotificationRateLimiter.php
class NotificationRateLimiter
{
    public function canSendNotification(User $user, string $type): bool
    {
        $key = "notification_limit:{$user->id}:{$type}";
        $limit = $this->getTypeLimit($type);
        
        return RateLimiter::attempt(
            $key,
            $limit,
            function () {
                // السماح بالإرسال
            },
            3600 // ساعة واحدة
        );
    }

    private function getTypeLimit(string $type): int
    {
        $limits = [
            'collection_payment_due' => 5, // 5 إشعارات في الساعة
            'supply_payment_due' => 3,
            'contract_expiry' => 2,
        ];

        return $limits[$type] ?? 1;
    }
}
```

#### 21.12.2 Notification Queue Optimization
```php
// config/queue.php - إعداد Queue خاص للإشعارات
'connections' => [
    'notifications' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'notifications',
        'retry_after' => 90,
        'block_for' => null,
    ],
],

// في app/Notifications/ - استخدام Queue مخصص
class CollectionPaymentDueNotification extends Notification implements ShouldQueue
{
    public $queue = 'notifications';
    public $tries = 3;
    public $timeout = 60;
}
```

### 21.13 Integration Testing مع النظام الكامل
#### 21.13.1 End-to-End Notification Flow Test
```php
// tests/Feature/NotificationWorkflowTest.php
class NotificationWorkflowTest extends TestCase
{
    public function test_complete_notification_workflow(): void
    {
        // إعداد البيانات
        $tenant = User::factory()->tenant()->create();
        $payment = CollectionPayment::factory()->create([
            'due_date' => Carbon::tomorrow(),
            'status' => 'worth_collecting'
        ]);
        
        // تشغيل الأمر
        $this->artisan('notifications:send-collection-due --days=1');
        
        // التحقق من الإشعارات في قاعدة البيانات
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $tenant->id,
            'type' => CollectionPaymentDueNotification::class,
        ]);
        
        // التحقق من سجل الإرسال
        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $tenant->id,
            'status' => 'sent',
        ]);
    }
}
```

### 21.14 Performance Monitoring والتحليلات
#### 21.14.1 Notification Analytics Service
```php
// app/Services/NotificationAnalyticsService.php
class NotificationAnalyticsService
{
    public function getDeliveryRates(): array
    {
        $total = NotificationLog::count();
        $sent = NotificationLog::where('status', 'sent')->count();
        $failed = NotificationLog::where('status', 'failed')->count();
        
        return [
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($sent / $total) * 100, 2) : 0,
            'failure_rate' => $total > 0 ? round(($failed / $total) * 100, 2) : 0,
        ];
    }

    public function getTypeStats(): Collection
    {
        return NotificationLog::select('channel')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy(['channel', 'status'])
            ->get()
            ->groupBy('channel');
    }

    public function getUserEngagement(): array
    {
        $readNotifications = DB::table('notifications')
            ->whereNotNull('read_at')
            ->count();
            
        $totalNotifications = DB::table('notifications')->count();
        
        return [
            'total_sent' => $totalNotifications,
            'total_read' => $readNotifications,
            'read_rate' => $totalNotifications > 0 ? 
                round(($readNotifications / $totalNotifications) * 100, 2) : 0,
        ];
    }
}
```

### 21.15 إعداد التشغيل والصيانة
#### 21.15.1 Health Checks للإشعارات
```php
// app/Console/Commands/NotificationHealthCheck.php
class NotificationHealthCheck extends Command
{
    protected $signature = 'notifications:health-check';
    protected $description = 'Check notification system health';

    public function handle()
    {
        $this->info('فحص صحة نظام الإشعارات...');
        
        // فحص الاتصال بـ Queue
        $this->checkQueueConnection();
        
        // فحص إعدادات البريد الإلكتروني
        $this->checkMailConfiguration();
        
        // فحص قاعدة البيانات
        $this->checkDatabaseTables();
        
        // فحص الإشعارات الفاشلة الأخيرة
        $this->checkFailedNotifications();
        
        $this->info('تم الانتهاء من فحص النظام');
    }

    private function checkQueueConnection(): void
    {
        try {
            Queue::size('notifications');
            $this->line('✅ اتصال Queue يعمل بشكل صحيح');
        } catch (\Exception $e) {
            $this->error('❌ مشكلة في اتصال Queue: ' . $e->getMessage());
        }
    }

    private function checkMailConfiguration(): void
    {
        $config = config('mail.default');
        if ($config) {
            $this->line("✅ إعدادات البريد: {$config}");
        } else {
            $this->error('❌ إعدادات البريد غير مكتملة');
        }
    }

    private function checkDatabaseTables(): void
    {
        $tables = ['notifications', 'notification_types', 'notification_logs'];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $this->line("✅ جدول {$table} موجود");
            } else {
                $this->error("❌ جدول {$table} غير موجود");
            }
        }
    }

    private function checkFailedNotifications(): void
    {
        $failedCount = NotificationLog::where('status', 'failed')
            ->where('created_at', '>', Carbon::now()->subDay())
            ->count();
            
        if ($failedCount > 0) {
            $this->warn("⚠️ {$failedCount} إشعار فاشل في آخر 24 ساعة");
        } else {
            $this->line('✅ لا توجد إشعارات فاشلة حديثة');
        }
    }
}
```

### 21.16 Advanced Features للمستقبل
#### 21.16.1 Multi-Language Template Support
```php
// إدعم للقوالب متعددة اللغات
class MultiLanguageNotificationTemplate
{
    public function getTemplate(string $type, string $locale = 'ar'): string
    {
        $templates = [
            'collection_payment_due' => [
                'ar' => 'تنبيه: دفعة تحصيل مستحقة بمبلغ {amount} ريال',
                'en' => 'Reminder: Collection payment of {amount} SAR is due',
            ],
        ];

        return $templates[$type][$locale] ?? $templates[$type]['ar'];
    }
}
```

#### 21.16.2 Smart Notification Scheduling
```php
// جدولة ذكية بناءً على سلوك المستخدم
class SmartNotificationScheduler
{
    public function getBestTimeToSend(User $user): Carbon
    {
        // تحليل أوقات قراءة الإشعارات السابقة
        $preferredHour = $this->getUserPreferredHour($user);
        
        return Carbon::now()
            ->setHour($preferredHour)
            ->setMinute(0)
            ->setSecond(0);
    }

    private function getUserPreferredHour(User $user): int
    {
        // تحليل بيانات قراءة الإشعارات لتحديد أفضل وقت
        $readTimes = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->whereNotNull('read_at')
            ->selectRaw('HOUR(read_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->first();

        return $readTimes->hour ?? 9; // افتراضي الساعة 9 صباحاً
    }
}
```

هذا النظام الشامل للإشعارات سيحل محل النظام البسيط والمحدود في WordPress ويوفر مرونة كاملة وأمان وأداء محسن مع إمكانات متقدمة للمراقبة والتحليل.
```

### 20.8 الاختبارات المطلوبة
#### 20.8.1 Unit Tests
```php
// tests/Unit/AuditLogTest.php
test('audit log is created when model is created', function () {
    $property = Property::factory()->create();
    
    expect(AuditLog::count())->toBe(1);
    
    $log = AuditLog::first();
    expect($log->event)->toBe('created');
    expect($log->auditable_type)->toBe(Property::class);
    expect($log->auditable_id)->toBe($property->id);
});

test('audit log captures old and new values on update', function () {
    $property = Property::factory()->create(['title' => 'Old Title']);
    
    $property->update(['title' => 'New Title']);
    
    $updateLog = AuditLog::where('event', 'updated')->first();
    expect($updateLog->old_values['title'])->toBe('Old Title');
    expect($updateLog->new_values['title'])->toBe('New Title');
});
```

#### 20.8.2 Feature Tests
```php
// tests/Feature/AuditLogResourceTest.php
test('admin can view audit logs', function () {
    $admin = User::factory()->admin()->create();
    $property = Property::factory()->create();
    
    actingAs($admin)
        ->get(AuditLogResource::getUrl('index'))
        ->assertSuccessful()
        ->assertSee('سجلات التدقيق');
});

test('audit log filters work correctly', function () {
    $admin = User::factory()->admin()->create();
    Property::factory()->create();
    Unit::factory()->create();
    
    actingAs($admin)
        ->get(AuditLogResource::getUrl('index') . '?tableFilters[auditable_type][value]=App\\Models\\Property')
        ->assertSuccessful();
});
```

### 20.9 التكامل مع النظام القديم
#### 20.9.1 تحويل بيانات السجلات القديمة
```php
// app/Console/Commands/ImportOldAuditLogs.php
class ImportOldAuditLogs extends Command
{
    protected $signature = 'import:old-audit-logs';
    protected $description = 'Import audit logs from old WordPress system';

    public function handle()
    {
        // الاتصال بقاعدة بيانات WordPress القديمة
        $oldLogs = DB::connection('old_wp')
            ->table('posts')
            ->where('post_type', 'log')
            ->get();

        foreach ($oldLogs as $oldLog) {
            AuditLog::create([
                'event' => 'legacy_import',
                'auditable_type' => 'Legacy\\Log',
                'auditable_id' => $oldLog->ID,
                'new_values' => [
                    'title' => $oldLog->post_title,
                    'content' => $oldLog->post_content,
                    'date' => $oldLog->post_date,
                ],
                'tags' => ['legacy', 'imported'],
                'created_at' => $oldLog->post_date,
            ]);
        }

        $this->info('تم استيراد ' . $oldLogs->count() . ' سجل من النظام القديم');
    }
}
```

### 20.10 الأولوية في التطوير
- **أولوية متوسطة**: نظام التدقيق مهم للأمان والمراقبة
- **المرحلة**: بعد إكمال النماذج الأساسية والصلاحيات  
- **التبعيات**: User Management, Permissions, Base Models
- **التكامل**: مع جميع النماذج والموارد في النظام

## 21. قوائم العرض والأرشيف (Archive Lists & Data Tables)
### 21.1 نظام قوائم العرض المتقدم
#### 21.1.1 تحليل archive.php من النظام القديم
- **الملف المُحلل**: `D:\Server\crm\wp-content\themes\alhiaa-system\archive.php`
- **الوظائف الرئيسية**:
  - عرض قوائم الكيانات (Properties, Units, Contracts, etc.)
  - دعم التصنيفات الهرمية (Taxonomies) 
  - نظام المستويات للمواقع (Level-based location hierarchy)
  - ربط مع نماذج الفلترة ACF
  - عناوين ديناميكية حسب نوع المحتوى
  - أزرار إجراءات سريعة

#### 21.1.2 مكونات نظام العرض
##### 21.1.2.1 DataTables متقدمة (من template-part/archive/content.php)
- **المكونات المطلوبة**:
  - فلتر ديناميكي متقدم مع ACF Forms
  - جداول بيانات مع إمكانيات البحث والتصفية
  - أزرار إجراءات (تعديل، حذف، عرض)
  - رسائل نجاح/خطأ للعمليات
  - تصدير البيانات (CSV, PDF, Excel)
  - عرض responsive للجوال

##### 21.1.2.2 نظام التصنيفات الهرمية
- **الوظائف المطلوبة**:
  - كشف مستوى التصنيف تلقائياً (Level 1-4)
  - عرض عنوان حسب نوع الصفحة (Taxonomy vs Archive)
  - ربط مع نماذج التحرير للتصنيفات
  - أزرار انتقال سريع بين التصنيفات

#### 21.1.3 Filament Tables Resources المطلوبة
##### 21.1.3.1 PropertyListResource
```php
// app/Filament/Resources/PropertyListResource.php
class PropertyListResource extends Resource
{
    protected static ?string $model = Property::class;
    protected static ?string $navigationLabel = 'قائمة العقارات';
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('عنوان العقار')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('property_type.name_ar')
                    ->label('نوع العقار')
                    ->sortable(),
                TextColumn::make('property_status.name_ar')
                    ->label('حالة العقار')
                    ->badge()
                    ->color(fn ($record) => $record->property_status->color),
                TextColumn::make('location.full_path')
                    ->label('الموقع')
                    ->limit(30),
                TextColumn::make('owner.name')
                    ->label('المالك')
                    ->searchable(),
                TextColumn::make('units_count')
                    ->label('عدد الوحدات')
                    ->counts('units'),
                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('property_type_id')
                    ->label('نوع العقار')
                    ->relationship('property_type', 'name_ar'),
                SelectFilter::make('property_status_id')
                    ->label('حالة العقار')
                    ->relationship('property_status', 'name_ar'),
                SelectFilter::make('location_id')
                    ->label('المنطقة')
                    ->relationship('location', 'name_ar'),
                Filter::make('date_range')
                    ->label('فترة زمنية')
                    ->form([
                        DatePicker::make('from')->label('من تاريخ'),
                        DatePicker::make('to')->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['to'], fn ($q) => $q->whereDate('created_at', '<=', $data['to']));
                    })
            ])
            ->actions([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف المحدد'),
                    ExportBulkAction::make()->label('تصدير المحدد'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
```

##### 21.1.3.2 مكون الفلترة المتقدم
```php
// app/Filament/Components/AdvancedPropertyFilter.php
class AdvancedPropertyFilter extends Component
{
    protected string $view = 'filament.components.advanced-property-filter';
    
    public array $filters = [
        'property_type' => null,
        'property_status' => null, 
        'location_level_1' => null,
        'location_level_2' => null,
        'location_level_3' => null,
        'owner_id' => null,
        'price_min' => null,
        'price_max' => null,
        'area_min' => null,
        'area_max' => null,
        'features' => [],
    ];
    
    public function applyFilters()
    {
        // منطق تطبيق الفلاتر
        $this->dispatch('filters-applied', $this->filters);
    }
    
    public function resetFilters()
    {
        $this->reset('filters');
        $this->dispatch('filters-reset');
    }
}
```

#### 21.1.4 دعم المواقع الهرمية في القوائم
##### 21.1.4.1 Location Level Detection
```php
// app/Services/LocationHierarchyService.php
class LocationHierarchyService
{
    public function detectLocationLevel($location): int
    {
        if (!$location->parent_id) return 1; // محافظة/منطقة
        
        $parent = $location->parent;
        if (!$parent->parent_id) return 2; // مدينة
        
        $grandParent = $parent->parent;
        if (!$grandParent->parent_id) return 3; // حي
        
        return 4; // منطقة فرعية
    }
    
    public function getLocationBreadcrumb($location): array
    {
        $breadcrumb = [];
        $current = $location;
        
        while ($current) {
            array_unshift($breadcrumb, $current);
            $current = $current->parent;
        }
        
        return $breadcrumb;
    }
    
    public function getLocationsByLevel(int $level, $parentId = null): Collection
    {
        return Location::where('level', $level)
            ->when($parentId, fn($q) => $q->where('parent_id', $parentId))
            ->orderBy('name_ar')
            ->get();
    }
}
```

##### 21.1.4.2 Filament Location Filter Component  
```php
// app/Filament/Components/HierarchicalLocationFilter.php
class HierarchicalLocationFilter extends Component
{
    public ?int $selectedLevel1 = null;
    public ?int $selectedLevel2 = null; 
    public ?int $selectedLevel3 = null;
    public ?int $selectedLevel4 = null;
    
    public Collection $level1Options;
    public Collection $level2Options;
    public Collection $level3Options;
    public Collection $level4Options;
    
    public function mount()
    {
        $this->level1Options = Location::where('level', 1)->get();
        $this->level2Options = collect();
        $this->level3Options = collect();
        $this->level4Options = collect();
    }
    
    public function updatedSelectedLevel1()
    {
        $this->level2Options = $this->selectedLevel1 
            ? Location::where('parent_id', $this->selectedLevel1)->get()
            : collect();
        $this->reset(['selectedLevel2', 'selectedLevel3', 'selectedLevel4']);
    }
    
    public function updatedSelectedLevel2()
    {
        $this->level3Options = $this->selectedLevel2
            ? Location::where('parent_id', $this->selectedLevel2)->get()
            : collect();
        $this->reset(['selectedLevel3', 'selectedLevel4']);
    }
    
    public function updatedSelectedLevel3()
    {
        $this->level4Options = $this->selectedLevel3
            ? Location::where('parent_id', $this->selectedLevel3)->get()
            : collect();
        $this->reset('selectedLevel4');
    }
    
    public function getSelectedLocationId(): ?int
    {
        return $this->selectedLevel4 
            ?? $this->selectedLevel3 
            ?? $this->selectedLevel2 
            ?? $this->selectedLevel1;
    }
}
```

#### 21.1.5 تكامل التصنيفات مع Forms
##### 21.1.5.1 Taxonomy Edit Integration
- **الوظيفة**: من `template-part/tax/content.php`
- **المطلوب**: ربط قوائم العرض مع نماذج التحرير
- **التطبيق في Filament**:
```php
// Integration with taxonomy editing
Actions\HeaderAction::make('edit_taxonomy')
    ->label('تعديل التصنيف')
    ->url(fn () => route('filament.admin.resources.locations.edit', ['record' => request('location_id')]))
    ->visible(fn () => request()->has('location_id')),
```

#### 21.1.6 اختبارات القوائم والعرض
##### 21.1.6.1 Unit Tests
```php
// tests/Unit/LocationHierarchyServiceTest.php
class LocationHierarchyServiceTest extends TestCase
{
    public function test_detect_location_level()
    {
        $service = new LocationHierarchyService();
        
        $region = Location::factory()->create(['parent_id' => null]);
        $city = Location::factory()->create(['parent_id' => $region->id]);
        $district = Location::factory()->create(['parent_id' => $city->id]);
        $area = Location::factory()->create(['parent_id' => $district->id]);
        
        $this->assertEquals(1, $service->detectLocationLevel($region));
        $this->assertEquals(2, $service->detectLocationLevel($city));
        $this->assertEquals(3, $service->detectLocationLevel($district));
        $this->assertEquals(4, $service->detectLocationLevel($area));
    }
    
    public function test_location_breadcrumb_generation()
    {
        $service = new LocationHierarchyService();
        
        $region = Location::factory()->create(['name_ar' => 'الرياض']);
        $city = Location::factory()->create(['name_ar' => 'الرياض المدينة', 'parent_id' => $region->id]);
        
        $breadcrumb = $service->getLocationBreadcrumb($city);
        
        $this->assertCount(2, $breadcrumb);
        $this->assertEquals('الرياض', $breadcrumb[0]->name_ar);
        $this->assertEquals('الرياض المدينة', $breadcrumb[1]->name_ar);
    }
}
```

##### 21.1.6.2 Feature Tests
```php
// tests/Feature/PropertyListResourceTest.php
class PropertyListResourceTest extends TestCase
{
    public function test_property_list_displays_correctly()
    {
        $admin = User::factory()->admin()->create();
        $properties = Property::factory()->count(5)->create();
        
        $this->actingAs($admin)
            ->get(PropertyListResource::getUrl('index'))
            ->assertSuccessful()
            ->assertSeeText($properties->first()->title);
    }
    
    public function test_property_list_filtering_works()
    {
        $admin = User::factory()->admin()->create();
        $type = PropertyType::factory()->create(['name_ar' => 'شقة']);
        $property = Property::factory()->create(['property_type_id' => $type->id]);
        
        $this->actingAs($admin)
            ->get(PropertyListResource::getUrl('index') . '?tableFilters[property_type_id][value]=' . $type->id)
            ->assertSuccessful()
            ->assertSeeText($property->title);
    }
    
    public function test_hierarchical_location_filter()
    {
        $admin = User::factory()->admin()->create();
        $region = Location::factory()->create(['name_ar' => 'الرياض', 'level' => 1]);
        $city = Location::factory()->create(['name_ar' => 'الرياض المدينة', 'parent_id' => $region->id, 'level' => 2]);
        $property = Property::factory()->create(['location_id' => $city->id]);
        
        $response = $this->actingAs($admin)
            ->get(PropertyListResource::getUrl('index') . '?location_filter=' . $region->id);
            
        $response->assertSuccessful()
                ->assertSeeText($property->title);
    }
}
```

#### 21.1.7 تحسينات الأداء للقوائم الكبيرة
##### 21.1.7.1 Database Indexing
```php
// database/migrations/add_indexes_for_lists.php
Schema::table('properties', function (Blueprint $table) {
    $table->index(['property_type_id', 'created_at']);
    $table->index(['property_status_id', 'updated_at']);
    $table->index(['location_id', 'property_type_id']);
    $table->index(['owner_id', 'created_at']);
});

Schema::table('locations', function (Blueprint $table) {
    $table->index(['parent_id', 'level']);
    $table->index(['level', 'name_ar']);
});
```

##### 21.1.7.2 Query Optimization
```php
// Eager loading for better performance
public static function table(Table $table): Table
{
    return $table
        ->query(
            Property::with([
                'property_type:id,name_ar',
                'property_status:id,name_ar,color',
                'location:id,name_ar,parent_id',
                'location.parent:id,name_ar,parent_id',
                'owner:id,name'
            ])
        );
}
```

#### 21.1.8 التوطين والعرض RTL
##### 21.1.8.1 Arabic UI Components
```php
// config/filament.php
'default_direction' => 'rtl',
'default_language' => 'ar',
```

##### 21.1.8.2 Custom Table Styling
```css
/* resources/css/app.css */
.fi-ta-table {
    direction: rtl;
}

.fi-ta-header-cell {
    text-align: right;
    font-family: 'Cairo', sans-serif;
}

.fi-ta-cell {
    text-align: right;
    font-family: 'Cairo', sans-serif;
}
```

### 21.2 مهام التطوير والأولوية
#### 21.2.1 المرحلة الأولى (أولوية عالية)
- ✅ تحليل archive.php وفهم نظام العرض القديم
- إنشاء LocationHierarchyService للمواقع الهرمية
- تطوير PropertyListResource الأساسي
- إنشاء مكون الفلترة الهرمية للمواقع

#### 21.2.2 المرحلة الثانية (أولوية متوسطة)  
- تطوير Advanced Filter Component
- إضافة تصدير البيانات (Excel, PDF, CSV)
- تحسين الأداء مع فهرسة قاعدة البيانات
- إنشاء اختبارات شاملة للقوائم

#### 21.2.3 المرحلة الثالثة (أولوية منخفضة)
- تطوير قوائم العرض للكيانات الأخرى
- إضافة إعدادات عرض شخصية للمستخدمين
- تكامل مع نظام الإشعارات
- تطوير لوحة معلومات تفاعلية للقوائم

### 21.3 ملاحظات التكامل
- **التبعيات**: Location Management, Property Models, User Permissions
- **التكامل**: مع جميع Filament Resources في النظام
- **الاختبارات**: Unit, Feature, Browser tests for all list components
- **الأداء**: Index optimization, eager loading, pagination for large datasets

---

## 22. نظام AJAX/API Endpoints - تحليل ajax-functions.php ✅ [تم المراجعة]

### 22.1 تحليل AJAX Handlers الموجودة في النظام القديم
**مرجع الملف**: `D:\Server\crm\wp-content\themes\alhiaa-system\ajax\ajax-functions.php` (1277 سطر)

تم اكتشاف 8 endpoints رئيسية لـ DataTables مع آليات فلترة متقدمة وتحكم في الصلاحيات:

#### 22.1.1 Property DataTables Endpoint (`property_datatables`)
**الخطوط**: 3-172
- **المعرف**: `wp_ajax_property_datatables` & `wp_ajax_nopriv_property_datatables`
- **Post Type**: `alh_property`
- **فلاتر الموقع**: state, city, city_center, area (hierarchical taxonomy filtering)
- **فلتر البحث**: `searchstring` في عنوان العقار
- **تحكم الصلاحيات**: 
  ```php
  if($user && $user->roles[0] === 'alh_owner') {
      $args['meta_key'] = 'owner';
      $args['meta_value'] = $user->ID;
      $can_edit = false;
  }
  ```
- **الأعمدة المُرجعة**: #, العنوان, المالك, عدد الوحدات, المحافظة, المدينة, المركز, الحي, الإجراءات
- **إجراءات مختلفة للأدوار**: edit/report للإدارة, report فقط للملاك

#### 22.1.2 Unit DataTables Endpoint (`unit_datatables`)  
**الخطوط**: 174-341
- **المعرف**: `wp_ajax_unit_datatables` & `wp_ajax_nopriv_unit_datatables`
- **Post Type**: `alh_unit`
- **فلاتر التطبيق**:
  - `property`: الوحدة في عقار محدد
  - `rooms_number`: عدد الغرف
  - `bathrooms_number`: عدد الحمامات  
  - `max_rant_price` & `min_rant_price`: نطاق سعر الإيجار (DECIMAL comparison)
- **فلتر البحث**: `unit_name` في عنوان الوحدة
- **الأعمدة المُرجعة**: #, الاسم, العقار, عدد الغرف, عدد الحمامات, سعر الإيجار, الإجراءات

#### 22.1.3 Property Contract DataTables (`prop_contract_datatables`)
**الخطوط**: 343-511  
- **المعرف**: `wp_ajax_prop_contract_datatables` & `wp_ajax_nopriv_prop_contract_datatables`
- **Post Type**: `property_contract`
- **فلاتر العقود**:
  - `contract_owner`: المالك
  - `contract_property`: العقار المحدد
  - `contract_price`: سعر العقد الثابت
  - `max_rant_price` & `min_rant_price`: نطاق الأسعار
- **معالجة التواريخ**: 
  ```php
  $date_creation_contract = date('Y-m-d', strtotime('+'.$contract_duration_per_month.' month', strtotime($date_creation_contract)));
  $date_creation_contract = date('Y-m-d', strtotime('-1 day', strtotime($date_creation_contract)));
  ```
- **الأعمدة المُرجعة**: #, الاسم, المالك, مدة العقد (شهور), العقار, تاريخ الانتهاء, السعر, الإجراءات

#### 22.1.4 Unit Contract DataTables (`unit_contract_datatables`)
**الخطوط**: 514-705
- **المعرف**: `wp_ajax_unit_contract_datatables` & `wp_ajax_nopriv_unit_contract_datatables`  
- **Post Type**: `unit_contract`
- **فلاتر العقود**:
  - `unit_name`: الوحدة المحددة
  - `property_name`: العقار التابع له
  - `tenant_name`: المستأجر
  - `contract_price`: سعر العقد
  - `end_date`: تاريخ الانتهاء
- **تحكم الحذف**: 
  ```php
  if (current_user_can('administrator')) {
      $remove = '<a href="...delete link...">حذف</a>';
  }
  ```
- **حساب تاريخ الانتهاء**:
  ```php
  $date_end_contract = date_i18n('Y-m-d', strtotime('+'.$contract_duration_per_month.' month', $date_end_contract));
  ```
- **الأعمدة المُرجعة**: #, الاسم, المستأجر, الوحدة, العقار, مدة العقد, تاريخ الانتهاء, سعر الإيجار, الإجراءات

#### 22.1.5 Collection Payment DataTables (`collection_payment_datatables`)
**الخطوط**: 707-857
- **المعرف**: `wp_ajax_collection_payment_datatables` & `wp_ajax_nopriv_collection_payment_datatables`
- **Post Type**: `collection_payment` (استخدام Custom Table)
- **تكامل قاعدة البيانات المخصصة**:
  ```php
  global $wpdb;
  $table_name = $wpdb->prefix . 'unit_collection';
  $contract = get_custom_table_field($table_name, 'contract', $post_id);
  ```
- **حالات الدفع المترجمة**:
  ```php
  $payment_status = [
      'collected' => 'تم التحصيل',
      'worth_collecting' => 'تستحق التحصيل', 
      'delayed' => 'المؤجلة',
      'overdue' => 'تجاوزة المدة',
  ];
  ```
- **منطق الإجراءات الشرطي**:
  ```php
  if($contract_status === 'collected') {
      $report = alh_report_link(get_the_permalink(), '<i class="fa-solid fa-file-lines"></i>');
      $remove = '';
  } else {
      $report = '';
  }
  ```
- **الأعمدة المُرجعة**: #, الاسم, العقد, المبلغ, الحالة, فترة التحصيل, ملاحظات التجاوز, الإجراءات

#### 22.1.6 Supply Payment DataTables (`supply_payment_datatables`)
**الخطوط**: 859-1005
- **المعرف**: `wp_ajax_supply_payment_datatables` & `wp_ajax_nopriv_supply_payment_datatables`
- **Post Type**: `supply_payment` (استخدام Custom Table)
- **تكامل قاعدة البيانات**: `$wpdb->prefix . 'supply_payment'`
- **حالات التوريد**:
  ```php
  $payment_status = [
      'pending' => 'في الانتظار',
      'collected' => 'تم التوريد', 
      'worth_collecting' => 'تستحق التوريد',
  ];
  ```
- **تحكم الصلاحيات للملاك**:
  ```php
  if($user && $user->roles[0] === 'alh_owner') {
      $args['owner'] = $user->ID;
      $can_edit = false;
  }
  ```
- **معالجة التواريخ**: تنسيق التواريخ بـ `date('Y-m-d', strtotime($supply_date))`
- **الأعمدة المُرجعة**: #, الاسم, العقد, المبلغ الإجمالي, الحالة, تاريخ الاستحقاق, تاريخ التوريد, الإجراءات

#### 22.1.7 Property Repair DataTables (`property_repair_datatables`)
**الخطوط**: 1007-1183
- **المعرف**: `wp_ajax_property_repair_datatables` & `wp_ajax_nopriv_property_repair_datatables`
- **Post Type**: `property_repair`
- **أنواع الصيانة المترجمة**:
  ```php
  $maintenance_type_lable = [
      'general_maintenance' => 'عملية عامة',
      'special_maintenance' => 'عملية خاصة', 
      'government_payment_unit' => 'التزام خاص',
      'government_payment_prop' => 'التزام عام',
  ];
  ```
- **فلاتر الصيانة**:
  - `maintenance_type`: نوع الصيانة
  - `maintenance_property`: العقار
  - `maintenance_unit`: الوحدة المحددة
  - `total_maintenance_cost`: تكلفة الصيانة
  - `maintenance_date`: تاريخ الصيانة
- **منطق العقار/الوحدة**:
  ```php
  if(!empty($maintenance_unit)) {
      $maintenance_unit_property = get_the_title($maintenance_unit);
  } else if(!empty($maintenance_property)) {
      $maintenance_unit_property = get_the_title($maintenance_property);
  }
  ```
- **الأعمدة المُرجعة**: #, الاسم, العقار/الوحدة, التكلفة الإجمالية, تاريخ الصيانة, نوع الصيانة, المحتوى, الإجراءات

#### 22.1.8 Members DataTables (`alh_members_datatables`)
**الخطوط**: 1185-1277
- **المعرف**: `wp_ajax_alh_members_datatables` & `wp_ajax_nopriv_alh_members_datatables`
- **استعلام المستخدمين**:
  ```php
  $args = array( 
      'role__in' => array('alh_owner', 'alh_tenant'),
      'number' => $request['length'], 
      'offset' => $request['start'],
  );
  ```
- **بيانات المستخدم المُسترجعة**:
  - `owner_phone`: رقم الهاتف الأساسي
  - `owner_phone_2`: رقم الهاتف الثانوي  
  - `owner_adress`: العنوان
  - `first_name` + `last_name`: الاسم الكامل
- **معالجة الأدوار**:
  ```php
  global $wp_roles;
  $u = get_userdata($user->ID);
  $role = array_shift($u->roles);
  $user->role = $wp_roles->roles[$role]['name'];
  ```
- **الأعمدة المُرجعة**: #, الاسم الكامل, البريد الإلكتروني, الدور, رقم الهاتف, العنوان, الإجراءات

### 22.2 متطلبات التحويل إلى Laravel/Filament

#### 22.2.1 Laravel API Controllers المطلوبة
- **PropertyDataController.php**: 
  ```php
  class PropertyDataController extends Controller {
      public function datatables(Request $request) {
          return PropertyDatatableService::handle($request);
      }
  }
  ```
- **UnitDataController.php**: لجداول الوحدات
- **ContractDataController.php**: لجداول العقود (property & unit contracts)
- **PaymentDataController.php**: لجداول المدفوعات (collection & supply)  
- **MaintenanceDataController.php**: لجداول الصيانة
- **UserDataController.php**: لجداول المستخدمين

#### 22.2.2 Filament Table Integration
**استخدام Filament Tables مع AJAX للبيانات الكبيرة:**
```php
// في PropertyResource
public static function table(Table $table): Table {
    return $table
        ->columns([
            TextColumn::make('title')->label('العنوان'),
            TextColumn::make('owner.name')->label('المالك'),
            TextColumn::make('units_count')->label('عدد الوحدات'),
            // ... باقي الأعمدة
        ])
        ->filters([
            SelectFilter::make('state_id')->relationship('state', 'name'),
            SelectFilter::make('city_id')->relationship('city', 'name'),
            Filter::make('search')->form([
                TextInput::make('searchstring')->label('البحث'),
            ]),
        ])
        ->actions([
            // إجراءات مختلفة بناءً على دور المستخدم
            EditAction::make()->visible(fn() => auth()->user()->hasRole(['admin', 'manager'])),
            Action::make('report')->icon('heroicon-o-document-text'),
        ]);
}
```

#### 22.2.3 Services المطلوبة لمعالجة البيانات
- **PropertyDatatableService.php**: معالجة بيانات العقارات مع الفلترة الهرمية
- **UnitDatatableService.php**: معالجة بيانات الوحدات مع فلاتر الأسعار
- **ContractDatatableService.php**: معالجة العقود مع حسابات التواريخ
- **PaymentDatatableService.php**: معالجة المدفوعات مع Custom Tables
- **MaintenanceDatatableService.php**: معالجة بيانات الصيانة
- **UserDatatableService.php**: معالجة بيانات المستخدمين مع metadata

#### 22.2.4 Role-Based Access Control في API
**تطبيق نفس منطق التحكم في الصلاحيات:**
```php
// PropertyDatatableService
public function applyRoleFilters(Builder $query, User $user): Builder {
    if ($user->hasRole('alh_owner')) {
        return $query->where('owner_id', $user->id);
    }
    return $query; // إرجاع جميع البيانات للإدارة
}

// في الـ Actions
public function getAvailableActions(User $user): array {
    $actions = ['view'];
    
    if ($user->hasRole(['admin', 'manager'])) {
        $actions[] = 'edit';
        $actions[] = 'delete';
    }
    
    $actions[] = 'report'; // متاح للجميع
    return $actions;
}
```

#### 22.2.5 Custom Table Integration
**معالجة Custom Tables للمدفوعات:**
```php
// PaymentCustomTableService
class PaymentCustomTableService {
    public function getCollectionPayments(int $postId): array {
        return DB::table('unit_collection')
            ->where('post_id', $postId)
            ->first();
    }
    
    public function getSupplyPayments(int $postId): array {
        return DB::table('supply_payment')
            ->where('post_id', $postId)
            ->first();
    }
}
```

#### 22.2.6 Status Translation Service
**ترجمة الحالات والأنواع:**
```php
class StatusTranslationService {
    public static function getPaymentStatuses(): array {
        return [
            'collected' => 'تم التحصيل',
            'worth_collecting' => 'تستحق التحصيل',
            'delayed' => 'المؤجلة', 
            'overdue' => 'تجاوزة المدة',
        ];
    }
    
    public static function getMaintenanceTypes(): array {
        return [
            'general_maintenance' => 'عملية عامة',
            'special_maintenance' => 'عملية خاصة',
            'government_payment_unit' => 'التزام خاص',
            'government_payment_prop' => 'التزام عام',
        ];
    }
}
```

### 22.3 Testing Strategy للـ AJAX Endpoints

#### 22.3.1 Unit Tests
- **PropertyDatatableServiceTest**: اختبار الفلترة والترقيم
- **RoleBasedFilterTest**: اختبار تطبيق فلاتر الصلاحيات
- **StatusTranslationTest**: اختبار ترجمة الحالات
- **CustomTableIntegrationTest**: اختبار تكامل الجداول المخصصة

#### 22.3.2 Feature Tests  
- **PropertyDataTableEndpointTest**: اختبار شامل لـ endpoint العقارات
- **UserRoleAccessTest**: اختبار وصول الأدوار المختلفة للبيانات
- **DataTablePaginationTest**: اختبار الترقيم والفرز
- **FilterValidationTest**: اختبار صحة الفلاتر

#### 22.3.3 Browser Tests
- **DataTableInteractionTest**: اختبار التفاعل مع الجداول
- **FilterApplicationTest**: اختبار تطبيق الفلاتر في المتصفح
- **RoleBasedUITest**: اختبار واجهة المستخدم بناءً على الأدوار

### 22.4 Performance Optimization

#### 22.4.1 Database Indexing
- **فهارس العقارات**: (owner_id, state_id, city_id, status_id)
- **فهارس الوحدات**: (property_id, unit_type_id, rent_price)
- **فهارس العقود**: (tenant_id, property_id, unit_id, end_date)
- **فهارس المدفوعات**: (contract_id, status, payment_date)

#### 22.4.2 Eager Loading Strategy
```php
// في PropertyDatatableService
$query->with([
    'owner:id,name',
    'state:id,name',
    'city:id,name', 
    'units:id,property_id',
]);
```

#### 22.4.3 Caching Strategy
- **Filter Options**: cache قوائم المحافظات/المدن لمدة ساعة
- **User Permissions**: cache صلاحيات المستخدم لمدة 15 دقيقة
- **Count Queries**: cache عدد النتائج للاستعلامات الثقيلة

### 22.5 ملاحظات هامة للمطورين

#### 22.5.1 نقاط حرجة مكتشفة
- **Custom Tables**: استخدام جداول مخصصة للمدفوعات يتطلب معالجة خاصة
- **Role-Based Filtering**: فلترة البيانات على مستوى الاستعلام وليس على مستوى العرض
- **Date Calculations**: حسابات معقدة للتواريخ في العقود (إضافة شهور - يوم واحد)
- **Conditional Actions**: الإجراءات تختلف بناءً على الحالة والدور معاً

#### 22.5.2 أولويات التطوير
1. **المرحلة الأولى**: Property & Unit DataTables (الأساسية)
2. **المرحلة الثانية**: Contract DataTables (متوسطة التعقيد)  
3. **المرحلة الثالثة**: Payment DataTables (معقدة - تتطلب Custom Tables)
4. **المرحلة الرابعة**: Maintenance & Users DataTables (تكميلية)

#### 22.5.3 التبعيات المطلوبة
- **Models**: Property, Unit, Contract, Payment, User
- **Relationships**: جميع العلاقات محددة ومفعلة
- **Permissions**: Spatie Permission معد بالكامل
- **Custom Tables**: مايجريشن للجداول المخصصة
- **Status Enums**: إنشاء Enums للحالات والأنواع

## 23. نظام المهام المجدولة والإشعارات (Scheduled Tasks & Notifications)

### 23.1 تحليل class-alhiaa-cron.php

#### 23.1.1 المهام المجدولة المكتشفة
- **الملف المرجعي**: `D:\Server\crm\wp-content\themes\alhiaa-system\classes\class-alhiaa-cron.php`
- **حجم الملف**: 61 سطر من الكود
- **الوظيفة الأساسية**: إرسال إشعارات يومية بالدفعات المستحقة للتحصيل

#### 23.1.2 المهمة الحالية: إشعارات دفعات التحصيل
```php
// جدولة يومية
wp_schedule_event( time(), 'daily', 'alh_notifications' );

// استعلام المدفوعات المستحقة  
'post_type' => 'collection_payment',
'meta_query' => [
    'key' => 'contract_status',
    'value' => 'worth_collecting',
    'compare' => '='
]

// إرسال البريد الإلكتروني
$multiple_recipients = [
    'alhiaaalaqaria@gmail.com',
    'sherif.ali.sa3d@gmail.com'
];
```

#### 23.1.3 آلية العمل الحالية
1. **التشغيل**: يومياً عبر WordPress Cron
2. **البحث**: عن دفعات بحالة "worth_collecting"
3. **الفلترة**: دفعات لم يتم إرسال إشعار لها (`send_mail != 1`)
4. **الإرسال**: بريد إلكتروني للمدراء
5. **التتبع**: وضع علامة "تم الإرسال" (`send_mail = 1`)

### 23.2 تحويل إلى Laravel Task Scheduling

#### 23.2.1 Laravel Command للإشعارات
- **Command**: `php artisan notifications:collection-payments`
- **الملف**: `app/Console/Commands/SendCollectionPaymentNotifications.php`
- **الجدولة**: في `app/Console/Kernel.php`

```php
// في Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('notifications:collection-payments')
             ->daily()
             ->at('09:00');
}
```

#### 23.2.2 Notification Class
- **الملف**: `app/Notifications/CollectionPaymentDue.php`
- **نوع**: Laravel Mail Notification
- **المستقبلون**: Admin users with specific permission

#### 23.2.3 Service Class للإشعارات
- **الملف**: `app/Services/NotificationService.php`
- **الوظائف**:
  - `getOverdueCollectionPayments()`
  - `sendCollectionPaymentNotifications()`
  - `markNotificationSent()`

### 23.3 تحسينات مطلوبة للنظام الجديد

#### 23.3.1 إدارة قوائم المستقبلين
- **جدول**: `notification_recipients` (id, user_id, notification_type, is_active)
- **Interface**: إدارة المستقبلين عبر Filament Panel
- **مرونة**: إضافة/حذف مستقبلين دون تعديل الكود

#### 23.3.2 أنواع إشعارات إضافية
- **عقود منتهية الصلاحية**: إشعار قبل انتهاء العقود بـ 30/7 أيام
- **مدفوعات متأخرة**: إشعارات تصاعدية للمدفوعات المتأخرة
- **طلبات صيانة عاجلة**: إشعار فوري للصيانات العاجلة
- **تقارير دورية**: ملخص شهري للعمليات

#### 23.3.3 قنوات الإشعارات المتعددة
- **البريد الإلكتروني**: الحالي + تحسين القوالب
- **SMS**: للإشعارات العاجلة (اختياري)
- **In-App**: إشعارات داخل النظام عبر Filament
- **WhatsApp Business API**: للمستأجرين والملاك (مستقبلي)

### 23.4 متطلبات التنفيذ

#### 23.4.1 Database Schema
```sql
-- جدول أنواع الإشعارات
CREATE TABLE notification_types (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    description TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- جدول المستقبلين
CREATE TABLE notification_recipients (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    notification_type_id BIGINT,
    channel ENUM('email', 'sms', 'in_app'),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (notification_type_id) REFERENCES notification_types(id)
);

-- جدول سجل الإشعارات المرسلة
CREATE TABLE notification_logs (
    id BIGINT PRIMARY KEY,
    notification_type_id BIGINT,
    related_model_type VARCHAR(255),
    related_model_id BIGINT,
    recipient_email VARCHAR(255),
    sent_at TIMESTAMP,
    status ENUM('sent', 'failed', 'pending'),
    failure_reason TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 23.4.2 Laravel Models المطلوبة
- `NotificationType.php`
- `NotificationRecipient.php` 
- `NotificationLog.php`

#### 23.4.3 Commands المطلوبة
- `SendCollectionPaymentNotifications.php`
- `SendContractExpiryNotifications.php`
- `SendOverduePaymentReminders.php`
- `SendMaintenanceUrgentAlerts.php`
- `GenerateMonthlyReports.php`

#### 23.4.4 Notifications المطلوبة
- `CollectionPaymentDue.php`
- `ContractExpiringSoon.php`
- `PaymentOverdue.php`
- `MaintenanceUrgent.php`
- `MonthlyReport.php`

### 23.5 Filament Management Interface

#### 23.5.1 Notification Settings Resource
- **قائمة أنواع الإشعارات**: تفعيل/إلغاء الأنواع المختلفة
- **إدارة المستقبلين**: إضافة/حذف المستقبلين لكل نوع
- **جدولة التشغيل**: تعديل أوقات التشغيل
- **قوالب الرسائل**: تخصيص محتوى الإشعارات

#### 23.5.2 Notification Logs
- **سجل الإشعارات المرسلة**: عرض تفصيلي للإشعارات
- **حالة التسليم**: نجح/فشل مع تفاصيل الأخطاء
- **إحصائيات**: معدلات النجاح والفشل
- **إعادة الإرسال**: للإشعارات الفاشلة

#### 23.5.3 Dashboard Widgets
- **إشعارات اليوم**: عدد الإشعارات المرسلة اليوم
- **إشعارات معلقة**: الإشعارات المنتظرة للإرسال
- **حالة النظام**: صحة نظام الإشعارات

### 23.6 Testing Strategy

#### 23.6.1 Unit Tests
- `NotificationServiceTest::test_get_overdue_collection_payments()`
- `NotificationServiceTest::test_mark_notification_sent()`
- `CollectionPaymentDueTest::test_notification_content()`
- `NotificationCommandTest::test_command_execution()`

#### 23.6.2 Feature Tests
- `SendCollectionPaymentNotificationsTest::test_daily_notifications()`
- `NotificationRecipientsTest::test_recipient_management()`
- `NotificationLogsTest::test_log_creation()`

#### 23.6.3 Integration Tests
- `ScheduledTasksTest::test_laravel_scheduler_integration()`
- `MailDeliveryTest::test_email_delivery()`
- `FilamentInterfaceTest::test_notification_management_ui()`

### 23.7 Migration من WordPress

#### 23.7.1 Data Migration
- **Current Recipients**: تحويل العناوين المكتوبة في الكود إلى قاعدة البيانات
- **Notification History**: لا يوجد سجل في النظام القديم
- **Settings**: إنشاء إعدادات افتراضية

#### 23.7.2 Migration Command
```bash
php artisan migrate:notifications-from-wordpress
```

#### 23.7.3 Seeder للبيانات الأولية
- **NotificationTypesSeeder**: إنشاء أنواع الإشعارات الأساسية
- **NotificationRecipientsSeeder**: إدراج المستقبلين الحاليين
- **Default Settings**: إعدادات افتراضية للجدولة

### 23.8 Security & Performance

#### 23.8.1 أمان الإشعارات
- **Rate Limiting**: تحديد عدد الإشعارات في الساعة
- **Validation**: التحقق من صحة عناوين البريد
- **Encryption**: تشفير البيانات الحساسة في الإشعارات
- **Queue Security**: حماية Queue Jobs

#### 23.8.2 الأداء والتحسين
- **Queue Jobs**: تشغيل الإشعارات في الخلفية
- **Batch Processing**: معالجة مجمعة للإشعارات الكثيرة
- **Database Indexing**: فهارس على جداول الإشعارات
- **Caching**: تخزين مؤقت لقوائم المستقبلين

### 23.9 Implementation Priority

#### 23.9.1 المرحلة الأولى (أساسية)
1. **Command**: SendCollectionPaymentNotifications
2. **Notification**: CollectionPaymentDue  
3. **Models**: NotificationType, NotificationRecipient, NotificationLog
4. **Migration**: من النظام الحالي

#### 23.9.2 المرحلة الثانية (تحسينات)
1. **Filament Interface**: إدارة الإشعارات
2. **Additional Commands**: باقي أنواع الإشعارات
3. **Dashboard Widgets**: لوحة مراقبة الإشعارات
4. **Queue Integration**: تشغيل في الخلفية

#### 23.9.3 المرحلة الثالثة (متقدمة)
1. **Multiple Channels**: SMS, In-App notifications
2. **Advanced Scheduling**: جدولة مرنة
3. **Template System**: قوالب قابلة للتخصيص
4. **Analytics**: تحليلات الإشعارات

### 23.10 Dependencies & Integration

#### 23.10.1 Laravel Packages المطلوبة
- **Laravel Notification**: مدمج في Laravel
- **Laravel Queue**: لتشغيل الإشعارات في الخلفية
- **Laravel Mail**: لإرسال البريد الإلكتروني
- **Filament Notifications**: للإشعارات داخل النظام

#### 23.10.2 التكامل مع النظام
- **Models Integration**: ربط مع Models الأساسية (Payment, Contract, etc.)
- **Permissions**: صلاحيات إدارة الإشعارات
- **Middleware**: التحقق من صلاحيات الوصول
- **Event Listeners**: تشغيل الإشعارات عند الأحداث

#### 23.10.3 Configuration
- **Environment Variables**: إعدادات البريد الإلكتروني
- **Config Files**: ملفات تكوين الإشعارات
- **Queue Configuration**: إعداد Queues للإشعارات
- **Scheduler Configuration**: إعداد Laravel Task Scheduler
