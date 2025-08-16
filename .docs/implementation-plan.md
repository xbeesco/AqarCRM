# 📋 خطة التنفيذ التفصيلية - نظام إدارة العقارات

## 🎯 المرحلة 1: البنية الأساسية والبيانات المرجعية

### 1.1 إعداد الجداول الأساسية
#### 1.1.1 جدول أنواع العقارات (property_types)
**المتطلبات التقنية:**
```php
// Migration
- id, name_ar, name_en, slug, icon, description, is_active, sort_order, timestamps

// Model: PropertyType
- Relations: hasMany(Property::class)
- Scopes: active(), ordered()
- Attributes: getNameAttribute() // based on locale

// Seeder: PropertyTypeSeeder
- Data: فيلا، شقة، محل تجاري، مكتب، مستودع، أرض
```

**الاختبارات المطلوبة:**
```php
// Unit Tests
- test_property_type_has_required_fields()
- test_property_type_name_localization()
- test_property_type_slug_generation()
- test_property_type_active_scope()

// Integration Tests
- test_property_type_seeder_creates_default_types()
- test_property_type_can_be_created_via_api()
- test_property_type_relationships_with_properties()
```

#### 1.1.2 جدول حالات العقارات (property_statuses)
**المتطلبات التقنية:**
```php
// Migration
- id, name_ar, name_en, slug, color, icon, is_active, timestamps

// Model: PropertyStatus
- Relations: hasMany(Property::class)
- Methods: getBadgeColorAttribute(), canTransitionTo($status)

// Seeder: PropertyStatusSeeder
- Data: متاح، مؤجر، قيد الصيانة، محجوز، غير متاح
```

**الاختبارات المطلوبة:**
```php
// Unit Tests
- test_property_status_color_validation()
- test_property_status_transition_rules()
- test_property_status_badge_rendering()

// Integration Tests
- test_property_status_workflow_transitions()
- test_property_status_affects_availability()
```

#### 1.1.3 جدول مميزات العقارات (property_features)
**المتطلبات التقنية:**
```php
// Migration
- id, name_ar, name_en, category, icon, requires_value, value_type, timestamps

// Pivot Table: property_feature_property
- property_id, feature_id, value, timestamps

// Model: PropertyFeature
- Relations: belongsToMany(Property::class)->withPivot('value')
- Methods: getFormattedValueAttribute()

// Seeder: PropertyFeatureSeeder
- Categories: أساسيات، مرافق، أمان، إضافات
- Features: مصعد، موقف سيارات، حديقة، مسبح، نظام أمني
```

**الاختبارات المطلوبة:**
```php
// Unit Tests
- test_feature_value_type_validation()
- test_feature_category_grouping()
- test_feature_value_formatting()

// Integration Tests
- test_property_can_attach_multiple_features()
- test_feature_values_are_validated_by_type()
- test_feature_filtering_in_property_search()
```

### 1.2 إعداد نظام المواقع الهرمي
#### 1.2.1 تحسين جدول المواقع (locations)
**المتطلبات التقنية:**
```php
// Migration Updates
- Add: level (1-4), path, coordinates (lat, lng), postal_code
- Add indexes: parent_id, level, path

// Model: Location
- Traits: HasRecursiveRelationships
- Methods: 
  - getFullPathAttribute() // منطقة > مدينة > مركز > حي
  - getChildrenAttribute()
  - getBreadcrumbsAttribute()
- Scopes: byLevel($level), withChildren(), roots()

// Service: LocationService
- Methods:
  - buildHierarchyTree()
  - findByPath($path)
  - getLocationOptions($level, $parentId)
```

**الاختبارات المطلوبة:**
```php
// Unit Tests
- test_location_hierarchy_validation()
- test_location_path_generation()
- test_location_level_constraints()
- test_location_coordinates_validation()

// Integration Tests
- test_location_tree_building()
- test_location_cascade_operations()
- test_location_search_by_path()
- test_location_api_filtering()
```

## 🎯 المرحلة 2: إدارة المستخدمين

