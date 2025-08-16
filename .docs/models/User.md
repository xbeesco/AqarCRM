# User Model Tasks - Al-Hiaa Real Estate Management System
**Target Stack:** Laravel 12 & Filament 4

## المبادئ الأساسية للتحسين
- ✅ استخدام ميزات Filament 4 الجاهزة بدلاً من البناء من الصفر
- ✅ استخدام Laravel's built-in User model مع التوسعات المطلوبة
- ✅ استخدام Spatie Laravel Permission بدلاً من نظام صلاحيات مخصص
- ✅ الاستفادة من Filament's built-in User Resource

## 1. إعداد نظام المستخدمين المحسن

### 1.1 استخدام Laravel's User Model
```php
// استخدام User model الافتراضي مع التوسعات
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;
    
    protected $fillable = [
        'name', 'email', 'password', 'phone',
        // حقول إضافية للملاك
        'national_id', 'commercial_register', 'tax_number', 'bank_account',
        // حقول إضافية للمستأجرين
        'occupation', 'employer', 'emergency_contact',
        // حقول إضافية للموظفين
        'department', 'employee_id', 'assigned_properties'
    ];
}
```

### 1.2 تثبيت وتكوين Spatie Permission مع الهيكل الإداري
- **التثبيت**: `composer require spatie/laravel-permission`
- **الأدوار المطلوبة**: 
  - `super_admin` - الإدارة العليا (كامل الصلاحيات)
  - `admin` - المدير العام  
  - `contracts_employee` - موظف عقود
  - `finance_employee` - موظف مالية
  - `maintenance_employee` - موظف صيانة
  - `owner` - مالك عقار
  - `tenant` - مستأجر
- **الصلاحيات المفصلة**: properties.*, units.*, contracts.*, payments.*, expenses.*, reports.*, users.*

### 1.3 استخدام Filament's User Resource (بدلاً من إنشاء مخصص)
```php
// استخدام Filament's built-in User Resource مع التخصيص
class UserResource extends Resource
{
    protected static ?string $model = User::class;
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required(),
            TextInput::make('email')->email()->required(),
            Select::make('roles')
                ->relationship('roles', 'name')
                ->multiple()
                ->preload(),
            // حقول إضافية حسب الدور
        ]);
    }
}
```

## 2. الملاك (Owners)

### 2.1 حقول إضافية للملاك (تحسين البساطة)
- **Migration واحدة**: `add_user_fields_to_users_table.php` بدلاً من migrations منفصلة
- **الحقول**: national_id, commercial_register, tax_number, bank_account, occupation, employer, emergency_contact
- **Nullable Fields**: جميع الحقول الإضافية nullable لدعم جميع أنواع المستخدمين

### 2.2 استخدام Eloquent Scopes (بدلاً من Repository Pattern)
```php
// في User Model
public function scopeOwners($query)
{
    return $query->whereHas('roles', function($q) {
        $q->where('name', 'owner');
    });
}

public function scopeTenants($query)
{
    return $query->whereHas('roles', function($q) {
        $q->where('name', 'tenant');
    });
}
```

### 2.3 الهيكل الإداري المفصل

#### 2.3.1 Super Admin (الإدارة العليا)
```php
// صلاحيات كاملة
'super_admin' => [
    '*', // جميع الصلاحيات
    'system.settings', // إعدادات النظام
    'users.manage_all', // إدارة جميع المستخدمين
    'roles.manage', // إدارة الأدوار
]
```

#### 2.3.2 Admin (المدير العام)  
```php
'admin' => [
    'properties.*', 'units.*', 'contracts.*', 
    'payments.*', 'expenses.*', 'reports.*',
    'users.view', 'users.create_employee',
    'dashboard.full_access'
]
```

#### 2.3.3 الموظفين المتخصصين
```php
// موظف العقود
'contracts_employee' => [
    'contracts.*', 'properties.view', 'units.*',
    'users.view_owners', 'users.view_tenants',
    'reports.contracts'
],

// موظف المالية  
'finance_employee' => [
    'payments.*', 'expenses.view', 'reports.financial',
    'contracts.view', 'properties.view'
],

// موظف الصيانة
'maintenance_employee' => [
    'expenses.*', 'properties.view', 'units.view',
    'reports.maintenance'
]
```

