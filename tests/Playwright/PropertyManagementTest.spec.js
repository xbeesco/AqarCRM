import { test, expect } from '@playwright/test';

// Test credentials
const ADMIN_EMAIL = 'admin@aqarcrm.com';
const ADMIN_PASSWORD = 'password123';

// Base URL
const BASE_URL = 'http://127.0.0.1:8000';

test.describe('Property Management System Tests', () => {
  
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await page.goto(`${BASE_URL}/admin/login`);
    await page.fill('input[type="email"]', ADMIN_EMAIL);
    await page.fill('input[type="password"]', ADMIN_PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL(`${BASE_URL}/admin`);
  });

  test.describe('Property Types Management Tests', () => {
    
    test('should access property types page as simple resource (modal)', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      await expect(page).toHaveTitle(/أنواع العقارات/);
      
      // Check table is displayed
      await expect(page.locator('table')).toBeVisible();
      
      // Check filters are above table
      const filtersSection = page.locator('[data-filament-table-filters]');
      await expect(filtersSection).toBeVisible();
      
      // Check that this is a simple resource (should have manage page, not separate create/edit pages)
      await expect(page.locator('a[href*="property-types/create"]')).not.toBeVisible();
      
      // Should have create button that opens modal
      await expect(page.locator('button:has-text("إنشاء"), button:has-text("Create")')).toBeVisible();
    });

    test('should display seeded property types', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      
      // Check for seeded property types
      const propertyTypes = ['فيلا', 'شقة', 'محل تجاري', 'مكتب', 'مستودع', 'أرض', 'عمارة'];
      
      for (const type of propertyTypes) {
        await expect(page.locator('table').getByText(type)).toBeVisible();
      }
    });

    test('should create new property type via modal', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      
      // Click create button (should open modal)
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      
      // Check that modal is opened
      await expect(page.locator('.filament-modal, [role="dialog"]')).toBeVisible();
      
      // Fill form in modal
      await page.fill('input[name="data.name_ar"]', 'شاليه');
      await page.fill('input[name="data.name_en"]', 'Chalet');
      await page.fill('textarea[name="data.description_ar"]', 'شاليه للإيجار');
      await page.fill('textarea[name="data.description_en"]', 'Chalet for rent');
      await page.fill('input[name="data.sort_order"]', '8');
      
      // Submit form
      await page.click('button[type="submit"]');
      
      // Check success notification
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Modal should close and item should appear in table
      await expect(page.locator('.filament-modal, [role="dialog"]')).not.toBeVisible();
      await expect(page.locator('table').getByText('شاليه')).toBeVisible();
    });

    test('should edit property type via modal', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      
      // Click edit on first item (should open modal)
      await page.click('table tbody tr:first-child button[title="تعديل"], table tbody tr:first-child a[href*="/edit"]');
      
      // Check that modal is opened
      await expect(page.locator('.filament-modal, [role="dialog"]')).toBeVisible();
      
      // Update description
      const newDescription = 'وصف محدث للاختبار';
      await page.fill('textarea[name="data.description_ar"]', newDescription);
      
      // Submit
      await page.click('button[type="submit"]');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Modal should close
      await expect(page.locator('.filament-modal, [role="dialog"]')).not.toBeVisible();
    });

    test('should delete property type', async ({ page }) => {
      // First create a test item to delete
      await page.goto(`${BASE_URL}/admin/property-types`);
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      await page.fill('input[name="data.name_ar"]', 'نوع للحذف');
      await page.fill('input[name="data.name_en"]', 'Type to Delete');
      await page.click('button[type="submit"]');
      
      // Find the row with our test item and click delete
      const row = page.locator('table tbody tr').filter({ hasText: 'نوع للحذف' });
      await row.locator('button[title="حذف"]').click();
      
      // Confirm deletion
      await page.click('button:has-text("تأكيد")');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحذف');
      
      // Verify deleted
      await expect(page.locator('table').getByText('نوع للحذف')).not.toBeVisible();
    });

    test('should validate required fields', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      
      // Click create button
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      
      // Try to submit without filling required fields
      await page.click('button[type="submit"]');
      
      // Check validation errors
      await expect(page.locator('.filament-form-field-error')).toContainText('مطلوب');
    });

    test('should filter property types', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      
      // Open filters
      const filterButton = page.locator('button:has-text("فلترة")');
      if (await filterButton.isVisible()) {
        await filterButton.click();
        
        // Select active filter
        await page.selectOption('select[name="tableFilters[is_active][value]"]', '1');
        
        // Apply filter
        await page.click('button:has-text("تطبيق")');
        
        // Check that table is filtered
        await expect(page.locator('table')).toBeVisible();
      }
    });
  });

  test.describe('Property Statuses Management Tests', () => {
    
    test('should access property statuses page as simple resource (modal)', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-statuses`);
      await expect(page).toHaveTitle(/حالات العقارات/);
      
      // Check table is displayed
      await expect(page.locator('table')).toBeVisible();
      
      // Check filters are above table
      const filtersSection = page.locator('[data-filament-table-filters]');
      await expect(filtersSection).toBeVisible();
      
      // Should be simple resource with modal
      await expect(page.locator('button:has-text("إنشاء"), button:has-text("Create")')).toBeVisible();
    });

    test('should display seeded property statuses', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-statuses`);
      
      // Check for seeded statuses
      const statuses = ['متاح', 'مؤجر', 'قيد الصيانة', 'محجوز', 'غير متاح'];
      
      for (const status of statuses) {
        await expect(page.locator('table').getByText(status)).toBeVisible();
      }
    });

    test('should create new property status via modal', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-statuses`);
      
      // Click create button
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      
      // Check that modal is opened
      await expect(page.locator('.filament-modal, [role="dialog"]')).toBeVisible();
      
      // Fill form
      await page.fill('input[name="data.name_ar"]', 'قيد البناء');
      await page.fill('input[name="data.name_en"]', 'Under Construction');
      await page.fill('input[name="data.color"]', '#FFA500');
      await page.fill('input[name="data.icon"]', 'heroicon-o-wrench');
      
      // Submit
      await page.click('button[type="submit"]');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Verify in table
      await expect(page.locator('table').getByText('قيد البناء')).toBeVisible();
    });

    test('should edit property status via modal', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-statuses`);
      
      // Click edit on first status
      await page.click('table tbody tr:first-child button[title="تعديل"], table tbody tr:first-child a[href*="/edit"]');
      
      // Check that modal is opened
      await expect(page.locator('.filament-modal, [role="dialog"]')).toBeVisible();
      
      // Update color
      await page.fill('input[name="data.color"]', '#FF0000');
      
      // Submit
      await page.click('button[type="submit"]');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    });

    test('should delete property status', async ({ page }) => {
      // First create a test status
      await page.goto(`${BASE_URL}/admin/property-statuses`);
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      await page.fill('input[name="data.name_ar"]', 'حالة للحذف');
      await page.fill('input[name="data.name_en"]', 'Status to Delete');
      await page.fill('input[name="data.color"]', '#000000');
      await page.click('button[type="submit"]');
      
      // Delete the created status
      const row = page.locator('table tbody tr').filter({ hasText: 'حالة للحذف' });
      await row.locator('button[title="حذف"]').click();
      
      // Confirm deletion
      await page.click('button:has-text("تأكيد")');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحذف');
    });

    test('should validate status color field', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-statuses`);
      
      // Click create button
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      
      // Fill name but not color
      await page.fill('input[name="data.name_ar"]', 'اختبار');
      await page.fill('input[name="data.name_en"]', 'Test');
      
      // Try to submit without color
      await page.click('button[type="submit"]');
      
      // Check validation for color field
      await expect(page.locator('.filament-form-field-error')).toBeVisible();
    });

    test('should display status badge colors correctly', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-statuses`);
      
      // Check that status badges are displayed with colors
      const statusBadges = page.locator('table .filament-badge');
      const count = await statusBadges.count();
      
      if (count > 0) {
        // Check that badges have styles/colors
        const firstBadge = statusBadges.first();
        await expect(firstBadge).toBeVisible();
        
        // Badge should have color styling
        const style = await firstBadge.getAttribute('style');
        const className = await firstBadge.getAttribute('class');
        expect(style || className).toBeTruthy();
      }
    });
  });

  test.describe('Property Features Management Tests', () => {
    
    test('should access property features page as simple resource (modal)', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-features`);
      await expect(page).toHaveTitle(/مميزات العقارات/);
      
      // Check table is displayed
      await expect(page.locator('table')).toBeVisible();
      
      // Check filters are above table
      const filtersSection = page.locator('[data-filament-table-filters]');
      await expect(filtersSection).toBeVisible();
      
      // Should be simple resource with modal
      await expect(page.locator('button:has-text("إنشاء"), button:has-text("Create")')).toBeVisible();
    });

    test('should display features by category', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-features`);
      
      // Check for categories
      const categories = ['أساسيات', 'مرافق', 'أمان', 'إضافات'];
      
      for (const category of categories) {
        await expect(page.locator('table').getByText(category)).toBeVisible();
      }
    });

    test('should create new property feature via modal', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-features`);
      
      // Click create button
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      
      // Check that modal is opened
      await expect(page.locator('.filament-modal, [role="dialog"]')).toBeVisible();
      
      // Fill form
      await page.fill('input[name="data.name_ar"]', 'صالة رياضية');
      await page.fill('input[name="data.name_en"]', 'Gym');
      await page.selectOption('select[name="data.category"]', 'amenities');
      await page.fill('input[name="data.icon"]', 'heroicon-o-heart');
      
      // Enable requires value
      await page.check('input[name="data.requires_value"]');
      await page.selectOption('select[name="data.value_type"]', 'number');
      
      // Submit
      await page.click('button[type="submit"]');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Verify in table
      await expect(page.locator('table').getByText('صالة رياضية')).toBeVisible();
    });

    test('should edit property feature via modal', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-features`);
      
      // Click edit on first feature
      await page.click('table tbody tr:first-child button[title="تعديل"], table tbody tr:first-child a[href*="/edit"]');
      
      // Check that modal is opened
      await expect(page.locator('.filament-modal, [role="dialog"]')).toBeVisible();
      
      // Update description
      await page.fill('textarea[name="data.description_ar"]', 'وصف محدث');
      
      // Submit
      await page.click('button[type="submit"]');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    });

    test('should delete property feature', async ({ page }) => {
      // First create a test feature
      await page.goto(`${BASE_URL}/admin/property-features`);
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      await page.fill('input[name="data.name_ar"]', 'ميزة للحذف');
      await page.fill('input[name="data.name_en"]', 'Feature to Delete');
      await page.selectOption('select[name="data.category"]', 'basics');
      await page.click('button[type="submit"]');
      
      // Delete the created feature
      const row = page.locator('table tbody tr').filter({ hasText: 'ميزة للحذف' });
      await row.locator('button[title="حذف"]').click();
      
      // Confirm deletion
      await page.click('button:has-text("تأكيد")');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحذف');
    });

    test('should filter features by category', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-features`);
      
      // Open filters
      const filterButton = page.locator('button:has-text("فلترة")');
      if (await filterButton.isVisible()) {
        await filterButton.click();
        
        // Select category filter
        await page.selectOption('select[name="tableFilters[category][value]"]', 'basics');
        
        // Apply filter
        await page.click('button:has-text("تطبيق")');
        
        // Check filtered results
        await expect(page.locator('table').getByText('أساسيات')).toBeVisible();
      }
    });

    test('should validate feature value type selection', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-features`);
      
      // Click create button
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      
      // Fill basic info
      await page.fill('input[name="data.name_ar"]', 'ميزة اختبار');
      await page.fill('input[name="data.name_en"]', 'Test Feature');
      await page.selectOption('select[name="data.category"]', 'basics');
      
      // Enable requires value
      await page.check('input[name="data.requires_value"]');
      
      // Try to submit without selecting value type
      await page.click('button[type="submit"]');
      
      // Check validation
      await expect(page.locator('.filament-form-field-error')).toBeVisible();
    });

    test('should handle feature value types correctly', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-features`);
      
      // Create feature with number value type
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      await page.fill('input[name="data.name_ar"]', 'عدد الأدوار');
      await page.fill('input[name="data.name_en"]', 'Number of Floors');
      await page.selectOption('select[name="data.category"]', 'basics');
      await page.check('input[name="data.requires_value"]');
      await page.selectOption('select[name="data.value_type"]', 'number');
      await page.click('button[type="submit"]');
      
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Create feature with text value type
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      await page.fill('input[name="data.name_ar"]', 'نوع التشطيب');
      await page.fill('input[name="data.name_en"]', 'Finishing Type');
      await page.selectOption('select[name="data.category"]', 'basics');
      await page.check('input[name="data.requires_value"]');
      await page.selectOption('select[name="data.value_type"]', 'text');
      await page.click('button[type="submit"]');
      
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Create feature with boolean value type (checkbox)
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      await page.fill('input[name="data.name_ar"]', 'مفروش');
      await page.fill('input[name="data.name_en"]', 'Furnished');
      await page.selectOption('select[name="data.category"]', 'basics');
      await page.check('input[name="data.requires_value"]');
      await page.selectOption('select[name="data.value_type"]', 'boolean');
      await page.click('button[type="submit"]');
      
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    });
  });

  test.describe('Complex Property Management Workflows', () => {
    
    test('should handle property type hierarchy correctly', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      
      // Create parent type
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      await page.fill('input[name="data.name_ar"]', 'عقار سكني');
      await page.fill('input[name="data.name_en"]', 'Residential Property');
      await page.click('button[type="submit"]');
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Create child type
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      await page.fill('input[name="data.name_ar"]', 'شقة فاخرة');
      await page.fill('input[name="data.name_en"]', 'Luxury Apartment');
      
      // Select the parent we just created
      const parentSelect = page.locator('select[name="data.parent_id"]');
      if (await parentSelect.isVisible()) {
        await parentSelect.selectOption({ label: 'عقار سكني' });
      }
      
      await page.click('button[type="submit"]');
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Verify hierarchy in list
      await expect(page.locator('table').getByText('شقة فاخرة')).toBeVisible();
    });

    test('should handle bulk operations', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      
      // Select multiple items
      const checkboxes = page.locator('table tbody tr input[type="checkbox"]');
      const count = await checkboxes.count();
      
      if (count > 1) {
        await checkboxes.first().check();
        await checkboxes.nth(1).check();
        
        // Check if bulk actions menu appears
        const bulkActions = page.locator('button:has-text("إجراءات مجمعة")');
        if (await bulkActions.isVisible()) {
          await expect(bulkActions).toBeVisible();
        }
      }
    });

    test('should handle search across all property modules', async ({ page }) => {
      const modules = ['property-types', 'property-statuses', 'property-features'];
      
      for (const module of modules) {
        await page.goto(`${BASE_URL}/admin/${module}`);
        
        // Use search functionality
        const searchInput = page.locator('input[placeholder*="بحث"], input[type="search"]');
        if (await searchInput.isVisible()) {
          await searchInput.fill('test');
          await page.keyboard.press('Enter');
          
          // Check search results
          await expect(page.locator('table')).toBeVisible();
        }
      }
    });
  });

  test.describe('Performance and Error Handling', () => {
    
    test('should load property management pages within acceptable time', async ({ page }) => {
      const pages = [
        `${BASE_URL}/admin/property-types`,
        `${BASE_URL}/admin/property-statuses`,
        `${BASE_URL}/admin/property-features`
      ];
      
      for (const pageUrl of pages) {
        const startTime = Date.now();
        await page.goto(pageUrl);
        const loadTime = Date.now() - startTime;
        
        // Page should load within 3 seconds
        expect(loadTime).toBeLessThan(3000);
        await expect(page.locator('table')).toBeVisible();
      }
    });

    test('should handle modal form validation errors gracefully', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      
      // Open create modal
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      
      // Submit empty form
      await page.click('button[type="submit"]');
      
      // Check that validation errors are displayed in modal
      await expect(page.locator('.filament-form-field-error')).toBeVisible();
      
      // Modal should still be open and usable
      await expect(page.locator('.filament-modal, [role="dialog"]')).toBeVisible();
      await page.fill('input[name="data.name_ar"]', 'اختبار');
      await expect(page.locator('input[name="data.name_ar"]')).toHaveValue('اختبار');
    });

    test('should handle duplicate entries correctly', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      
      // Try to create with existing name
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      await page.fill('input[name="data.name_ar"]', 'فيلا');
      await page.fill('input[name="data.name_en"]', 'Villa');
      await page.click('button[type="submit"]');
      
      // Should show validation error for duplicate
      await expect(page.locator('.filament-form-field-error, .filament-notification-title')).toBeVisible();
    });
  });

  test.describe('Accessibility Tests', () => {
    
    test('should have proper modal accessibility', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      
      // Click create button
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      
      // Check modal accessibility
      const modal = page.locator('.filament-modal, [role="dialog"]');
      await expect(modal).toBeVisible();
      
      // Modal should have proper ARIA attributes
      const role = await modal.getAttribute('role');
      expect(role).toBe('dialog');
      
      // Should be able to close modal with Escape key
      await page.keyboard.press('Escape');
      await expect(modal).not.toBeVisible();
    });

    test('should have proper form labels in modals', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-features`);
      
      // Open create modal
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      
      // Check that all form fields have labels
      const inputs = page.locator('.filament-modal input[name^="data."], .filament-modal select[name^="data."], .filament-modal textarea[name^="data."]');
      const count = await inputs.count();
      
      for (let i = 0; i < count; i++) {
        const input = inputs.nth(i);
        const id = await input.getAttribute('id');
        if (id) {
          const label = page.locator(`label[for="${id}"]`);
          await expect(label).toBeVisible();
        }
      }
    });
  });
});