### 2.1 نظام الصلاحيات والأدوار
#### 2.1.1 تكوين Spatie Permissions
**المتطلبات التقنية:**
```php
// Roles
- super_admin, admin, manager, owner, tenant, maintenance_staff

// Permissions (grouped)
- properties.*: view, create, edit, delete, export
- units.*: view, create, edit, delete, assign
- contracts.*: view, create, edit, delete, approve
- payments.*: view, create, edit, mark_paid, export
- reports.*: view, export, financial, occupancy

// Service: RolePermissionService
- Methods:
  - syncRolePermissions($role, $permissions)
  - getUserPermissionMatrix($user)
  - checkPermissionDependencies($permission)
```

**الاختبارات المطلوبة:**
```php
// Unit Tests
- test_role_has_correct_permissions()
- test_permission_inheritance()
- test_permission_dependencies()

// Integration Tests  
- test_user_can_perform_allowed_actions()
- test_user_cannot_perform_restricted_actions()
- test_role_switching_updates_permissions()
```

### 2.2 إدارة الملاك
#### 2.2.1 نموذج وخدمات المالك
**المتطلبات التقنية:**
```php
// Model Updates: User (when role = owner)
- Additional fields: national_id, commercial_register, tax_number, bank_account
- Relations: hasMany(Property::class, 'owner_id')
- Scopes: owners(), activeOwners()

// Service: OwnerService
- Methods:
  - createOwner($data)
  - getOwnerPortfolio($ownerId)
  - calculateOwnerRevenue($ownerId, $period)
  - getOwnerStatement($ownerId, $dateRange)

// Repository: OwnerRepository
- Methods:
  - findWithProperties($id)
  - getOwnersWithActiveContracts()
  - getOwnerPaymentHistory($id)
```

**الاختبارات المطلوبة:**
```php
// Unit Tests
- test_owner_validation_rules()
- test_owner_bank_account_format()
- test_owner_national_id_uniqueness()

// Integration Tests
- test_owner_creation_assigns_correct_role()
- test_owner_portfolio_calculations()
- test_owner_statement_generation()
- test_owner_property_relationship()
```

### 2.3 إدارة المستأجرين
#### 2.3.1 نموذج وخدمات المستأجر
**المتطلبات التقنية:**
```php
// Model Updates: User (when role = tenant)
- Additional fields: national_id, occupation, employer, emergency_contact
- Relations: hasMany(UnitContract::class, 'tenant_id')
- Methods: getCurrentUnit(), getPaymentHistory()

// Service: TenantService  
- Methods:
  - createTenant($data)
  - assignToUnit($tenantId, $unitId)
  - getTenantLedger($tenantId)
  - checkTenantEligibility($tenantId)

// Events
- TenantCreated, TenantAssigned, TenantRemoved
```

**الاختبارات المطلوبة:**
```php
// Unit Tests
- test_tenant_validation_rules()
- test_tenant_current_unit_detection()
- test_tenant_eligibility_checks()

// Integration Tests
- test_tenant_unit_assignment_workflow()
- test_tenant_payment_history_accuracy()
- test_tenant_contract_relationships()
- test_tenant_events_are_fired()
```

## 🎯 المرحلة 3: إدارة الأصول

### 3.1 نظام إدارة العقارات
#### 3.1.1 نموذج العقار المحسن
**المتطلبات التقنية:**
```php
// Model: Property
- Additional fields: 
  - coordinates, area_sqm, build_year, floors_count
  - has_elevator, parking_spots, garden_area
- Relations:
  - belongsToMany(PropertyFeature::class)->withPivot('value')
  - morphMany(Media::class, 'mediable')
- Methods:
  - getOccupancyRateAttribute()
  - getMonthlyRevenueAttribute()
  - getTotalUnitsAttribute()

// Service: PropertyService
- Methods:
  - createPropertyWithFeatures($data, $features)
  - updatePropertyStatus($id, $status)
  - calculatePropertyMetrics($id)
  - generatePropertyReport($id, $period)

// Repository: PropertyRepository
- Methods:
  - findWithFullDetails($id)
  - searchProperties($filters)
  - getNearbyProperties($lat, $lng, $radius)
```