#### 2.3.4 استخدام Filament's Role-based Access
```php
// في Filament Resources
public static function canViewAny(): bool
{
    return auth()->user()->can('view_properties') ||
           auth()->user()->hasRole(['super_admin', 'admin']);
}

public static function canCreate(): bool
{
    return auth()->user()->can('create_properties');
}

// في الجداول - فلترة البيانات حسب الدور
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();
    
    if (auth()->user()->hasRole('owner')) {
        return $query->where('owner_id', auth()->id());
    }
    
    if (auth()->user()->hasRole('contracts_employee')) {
        $assignedProperties = auth()->user()->assigned_properties ?? [];
        return $query->whereIn('id', $assignedProperties);
    }
    
    return $query; // Admin و Super Admin يرون كل شيء
}
```

## 3. المستأجرون (Tenants)

### 3.1 نفس النهج المبسط للملاك
- **نفس الجدول**: استخدام users table مع role differentiation
- **نفس الحقول الإضافية**: مشاركة الحقول بين الملاك والمستأجرين
- **Role-based Logic**: تمييز الوظائف بناءً على الدور

### 3.2 استخدام Filament's Conditional Fields
```php
// في User Resource Form
TextInput::make('national_id')
    ->visible(fn (Get $get) => 
        collect($get('roles'))->contains(fn($role) => 
            in_array($role, ['owner', 'tenant'])
        )
    ),
```

## 4. التبسيطات المطبقة

### 4.1 إزالة Over-Engineering
- **❌ إزالة**: OwnerService, TenantService, OwnerRepository, TenantRepository
- **✅ بدلاً منها**: استخدام Eloquent relationships و Scopes
- **❌ إزالة**: أحداث مخصصة للمستخدمين
- **✅ بدلاً منها**: استخدام Laravel's built-in events

### 4.2 استخدام Filament's Built-in Features
- **User Management**: استخدام Filament's User Resource
- **Role Management**: استخدام Filament Shield أو Spatie's Filament package
- **Permissions**: استخدام Filament's built-in authorization

### 4.3 تبسيط الاختبارات
- **Unit Tests**: التركيز على Business Logic فقط
- **Feature Tests**: اختبار التدفقات الأساسية
- **Integration Tests**: اختبار تكامل Filament مع Spatie Permission

## 5. Migration من WordPress

### 5.1 خدمة استيراد مبسطة
```php
class UserImportService
{
    public function importUsers(): void
    {
        // استيراد مبسط من WordPress
        $wpUsers = $this->getWordPressUsers();
        
        foreach ($wpUsers as $wpUser) {
            $user = User::create([
                'name' => $wpUser->display_name,
                'email' => $wpUser->user_email,
                'password' => Hash::make('temp_password'),
            ]);
            
            // تحديد الدور بناءً على WordPress role
            $role = $this->mapWordPressRole($wpUser->role);
            $user->assignRole($role);
        }
    }
}
```

### 5.2 تبسيط User Meta Migration
- **❌ إزالة**: PropertyOwnershipService معقد
- **✅ بدلاً منها**: مباشرة تحديث Property records

## 6. الاختبارات المبسطة

### 6.1 اختبارات أساسية فقط
- **Unit**: UserModelTest (العلاقات الأساسية)
- **Feature**: UserResourceTest (Filament CRUD)
- **Integration**: PermissionTest (Spatie integration)

### 6.2 إزالة الاختبارات المعقدة
- **❌ إزالة**: اختبارات الخدمات المحذوفة
- **❌ إزالة**: اختبارات الأحداث المخصصة
- **✅ التركيز**: على وظائف Filament الأساسية

## 7. Filament 4 Implementation

### 7.1 User Resource مع Filament 4 Syntax
```php
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;

public static function form(Schema $schema): Schema
{
    return $schema->schema([
        Section::make('User Information')
            ->schema([
                TextInput::make('name')->required(),
                TextInput::make('email')->email()->required(),
                // باقي الحقول
            ])
    ]);
}
```

### 7.2 استخدام Filament Clusters
- **SettingsCluster**: تجميع User و Role resources
- **تنظيم بسيط**: بدلاً من تعقيدات متعددة

## الملخص النهائي

تم تبسيط نظام المستخدمين ليستخدم:
1. **Laravel's built-in User model** بدلاً من models مخصصة معقدة
2. **Spatie Permission** بدلاً من نظام صلاحيات من الصفر
3. **Filament's User Resource** بدلاً من resources مخصصة
4. **Eloquent relationships** بدلاً من Repository pattern
5. **Built-in Laravel features** بدلاً من خدمات مخصصة معقدة

هذا النهج يحافظ على جودة التطبيق مع تقليل التعقيد والصيانة المطلوبة.