# AqarCRM - نظام إدارة العقارات

نظام إدارة العقارات المطور باستخدام Laravel 12 و Filament 4 لإدارة العقارات والوحدات والعقود والمدفوعات.

## المتطلبات التقنية

- PHP 8.4+
- Laravel 12
- Filament 4
- MySQL/MariaDB
- Composer
- Node.js & NPM

## Business Logic - القواعد التجارية

### 1. نظام المستخدمين والأدوار

#### الأدوار الرئيسية:
- **super_admin**: إدارة كاملة للنظام
- **admin**: إدارة عامة
- **employee**: موظف/مدير عقار
- **manager**: إدارة محدودة
- **owner**: مالك العقار
- **tenant**: 

### 2. نظام الدفعات والتوريد

#### الأنواع الستة لدفعات التحصيل وتأثيرها على دفعات التوريد:

يعتمد النظام على تصنيف دفعات التحصيل إلى 6 أنواع بناءً على العلاقة الزمنية بين تاريخ الاستحقاق وتاريخ الدفع وفترة التوريد:

1- دفعة تابعه للفتره ولكنها لم تدفع
2- دفعه تابعه للفتره ودفعت خلال الفتره 

3- دفعة تابعه للفتره ولكنها دفعت قبل الفتره
4- دفعة تابعه للفتره ولكنها دفعت بعد الفتره 


5- دفعة تابعه لفترة سابقة ولكنها دفعت خلال الفتره 
6- دفعة تابعه لفترة لاحفة ولكنها دفعت خلال الفتره

الحساب يكون علي كل الانواع
2 & 3 & 5

**النوع 1: دفعات الفترة غير المدفوعة**
- دفعة استحقت في الفترة ولم تُدفع حتى نهايتها
- **لا تُحتسب للمالك** لأنها لم تُحصل بعد
- مثال: إيجار مارس مستحق ولم يُدفع حتى نهاية مارس
- اللون: أحمر (danger)

**النوع 2: دفعات الفترة المدفوعة خلالها** ✅
- دفعة استحقت في الفترة ودُفعت خلالها
- **تُحتسب للمالك** لأنها حُصلت في نفس الفترة
- مثال: إيجار مارس دُفع في 15 مارس
- اللون: أخضر (success)

**النوع 3: دفعات الفترة المدفوعة مسبقاً** ✅
- دفعة استحقت في الفترة لكن دُفعت قبلها
- **تُحتسب للمالك** في فترة الاستحقاق وليس فترة الدفع
- مثال: إيجار مارس دُفع في فبراير
- اللون: أزرق (info)

**النوع 4: دفعات الفترة المدفوعة متأخراً**
- دفعة استحقت في الفترة ودُفعت بعد انتهائها
- **لا تُحتسب للمالك** في فترة الاستحقاق
- ستُحتسب في فترة الدفع الفعلي كنوع 5
- مثال: إيجار مارس دُفع في أبريل
- اللون: برتقالي (warning)

**النوع 5: متأخرات محصلة** ✅
- دفعة استحقت في فترة سابقة ودُفعت في الفترة الحالية
- **تُحتسب للمالك** في فترة التحصيل الفعلي
- مثال: إيجار فبراير دُفع في مارس
- اللون: أصفر (warning)

**النوع 6: دفعات مستقبلية محصلة مبكراً**
- دفعة استحقت في فترة لاحقة ودُفعت في الفترة الحالية
- **لا تُحتسب للمالك** في فترة التحصيل
- ستُحتسب في فترة الاستحقاق كنوع 3
- مثال: إيجار أبريل دُفع في مارس
- اللون: رمادي (gray)

#### القاعدة الأساسية للحساب:
**المالك يحصل فقط على المبالغ المحصلة فعلياً في الفترة (الأنواع 2، 3، 5)**

#### معادلة حساب دفعة التوريد:
```
إجمالي المحصل = مجموع الدفعات من الأنواع (2 + 3 + 5)
العمولة = إجمالي المحصل × نسبة العمولة
المصروفات = مجموع مصروفات الفترة
صافي المستحق للمالك = إجمالي المحصل - العمولة - المصروفات
```

#### أمثلة عملية:

**مثال 1: فبراير ومارس**
- إيجار فبراير (1000 ريال) دُفع في مارس
- إيجار مارس (1000 ريال) دُفع في مارس