**الاختبارات المطلوبة:**
```php
// Unit Tests
- test_property_area_validation()
- test_property_coordinate_format()
- test_property_occupancy_calculation()
- test_property_revenue_calculation()

// Integration Tests
- test_property_creation_with_features()
- test_property_status_transitions()
- test_property_search_filters()
- test_property_media_attachment()
- test_property_metrics_accuracy()
```

### 3.2 نظام إدارة الوحدات
#### 3.2.1 نموذج الوحدة المحسن
**المتطلبات التقنية:**
```php
// Model: Unit
- Additional fields:
  - floor_number, unit_number, area_sqm, rooms_count
  - bathrooms_count, has_balcony, view_type, furnished
- Relations:
  - belongsToMany(UnitFeature::class)
  - hasOne(ActiveUnitContract::class)
- Methods:
  - isAvailable()
  - getCurrentTenant()
  - getNextAvailableDate()

// Service: UnitService
- Methods:
  - checkUnitAvailability($id, $dateRange)
  - assignTenant($unitId, $tenantId, $contractData)
  - releaseUnit($unitId, $reason)
  - calculateUnitPricing($unitId, $duration)

// Observer: UnitObserver
- Events: creating, updating, deleting
- Actions: Update property metrics, sync availability
```

**الاختبارات المطلوبة:**
```php
// Unit Tests
- test_unit_number_uniqueness_per_property()
- test_unit_availability_logic()
- test_unit_pricing_calculation()
- test_unit_floor_validation()

// Integration Tests
- test_unit_tenant_assignment_workflow()
- test_unit_availability_calendar()
- test_unit_observer_updates_property_metrics()
- test_unit_contract_relationship()
```

## 🎯 المرحلة 4: إدارة العقود

### 4.1 عقود الملاك
#### 4.1.1 نظام عقود الملكية
**المتطلبات التقنية:**
```php
// Model: PropertyContract
- Additional fields:
  - contract_number, notary_number, commission_rate
  - payment_day, auto_renew, notice_period
- States: draft, active, suspended, expired, terminated
- Methods:
  - generateContractNumber()
  - calculateCommission($amount)
  - checkRenewalEligibility()

// Service: PropertyContractService
- Methods:
  - createContract($data)
  - activateContract($id)
  - renewContract($id, $duration)
  - terminateContract($id, $reason)
  - generatePaymentSchedule($contractId)

// Jobs
- CheckContractExpiry (daily)
- GenerateMonthlyInvoices
- SendRenewalNotifications
```

**الاختبارات المطلوبة:**
```php
// Unit Tests
- test_contract_number_generation_uniqueness()
- test_contract_commission_calculation()
- test_contract_state_transitions()
- test_contract_renewal_eligibility()

// Integration Tests
- test_contract_activation_workflow()
- test_contract_payment_schedule_generation()
- test_contract_expiry_notifications()
- test_contract_auto_renewal_process()
```

### 4.2 عقود المستأجرين
#### 4.2.1 نظام عقود الإيجار
**المتطلبات التقنية:**
```php
// Model: UnitContract
- Additional fields:
  - security_deposit, utilities_included, payment_method
  - grace_period, late_fee_rate, evacuation_notice
- Relations:
  - hasMany(ContractAddendum::class)
  - morphMany(Document::class, 'documentable')
- Methods:
  - calculateLateFees($daysLate)
  - getRemainingDaysAttribute()
  - canTerminateEarly()

// Service: UnitContractService
- Methods:
  - createLeaseContract($data)
  - addAddendum($contractId, $addendumData)
  - processEarlyTermination($id, $reason)
  - calculateRefund($contractId)

// Notifications
- ContractSignedNotification
- PaymentDueNotification
- ContractExpiryNotification
```

**الاختبارات المطلوبة:**
```php
// Unit Tests
- test_lease_deposit_validation()
- test_late_fee_calculation()
- test_early_termination_penalties()
- test_contract_addendum_validation()

// Integration Tests
- test_lease_creation_workflow()
- test_payment_schedule_with_late_fees()
- test_contract_document_attachment()
- test_notification_sending()
```

## 🎯 المرحلة 5: النظام المالي