// Critical property management workflows
test.describe('Critical Property Management Workflows', () => {
  
  test('complete property configuration workflow', async ({ page }) => {
    // Login
    await page.goto(`${BASE_URL}/admin/login`);
    await page.fill('input[type="email"]', ADMIN_EMAIL);
    await page.fill('input[type="password"]', ADMIN_PASSWORD);
    await page.click('button[type="submit"]');
    
    // 1. Create property type
    await page.goto(`${BASE_URL}/admin/property-types`);
    await page.click('button:has-text("إنشاء"), button:has-text("Create")');
    await page.fill('input[name="data.name_ar"]', 'بيت شعبي');
    await page.fill('input[name="data.name_en"]', 'Traditional House');
    await page.fill('input[name="data.sort_order"]', '15');
    await page.click('button[type="submit"]');
    await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    
    // 2. Create property status
    await page.goto(`${BASE_URL}/admin/property-statuses`);
    await page.click('button:has-text("إنشاء"), button:has-text("Create")');
    await page.fill('input[name="data.name_ar"]', 'جاهز للتأجير');
    await page.fill('input[name="data.name_en"]', 'Ready for Rent');
    await page.fill('input[name="data.color"]', '#00FF00');
    await page.fill('input[name="data.icon"]', 'heroicon-o-check-circle');
    await page.click('button[type="submit"]');
    await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    
    // 3. Create property features
    await page.goto(`${BASE_URL}/admin/property-features`);
    
    // Create basic feature
    await page.click('button:has-text("إنشاء"), button:has-text("Create")');
    await page.fill('input[name="data.name_ar"]', 'حديقة خلفية');
    await page.fill('input[name="data.name_en"]', 'Backyard');
    await page.selectOption('select[name="data.category"]', 'amenities');
    await page.fill('input[name="data.icon"]', 'heroicon-o-tree');
    await page.click('button[type="submit"]');
    await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    
    // Create feature with value
    await page.click('button:has-text("إنشاء"), button:has-text("Create")');
    await page.fill('input[name="data.name_ar"]', 'مساحة الحديقة');
    await page.fill('input[name="data.name_en"]', 'Garden Area');
    await page.selectOption('select[name="data.category"]', 'basics');
    await page.check('input[name="data.requires_value"]');
    await page.selectOption('select[name="data.value_type"]', 'number');
    await page.click('button[type="submit"]');
    await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    
    // 4. Verify all created items exist
    await page.goto(`${BASE_URL}/admin/property-types`);
    await expect(page.locator('table').getByText('بيت شعبي')).toBeVisible();
    
    await page.goto(`${BASE_URL}/admin/property-statuses`);
    await expect(page.locator('table').getByText('جاهز للتأجير')).toBeVisible();
    
    await page.goto(`${BASE_URL}/admin/property-features`);
    await expect(page.locator('table').getByText('حديقة خلفية')).toBeVisible();
    await expect(page.locator('table').getByText('مساحة الحديقة')).toBeVisible();
  });

  test('property features comprehensive test', async ({ page }) => {
    // Login
    await page.goto(`${BASE_URL}/admin/login`);
    await page.fill('input[type="email"]', ADMIN_EMAIL);
    await page.fill('input[type="password"]', ADMIN_PASSWORD);
    await page.click('button[type="submit"]');
    
    await page.goto(`${BASE_URL}/admin/property-features`);
    
    // Test all value types
    const valueTypes = [
      { type: 'text', nameAr: 'نوع الأرضية', nameEn: 'Floor Type' },
      { type: 'number', nameAr: 'عدد الحمامات', nameEn: 'Number of Bathrooms' },
      { type: 'boolean', nameAr: 'يوجد مصعد', nameEn: 'Has Elevator' },
      { type: 'date', nameAr: 'تاريخ البناء', nameEn: 'Construction Date' }
    ];
    
    for (const valueType of valueTypes) {
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      await page.fill('input[name="data.name_ar"]', valueType.nameAr);
      await page.fill('input[name="data.name_en"]', valueType.nameEn);
      await page.selectOption('select[name="data.category"]', 'basics');
      await page.check('input[name="data.requires_value"]');
      await page.selectOption('select[name="data.value_type"]', valueType.type);
      await page.click('button[type="submit"]');
      
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      await expect(page.locator('table').getByText(valueType.nameAr)).toBeVisible();
    }
    
    // Test categories
    const categories = ['basics', 'amenities', 'security', 'extras'];
    
    for (const category of categories) {
      // Filter by category
      const filterButton = page.locator('button:has-text("فلترة")');
      if (await filterButton.isVisible()) {
        await filterButton.click();
        await page.selectOption('select[name="tableFilters[category][value]"]', category);
        await page.click('button:has-text("تطبيق")');
        await expect(page.locator('table')).toBeVisible();
        
        // Clear filter for next iteration
        await page.click('button:has-text("مسح الفلاتر"), button:has-text("Clear")');
      }
    }
  });
});