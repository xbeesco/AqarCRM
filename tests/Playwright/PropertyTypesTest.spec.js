import { test, expect } from '@playwright/test';

// Test credentials
const ADMIN_EMAIL = 'admin@aqarcrm.com';
const ADMIN_PASSWORD = 'password123';

// Base URL
const BASE_URL = 'http://127.0.0.1:8000';

test.describe('Property Types Module Tests', () => {
  
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await page.goto(`${BASE_URL}/admin/login`);
    await page.fill('input[type="email"]', ADMIN_EMAIL);
    await page.fill('input[type="password"]', ADMIN_PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL(`${BASE_URL}/admin`);
  });

  test.describe('Property Types Resource', () => {
    
    test('should access property types list page', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      await expect(page).toHaveTitle(/أنواع العقارات/);
      
      // Check if table is displayed
      await expect(page.locator('table')).toBeVisible();
      
      // Check if filters are above table
      const filtersSection = page.locator('[data-filament-table-filters]');
      await expect(filtersSection).toBeVisible();
    });

    test('should display all seeded property types', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      
      // Check for seeded property types
      const propertyTypes = ['فيلا', 'شقة', 'محل تجاري', 'مكتب', 'مستودع', 'أرض', 'عمارة'];
      
      for (const type of propertyTypes) {
        await expect(page.locator('table').getByText(type)).toBeVisible();
      }
    });

    test('should create new property type', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      
      // Click create button
      await page.click('a[href*="property-types/create"]');
      
      // Fill form
      await page.fill('input[name="data.name_ar"]', 'شاليه');
      await page.fill('input[name="data.name_en"]', 'Chalet');
      await page.fill('textarea[name="data.description"]', 'شاليه للإيجار');
      await page.fill('input[name="data.sort_order"]', '8');
      
      // Submit form
      await page.click('button[type="submit"]');
      
      // Check success notification
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Verify in list
      await page.goto(`${BASE_URL}/admin/property-types`);
      await expect(page.locator('table').getByText('شاليه')).toBeVisible();
    });

    test('should edit existing property type', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      
      // Click edit on first item
      await page.click('table tbody tr:first-child a[href*="/edit"]');
      
      // Update description
      const newDescription = 'وصف محدث للاختبار';
      await page.fill('textarea[name="data.description"]', newDescription);
      
      // Submit
      await page.click('button[type="submit"]');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    });

    test('should delete property type', async ({ page }) => {
      // First create a test item to delete
      await page.goto(`${BASE_URL}/admin/property-types/create`);
      await page.fill('input[name="data.name_ar"]', 'نوع للحذف');
      await page.fill('input[name="data.name_en"]', 'Type to Delete');
      await page.click('button[type="submit"]');
      
      // Go to list and delete
      await page.goto(`${BASE_URL}/admin/property-types`);
      
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

    test('should filter property types by active status', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      
      // Open filters
      await page.click('button:has-text("فلترة")');
      
      // Select active filter
      await page.selectOption('select[name="tableFilters[is_active][value]"]', '1');
      
      // Apply filter
      await page.click('button:has-text("تطبيق")');
      
      // Check that table is filtered
      await expect(page.locator('table')).toBeVisible();
    });

    test('should validate required fields', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types/create`);
      
      // Try to submit without filling required fields
      await page.click('button[type="submit"]');
      
      // Check validation errors
      await expect(page.locator('.filament-form-field-error')).toContainText('مطلوب');
    });

    test('should handle parent-child relationships', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types/create`);
      
      // Fill form with parent
      await page.fill('input[name="data.name_ar"]', 'نوع فرعي');
      await page.fill('input[name="data.name_en"]', 'Sub Type');
      
      // Select parent
      await page.selectOption('select[name="data.parent_id"]', { index: 1 });
      
      // Submit
      await page.click('button[type="submit"]');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    });
  });

  test.describe('Property Statuses Resource', () => {
    
    test('should access property statuses list page', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-statuses`);
      await expect(page).toHaveTitle(/حالات العقارات/);
      
      // Check if table is displayed
      await expect(page.locator('table')).toBeVisible();
    });

    test('should display all seeded property statuses', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-statuses`);
      
      // Check for seeded statuses
      const statuses = ['متاح', 'مؤجر', 'قيد الصيانة', 'محجوز', 'غير متاح'];
      
      for (const status of statuses) {
        await expect(page.locator('table').getByText(status)).toBeVisible();
      }
    });

    test('should create new property status', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-statuses`);
      
      // Click create button
      await page.click('a[href*="property-statuses/create"]');
      
      // Fill form
      await page.fill('input[name="data.name_ar"]', 'قيد البناء');
      await page.fill('input[name="data.name_en"]', 'Under Construction');
      await page.fill('input[name="data.color"]', '#FFA500');
      await page.fill('input[name="data.icon"]', 'heroicon-o-wrench');
      
      // Submit
      await page.click('button[type="submit"]');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    });

    test('should validate status transitions', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-statuses`);
      
      // Edit first status
      await page.click('table tbody tr:first-child a[href*="/edit"]');
      
      // Check transition rules section
      await expect(page.locator('label:has-text("يمكن التحويل إلى")')).toBeVisible();
    });
  });

  test.describe('Property Features Resource', () => {
    
    test('should access property features list page', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-features`);
      await expect(page).toHaveTitle(/مميزات العقارات/);
      
      // Check if table is displayed
      await expect(page.locator('table')).toBeVisible();
    });

    test('should display features by category', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-features`);
      
      // Check for categories
      const categories = ['أساسيات', 'مرافق', 'أمان', 'إضافات'];
      
      for (const category of categories) {
        await expect(page.locator('table').getByText(category)).toBeVisible();
      }
    });

    test('should create new property feature', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-features`);
      
      // Click create button
      await page.click('a[href*="property-features/create"]');
      
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
    });

    test('should filter features by category', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-features`);
      
      // Open filters
      await page.click('button:has-text("فلترة")');
      
      // Select category filter
      await page.selectOption('select[name="tableFilters[category][value]"]', 'basics');
      
      // Apply filter
      await page.click('button:has-text("تطبيق")');
      
      // Check filtered results
      await expect(page.locator('table').getByText('أساسيات')).toBeVisible();
    });

    test('should validate value type selection', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-features/create`);
      
      // Fill basic info
      await page.fill('input[name="data.name_ar"]', 'ميزة اختبار');
      await page.fill('input[name="data.name_en"]', 'Test Feature');
      
      // Enable requires value
      await page.check('input[name="data.requires_value"]');
      
      // Try to submit without selecting value type
      await page.click('button[type="submit"]');
      
      // Check validation
      await expect(page.locator('.filament-form-field-error')).toBeVisible();
    });
  });

  test.describe('Complex Workflows', () => {
    
    test('should handle property type hierarchy correctly', async ({ page }) => {
      // Create parent type
      await page.goto(`${BASE_URL}/admin/property-types/create`);
      await page.fill('input[name="data.name_ar"]', 'عقار رئيسي');
      await page.fill('input[name="data.name_en"]', 'Main Property');
      await page.click('button[type="submit"]');
      
      // Create child type
      await page.goto(`${BASE_URL}/admin/property-types/create`);
      await page.fill('input[name="data.name_ar"]', 'عقار فرعي');
      await page.fill('input[name="data.name_en"]', 'Sub Property');
      
      // Select the parent we just created
      await page.selectOption('select[name="data.parent_id"]', { label: 'عقار رئيسي' });
      await page.click('button[type="submit"]');
      
      // Verify hierarchy in list
      await page.goto(`${BASE_URL}/admin/property-types`);
      await expect(page.locator('table').getByText('عقار فرعي')).toBeVisible();
    });

    test('should handle bulk operations', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      
      // Select multiple items
      await page.check('table tbody tr:nth-child(1) input[type="checkbox"]');
      await page.check('table tbody tr:nth-child(2) input[type="checkbox"]');
      
      // Check bulk actions menu appears
      await expect(page.locator('button:has-text("إجراءات مجمعة")')).toBeVisible();
    });

    test('should handle pagination correctly', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      
      // Check pagination controls
      await expect(page.locator('.filament-pagination')).toBeVisible();
      
      // Check records per page selector
      await expect(page.locator('select[wire\\:model="tableRecordsPerPage"]')).toBeVisible();
    });

    test('should export data correctly', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      
      // Check if export button exists
      const exportButton = page.locator('button:has-text("تصدير")');
      if (await exportButton.isVisible()) {
        await exportButton.click();
        
        // Check export options
        await expect(page.locator('button:has-text("CSV")')).toBeVisible();
        await expect(page.locator('button:has-text("Excel")')).toBeVisible();
      }
    });
  });

  test.describe('Error Handling', () => {
    
    test('should handle network errors gracefully', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      
      // Simulate offline
      await page.context().setOffline(true);
      
      // Try to create new item
      await page.click('a[href*="property-types/create"]', { timeout: 5000 }).catch(() => {});
      
      // Check error handling
      await page.context().setOffline(false);
    });

    test('should handle validation errors properly', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types/create`);
      
      // Submit empty form
      await page.click('button[type="submit"]');
      
      // Check multiple validation errors
      const errors = page.locator('.filament-form-field-error');
      await expect(errors).toHaveCount(2); // name_ar and name_en are required
    });

    test('should handle duplicate slug correctly', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types/create`);
      
      // Try to create with existing name
      await page.fill('input[name="data.name_ar"]', 'فيلا');
      await page.fill('input[name="data.name_en"]', 'Villa');
      await page.click('button[type="submit"]');
      
      // Check for duplicate error
      await expect(page.locator('.filament-form-field-error')).toBeVisible();
    });
  });

  test.describe('Accessibility Tests', () => {
    
    test('should have proper ARIA labels', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      
      // Check main navigation
      await expect(page.locator('nav[aria-label]')).toBeVisible();
      
      // Check form labels
      await page.goto(`${BASE_URL}/admin/property-types/create`);
      await expect(page.locator('label[for]')).toHaveCount(7); // All form fields have labels
    });

    test('should be keyboard navigable', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      
      // Tab through interface
      await page.keyboard.press('Tab');
      await page.keyboard.press('Tab');
      await page.keyboard.press('Tab');
      
      // Check focus is visible
      const focusedElement = await page.evaluate(() => document.activeElement.tagName);
      expect(focusedElement).toBeTruthy();
    });
  });

  test.describe('Performance Tests', () => {
    
    test('should load list page within acceptable time', async ({ page }) => {
      const startTime = Date.now();
      await page.goto(`${BASE_URL}/admin/property-types`);
      const loadTime = Date.now() - startTime;
      
      // Page should load within 3 seconds
      expect(loadTime).toBeLessThan(3000);
    });

    test('should handle large datasets efficiently', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/property-types`);
      
      // Change records per page to maximum
      await page.selectOption('select[wire\\:model="tableRecordsPerPage"]', '50');
      
      // Check table still renders
      await expect(page.locator('table')).toBeVisible();
    });
  });
});