### 5.1 نظام المدفوعات
#### 5.1.1 دفعات التحصيل
**المتطلبات التقنية:**
```php
// Model: CollectionPayment
- Additional fields:
  - invoice_number, payment_reference, payment_channel
  - discount_amount, tax_amount, notes
- States: pending, due, paid, partial, overdue, cancelled
- Methods:
  - generateInvoice()
  - applyDiscount($amount, $reason)
  - recordPartialPayment($amount)

// Service: CollectionService
- Methods:
  - processPayment($paymentId, $paymentData)
  - bulkCollect($paymentIds)
  - generateReceipt($paymentId)
  - reconcilePayments($bankStatementData)

// Queue Jobs
- SendPaymentReminders
- UpdateOverdueStatuses
- GenerateMonthlyStatements
```

**الاختبارات المطلوبة:**
```php
// Unit Tests
- test_invoice_number_generation()
- test_payment_state_machine()
- test_discount_application_rules()
- test_partial_payment_tracking()

// Integration Tests
- test_payment_processing_workflow()
- test_bulk_collection_process()
- test_payment_reconciliation()
- test_overdue_status_updates()
- test_receipt_generation()
```

#### 5.1.2 دفعات التوريد
**المتطلبات التقنية:**
```php
// Model: SupplyPayment
- Additional fields:
  - bank_transfer_reference, deduction_details (JSON)
  - approval_status, approved_by, approved_at
- Methods:
  - calculateNetAmount()
  - getDeductionBreakdown()
  - requiresApproval()

// Service: SupplyService
- Methods:
  - calculateOwnerPayment($contractId, $month)
  - applyDeductions($paymentId, $deductions)
  - processApproval($paymentId, $approverId)
  - executeBankTransfer($paymentId)

// Reports
- OwnerPaymentStatement
- DeductionSummaryReport
- BankTransferReport
```

**الاختبارات المطلوبة:**
```php
// Unit Tests
- test_net_amount_calculation()
- test_deduction_validation()
- test_approval_workflow()
- test_bank_transfer_validation()

// Integration Tests
- test_owner_payment_calculation_accuracy()
- test_deduction_application_process()
- test_approval_notification_flow()
- test_bank_transfer_execution()
```

### 5.2 نظام الصيانة والمصروفات
#### 5.2.1 إدارة الصيانة
**المتطلبات التقنية:**
```php
// Model: PropertyRepair
- Additional fields:
  - priority, assigned_to, scheduled_date, completion_date
  - vendor_id, warranty_claim, recurring_schedule
- States: reported, scheduled, in_progress, completed, cancelled
- Methods:
  - isUnderWarranty()
  - calculateCostImpact()
  - scheduleNextMaintenance()

// Service: MaintenanceService
- Methods:
  - createMaintenanceRequest($data)
  - assignToVendor($repairId, $vendorId)
  - trackProgress($repairId, $status, $notes)
  - processWarrantyClaim($repairId)

// Scheduler
- PreventiveMaintenanceScheduler
- MaintenanceReminderJob
```

**الاختبارات المطلوبة:**
```php
// Unit Tests
- test_repair_priority_levels()
- test_warranty_validation()
- test_cost_impact_calculation()
- test_recurring_schedule_generation()

// Integration Tests
- test_maintenance_request_workflow()
- test_vendor_assignment_process()
- test_warranty_claim_processing()
- test_preventive_maintenance_scheduling()
```

## 🎯 المرحلة 6: الاستيراد والتكامل

### 6.1 نظام استيراد البيانات
#### 6.1.1 محرك الاستيراد الرئيسي
**المتطلبات التقنية:**
```php
// Command: ImportFromWordPress
- Options: --type, --batch-size, --dry-run, --force
- Methods:
  - validateConnection()
  - mapDataStructure()
  - processInBatches()
  - handleErrors()

// Service: DataImportService
- Methods:
  - connectToWordPress()
  - extractData($type, $criteria)
  - transformData($data, $mapping)
  - loadData($transformedData)
  - validateImport($type)

// Mappers (one for each entity)
- OwnerMapper, TenantMapper, PropertyMapper
- UnitMapper, ContractMapper, PaymentMapper

// ImportLog Model
- Fields: type, status, records_total, records_imported
  errors (JSON), started_at, completed_at
```