دفعة توريد فبراير:
- الأنواع المحتسبة: لا شيء (0 ريال)
- النوع 4: إيجار فبراير متأخر (غير محتسب)

دفعة توريد مارس:
- النوع 5: إيجار فبراير (1000 ريال) ✅
- النوع 2: إيجار مارس (1000 ريال) ✅
- إجمالي المحتسب: 2000 ريال

**مثال 2: الدفع المبكر**
- إيجار أبريل (1500 ريال) دُفع في مارس

دفعة توريد مارس:
- النوع 6: إيجار أبريل (غير محتسب)

دفعة توريد أبريل:
- النوع 3: إيجار أبريل (1500 ريال) ✅

#### خوارزمية التصنيف:
**المرجع**: `getPaymentTypeForPeriod()` في `app/Services/PaymentAssignmentService.php`

#### خوارزمية الاحتساب:
**المرجع**: `shouldPaymentBelongToPeriod()` في `app/Services/PaymentAssignmentService.php`

### 3. نظام العقود

#### أنواع العقود:
- **PropertyContract**: عقد مع المالك لإدارة العقار
- **UnitContract**: عقد إيجار مع المستأجر

#### حالات العقود:
- **draft**: مسودة
- **active**: فعال
- **suspended**: معلق
- **expired**: منتهي الصلاحية
- **terminated**: مُنهى

#### قواعد العقود:
- العقد يجب أن يكون فعال لإنشاء دفعات
- لا يمكن حذف عقد له دفعات مرتبطة
- العقد المنتهي لا يمكن تعديله
- تجديد العقد ينشئ عقد جديد بنفس الشروط

### 4. نظام العقارات والوحدات

#### هيكل البيانات الهرمي:
```
العقار (Property)
├── الوحدات (Units)
├── العقود (PropertyContract)
└── المصروفات (Expenses)

الوحدة (Unit)
├── عقود الإيجار (UnitContracts)
├── المستأجرين (Tenants)
└── دفعات التحصيل (CollectionPayments)
```

#### قواعد الإشغال:
- الوحدة لا يمكن أن يكون لها أكثر من عقد فعال
- حالة الوحدة تحدد توفرها للإيجار

#### حساب معدل الإشغال:
**المرجع**: `getOccupancyRateAttribute()` في `app/Models/Property.php`

### 5. النظام المالي

#### أنواع المدفوعات:
- **CollectionPayment**: دفعة محصلة من المستأجر
- **SupplyPayment**: دفعة موردة للمالك

#### حساب دفعة التوريد:
**المرجع**: `calculateAmountsFromPeriod()` في `app/Models/SupplyPayment.php`

#### معادلة العمولة:
**المرجع**: `calculateAmountsFromPeriod()` في `app/Models/SupplyPayment.php`

#### قواعد المصروفات:
- مصروفات العقار تُحسم من كل دفعات التوريد
- مصروفات الوحدة تُحسم من دفعات تلك الوحدة فقط
- المصروفات تُحسب حسب تاريخ حدوثها وليس تاريخ الدفع

### 6. نظام المواقع

#### التسلسل الهرمي:
1. **المنطقة** (Level 1)
2. **المدينة** (Level 2)
3. **المركز/الحي** (Level 3)
4. **الشارع/الحي الفرعي** (Level 4)

#### قواعد المواقع:
- كل موقع له موقع أب (ما عدا المستوى الأول)
- المسار الكامل يُحفظ لسرعة البحث
- الإحداثيات اختيارية ولكن مفضلة للعقارات

### 7. قواعد التحقق والأمان

#### التحقق من البيانات:
- الهوية الوطنية فريدة لكل مستخدم
- رقم العقد فريد لكل نوع عقد
- تواريخ العقد منطقية (البداية قبل النهاية)

#### الأمان:
- كلمات المرور مُشفرة
- الصلاحيات محددة بدقة
- تسجيل العمليات الحساسة
- حماية من CSRF و XSS

### 8. قواعد التقارير والإحصائيات

#### مؤشرات الأداء:
- معدل الإشغال الإجمالي
- الدخل الشهري لكل عقار
- متوسط فترة البقاء للمستأجرين
- معدل المصروفات للدخل

#### التقارير المالية:
- كشف حساب المالك
- تقرير التحصيلات الشهرية
- تقرير المصروفات
- تقرير الأرباح والخسائر

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