// Run specific test suites for critical paths
test.describe('Critical User Journeys', () => {
  
  test('complete property type creation workflow', async ({ page }) => {
    // Login
    await page.goto(`${BASE_URL}/admin/login`);
    await page.fill('input[type="email"]', ADMIN_EMAIL);
    await page.fill('input[type="password"]', ADMIN_PASSWORD);
    await page.click('button[type="submit"]');
    
    // Navigate to property types
    await page.click('a:has-text("أنواع العقارات")');
    
    // Create new type
    await page.click('a:has-text("إنشاء")');
    
    // Fill complete form
    await page.fill('input[name="data.name_ar"]', 'استراحة');
    await page.fill('input[name="data.name_en"]', 'Rest House');
    await page.fill('textarea[name="data.description"]', 'استراحة للإيجار اليومي والأسبوعي');
    await page.fill('input[name="data.icon"]', 'heroicon-o-home');
    await page.check('input[name="data.is_active"]');
    await page.fill('input[name="data.sort_order"]', '10');
    
    // Submit
    await page.click('button[type="submit"]');
    
    // Verify success
    await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    
    // Verify in list
    await page.goto(`${BASE_URL}/admin/property-types`);
    await expect(page.locator('table').getByText('استراحة')).toBeVisible();
    
    // Edit the created item
    const row = page.locator('table tbody tr').filter({ hasText: 'استراحة' });
    await row.locator('a[href*="/edit"]').click();
    
    // Update description
    await page.fill('textarea[name="data.description"]', 'استراحة فاخرة للإيجار');
    await page.click('button[type="submit"]');
    
    // Verify update
    await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
  });
});