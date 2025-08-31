# 🔒 تقرير مراجعة الصلاحيات والثغرات الأمنية

## 📅 تاريخ المراجعة: 31-08-2025

## 🎯 الهدف
مراجعة نظام الصلاحيات للتأكد من:
1. الموظفين لا يمكنهم إضافة/تعديل/حذف المستخدمين
2. منع الوصول المباشر للصفحات عبر URL
3. تطبيق الصلاحيات على كل الـ Resources

---

## 🚨 الثغرات المكتشفة

### 1. **UserResource - مفتوح للجميع**
**الملف:** `app/Filament/Resources/UserResource.php`
- ✅ **المشكلة:** لا يوجد أي قيود على من يمكنه الوصول
- ✅ **الخطورة:** عالية جداً
- ✅ **الحل المطلوب:** إضافة `canAccess()` و `canViewAny()` و `canCreate()` و `canEdit()` و `canDelete()`

### 2. **EmployeeResource - مفتوح للجميع**
**الملف:** `app/Filament/Resources/EmployeeResource.php`
- ✅ **المشكلة:** أي موظف يمكنه تعديل موظفين آخرين
- ✅ **الخطورة:** عالية
- ✅ **الحل المطلوب:** قصر الوصول على super_admin و admin فقط

### 3. **OwnerResource - مفتوح للجميع**
**الملف:** `app/Filament/Resources/OwnerResource.php`  
- ✅ **المشكلة:** الموظفون يمكنهم تعديل بيانات الملاك
- ✅ **الخطورة:** عالية
- ✅ **الحل المطلوب:** منع التعديل والحذف للموظفين

### 4. **TenantResource - مفتوح للجميع**
**الملف:** `app/Filament/Resources/TenantResource.php`
- ✅ **المشكلة:** الموظفون يمكنهم تعديل بيانات المستأجرين
- ✅ **الخطورة:** متوسطة
- ✅ **الحل المطلوب:** السماح بالعرض فقط للموظفين

### 5. **الوصول المباشر عبر URL**
- ✅ **المشكلة:** يمكن الوصول لصفحات التعديل مباشرة عبر `/admin/users/1/edit`
- ✅ **الخطورة:** عالية جداً
- ✅ **الحل المطلوب:** فحص الصلاحيات في `EditPage` و `CreatePage`

---

## 🛠️ الحلول المطبقة

### ✅ الحل المطبق: استخدام Filament Resource Methods

#### الكود المطبق في UserResource:
```php
// app/Filament/Resources/UserResource.php

// صلاحيات الوصول للـ Resource
public static function canViewAny(): bool
{
    $userType = auth()->user()?->type;
    return in_array($userType, ['super_admin', 'admin']);
}

public static function canCreate(): bool
{
    return auth()->user()?->type === 'super_admin';
}

public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
{
    return auth()->user()?->type === 'super_admin';
}

public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
{
    return auth()->user()?->type === 'super_admin';
}

public static function canDeleteAny(): bool
{
    return auth()->user()?->type === 'super_admin';
}
```

#### 📊 مقارنة الطرق المختلفة:

| الطريقة | المميزات | العيوب | التقييم |
|---------|----------|--------|---------|
| **auth()->user()->type** | • بسيط ومختصر<br>• مألوف لمطوري Laravel | • ❌ **خطر!** Error إذا لم يكن مسجل دخول<br>• ❌ قد لا يعمل مع guards مخصصة | ⭐⭐ |
| **Filament::auth()->user()** | • ✅ يستخدم guard الخاص بـ Filament<br>• ✅ آمن مع التحقق من null | • طويل نسبياً<br>• يحتاج import إضافي | ⭐⭐⭐ |
| **optional(auth()->user())** | • ✅ آمن من null errors<br>• ✅ Laravel idiomatic | • قد لا يعمل مع guards مخصصة<br>• أطول قليلاً | ⭐⭐⭐⭐ |
| **auth()->user()?->type** ✅ | • ✅ **الأقصر والأنظف**<br>• ✅ آمن من null<br>• ✅ PHP 8 modern syntax<br>• ✅ سهل القراءة | • يحتاج PHP 8.0+ | ⭐⭐⭐⭐⭐ |