**الاختبارات المطلوبة:**
```php
// Unit Tests
- test_wordpress_connection()
- test_data_extraction_queries()
- test_data_transformation_rules()
- test_validation_rules()

// Integration Tests
- test_full_import_cycle()
- test_duplicate_detection()
- test_rollback_on_failure()
- test_incremental_import()
- test_data_integrity_after_import()
```

### 6.2 التحقق من البيانات
#### 6.2.1 نظام التحقق والتدقيق
**المتطلبات التقنية:**
```php
// Service: DataValidationService
- Methods:
  - validateUserIntegrity()
  - validatePropertyRelations()
  - validateFinancialRecords()
  - generateValidationReport()

// Validators
- UserValidator: Check roles, permissions, duplicates
- PropertyValidator: Check owners, locations, features
- ContractValidator: Check dates, amounts, relations
- PaymentValidator: Check calculations, statuses

// Reports
- DataIntegrityReport
- MissingDataReport
- DuplicateRecordsReport
```

**الاختبارات المطلوبة:**
```php
// Unit Tests
- test_user_integrity_checks()
- test_property_relation_validation()
- test_financial_calculation_validation()

// Integration Tests
- test_full_system_data_validation()
- test_report_generation_accuracy()
- test_duplicate_detection_algorithm()
- test_missing_data_identification()
```

## 📊 جدول زمني تقديري

| المرحلة | المدة | الأولوية |
|---------|-------|----------|
| المرحلة 1: البنية الأساسية | 1-2 أسابيع | عالية جداً |
| المرحلة 2: إدارة المستخدمين | 1-2 أسابيع | عالية |
| المرحلة 3: إدارة الأصول | 2-3 أسابيع | عالية |
| المرحلة 4: إدارة العقود | 2-3 أسابيع | متوسطة |
| المرحلة 5: النظام المالي | 3-4 أسابيع | عالية |
| المرحلة 6: الاستيراد والتكامل | 1-2 أسابيع | حرجة |

## 🔧 أدوات الاختبار المطلوبة

```bash
# PHPUnit للاختبارات الوحدة
composer require --dev phpunit/phpunit

# Pest للاختبارات الأكثر تعبيرية
composer require --dev pestphp/pest

# Laravel Dusk للاختبارات الطرفية
composer require --dev laravel/dusk

# Factories and Seeders للبيانات التجريبية
php artisan make:factory PropertyFactory
php artisan make:seeder TestDataSeeder

# Coverage Reports
./vendor/bin/phpunit --coverage-html reports/
```

## ✅ معايير القبول لكل مرحلة

1. **تغطية الاختبارات**: 80% كحد أدنى
2. **اختبارات الوحدة**: جميع الدوال الحرجة
3. **اختبارات التكامل**: جميع مسارات العمل
4. **التوثيق**: PHPDoc لجميع الكلاسات والدوال
5. **الأداء**: استجابة أقل من 200ms للـ API
6. **الأمان**: تطبيق OWASP Top 10

## 📌 ملاحظات التنفيذ

### الأولويات الحرجة:
1. **البيانات المرجعية أولاً** - لا يمكن البدء بأي شيء بدونها
2. **المستخدمون ثانياً** - الملاك والمستأجرون أساس النظام
3. **الاستيراد أخيراً** - بعد التأكد من صحة الهيكل الجديد

### نقاط الانتباه:
- التأكد من دعم اللغة العربية في جميع الحقول
- الحفاظ على تكامل البيانات المالية
- توثيق جميع التغييرات عن النظام القديم
- إجراء اختبارات الأداء قبل الاستيراد الكامل

### المخاطر المحتملة:
- تعقيد عملية الاستيراد من WordPress
- احتمالية فقدان بعض البيانات التاريخية
- الحاجة لإعادة تدريب المستخدمين

## 🚀 الخطوات التالية

1. مراجعة واعتماد الخطة
2. إعداد بيئة التطوير
3. البدء بالمرحلة الأولى
4. إجراء مراجعات دورية كل أسبوع
5. التحقق من معايير القبول قبل الانتقال للمرحلة التالية