### 🎯 لماذا اخترنا `auth()->user()?->type`؟

1. **الأمان**: يستخدم null-safe operator (`?->`) للحماية من أخطاء null
2. **البساطة**: سطر واحد فقط بدون تعقيدات
3. **الحداثة**: يستخدم PHP 8 features
4. **التوافق**: يعمل مع Filament لأنه يستخدم Laravel auth
5. **القراءة**: واضح ومباشر للمطورين

### ⚠️ ملاحظات مهمة:

1. **PHP Version**: يتطلب PHP 8.0 أو أعلى
2. **User Model**: يجب أن يحتوي User model على حقل `type`
3. **Authentication**: يجب أن يكون المستخدم مسجل دخول
4. **Testing**: يجب اختبار كل الصلاحيات

### الحل 3: حماية صفحات Edit/Create
```php
// في EditUser.php
public function mount($record): void
{
    abort_unless(static::getResource()::canEdit($this->getRecord()), 403);
    parent::mount($record);
}

// في CreateUser.php
public function mount(): void
{
    abort_unless(static::getResource()::canCreate(), 403);
    parent::mount();
}
```

---

## 📊 مصفوفة الصلاحيات المطلوبة

| Resource | super_admin | admin | manager | employee | owner | tenant |
|----------|------------|-------|---------|----------|-------|--------|
| **Users** |
| - View | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| - Create | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| - Edit | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| - Delete | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Employees** |
| - View | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| - Create | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| - Edit | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| - Delete | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Owners** |
| - View | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| - Create | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| - Edit | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| - Delete | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Tenants** |
| - View | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| - Create | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| - Edit | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| - Delete | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## ✅ قائمة المهام

- [x] ~~إنشاء UserPolicy~~ (استخدمنا Resource Methods بدلاً منها)
- [ ] ~~إنشاء EmployeePolicy~~ (سنستخدم Resource Methods)
- [x] تحديث UserResource بالصلاحيات ✅ تم
- [ ] تحديث EmployeeResource بالصلاحيات
- [ ] تحديث OwnerResource بالصلاحيات
- [ ] تحديث TenantResource بالصلاحيات
- [ ] حماية صفحات Edit في كل Resource
- [ ] حماية صفحات Create في كل Resource
- [ ] إخفاء أزرار Actions حسب الصلاحيات
- [ ] اختبار كل الصلاحيات

### 📈 التقدم: 10% مكتمل

---

## 🔍 اختبارات مطلوبة

1. **اختبار الموظف:**
   - [ ] لا يمكنه رؤية قائمة Users
   - [ ] لا يمكنه إضافة موظف جديد
   - [ ] لا يمكنه تعديل أي مستخدم
   - [ ] لا يمكنه الوصول لـ `/admin/users/1/edit`

2. **اختبار المدير:**
   - [ ] يمكنه رؤية الموظفين فقط
   - [ ] لا يمكنه تعديل Users
   - [ ] يمكنه إضافة owners/tenants
   - [ ] لا يمكنه حذف أي شيء

3. **اختبار Admin:**
   - [ ] يمكنه إدارة الموظفين
   - [ ] لا يمكنه تعديل super_admin
   - [ ] يمكنه إدارة owners/tenants

4. **اختبار Super Admin:**
   - [ ] وصول كامل لكل شيء

---

## 📝 ملاحظات مهمة

1. **يجب** تطبيق الصلاحيات في الـ Backend أيضاً (Controllers/API)
2. **يجب** إضافة Middleware للتحقق من الصلاحيات
3. **يجب** تسجيل كل محاولات الوصول غير المصرح بها
4. **يجب** عمل audit log لكل العمليات الحساسة

---

## 🚀 الخطوات التالية

1. تطبيق الحلول في الكود
2. اختبار كل صلاحية
3. مراجعة الـ Backend
4. إضافة logging
5. إضافة 2FA للحسابات الحساسة