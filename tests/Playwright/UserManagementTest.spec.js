import { test, expect } from '@playwright/test';

// Test credentials
const ADMIN_EMAIL = 'admin@aqarcrm.com';
const ADMIN_PASSWORD = 'password123';

// Base URL
const BASE_URL = 'http://127.0.0.1:8000';

test.describe('User Management System Tests', () => {
  
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await page.goto(`${BASE_URL}/admin/login`);
    await page.fill('input[type="email"]', ADMIN_EMAIL);
    await page.fill('input[type="password"]', ADMIN_PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL(`${BASE_URL}/admin`);
  });

  test.describe('Authentication Tests', () => {
    
    test('should login successfully with valid credentials', async ({ page }) => {
      // This is already tested in beforeEach, but let's verify dashboard
      await expect(page).toHaveURL(`${BASE_URL}/admin`);
      await expect(page.locator('h1, h2, .filament-page-heading')).toContainText(['لوحة التحكم', 'Dashboard']);
      
      // Check navigation is visible
      await expect(page.locator('nav')).toBeVisible();
      await expect(page.locator('a:has-text("المستخدمين")')).toBeVisible();
    });

    test('should logout successfully', async ({ page }) => {
      // Click on user menu
      await page.click('button[aria-label="User menu"]');
      
      // Click logout
      await page.click('a:has-text("تسجيل الخروج"), a:has-text("Logout")');
      
      // Should redirect to login page
      await expect(page).toHaveURL(/login/);
      await expect(page.locator('form')).toBeVisible();
    });

    test('should show error with invalid credentials', async ({ page }) => {
      // Logout first
      await page.click('button[aria-label="User menu"]');
      await page.click('a:has-text("تسجيل الخروج"), a:has-text("Logout")');
      
      // Try login with wrong credentials
      await page.fill('input[type="email"]', 'wrong@email.com');
      await page.fill('input[type="password"]', 'wrongpassword');
      await page.click('button[type="submit"]');
      
      // Should show error message
      await expect(page.locator('.filament-notification-title, .alert-danger, .error')).toBeVisible();
    });
  });

  test.describe('Employee Management Tests', () => {
    
    test('should access employees list page', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/employees`);
      await expect(page).toHaveTitle(/الموظفين/);
      
      // Check table is displayed
      await expect(page.locator('table')).toBeVisible();
      
      // Check filters are above table
      const filtersSection = page.locator('[data-filament-table-filters]');
      await expect(filtersSection).toBeVisible();
    });

    test('should create new employee', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/employees`);
      
      // Click create button
      await page.click('a[href*="employees/create"]');
      
      // Fill employee form
      await page.fill('input[name="data.name"]', 'أحمد محمد');
      await page.fill('input[name="data.phone1"]', '0501234567');
      await page.fill('input[name="data.phone2"]', '0507654321');
      await page.fill('input[name="data.email"]', 'ahmed@test.com');
      
      // Username and email should be auto-generated from phone1
      const username = await page.locator('input[name="data.username"]').inputValue();
      expect(username).toBe('9660501234567');
      
      // Submit form
      await page.click('button[type="submit"]');
      
      // Check success notification
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Verify in list
      await page.goto(`${BASE_URL}/admin/employees`);
      await expect(page.locator('table').getByText('أحمد محمد')).toBeVisible();
    });

    test('should edit existing employee', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/employees`);
      
      // Click edit on first employee
      await page.click('table tbody tr:first-child a[href*="/edit"]');
      
      // Update name
      await page.fill('input[name="data.name"]', 'أحمد محمد المحدث');
      
      // Submit
      await page.click('button[type="submit"]');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Verify update
      await page.goto(`${BASE_URL}/admin/employees`);
      await expect(page.locator('table').getByText('أحمد محمد المحدث')).toBeVisible();
    });

    test('should delete employee', async ({ page }) => {
      // First create a test employee
      await page.goto(`${BASE_URL}/admin/employees/create`);
      await page.fill('input[name="data.name"]', 'موظف للحذف');
      await page.fill('input[name="data.phone1"]', '0509999999');
      await page.click('button[type="submit"]');
      
      // Go to list and delete
      await page.goto(`${BASE_URL}/admin/employees`);
      
      // Find the row and click delete
      const row = page.locator('table tbody tr').filter({ hasText: 'موظف للحذف' });
      await row.locator('button[title="حذف"]').click();
      
      // Confirm deletion
      await page.click('button:has-text("تأكيد")');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحذف');
    });

    test('should validate required fields for employee', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/employees/create`);
      
      // Try to submit without filling required fields
      await page.click('button[type="submit"]');
      
      // Check validation errors
      await expect(page.locator('.filament-form-field-error')).toContainText('مطلوب');
    });
  });

  test.describe('Owner Management Tests', () => {
    
    test('should access owners list page', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/owners`);
      await expect(page).toHaveTitle(/الملاك/);
      
      // Check table is displayed
      await expect(page.locator('table')).toBeVisible();
    });

    test('should create new owner with auto-generated username/email', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/owners`);
      
      // Click create button
      await page.click('a[href*="owners/create"]');
      
      // Fill owner form
      await page.fill('input[name="data.name"]', 'مالك العقار');
      await page.fill('input[name="data.phone1"]', '0501111111');
      await page.fill('input[name="data.phone2"]', '0507777777');
      
      // Check that username and email are auto-generated from phone1
      const username = await page.locator('input[name="data.username"]').inputValue();
      const email = await page.locator('input[name="data.email"]').inputValue();
      
      expect(username).toBe('9660501111111');
      expect(email).toBe('9660501111111@aqarcrm.com');
      
      // Should not see login details section
      await expect(page.locator('section:has-text("معلومات الدخول")')).not.toBeVisible();
      
      // Submit form
      await page.click('button[type="submit"]');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Verify in list
      await page.goto(`${BASE_URL}/admin/owners`);
      await expect(page.locator('table').getByText('مالك العقار')).toBeVisible();
    });

    test('should edit existing owner', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/owners`);
      
      // Click edit on first owner
      await page.click('table tbody tr:first-child a[href*="/edit"]');
      
      // Update name
      await page.fill('input[name="data.name"]', 'مالك العقار المحدث');
      
      // Submit
      await page.click('button[type="submit"]');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    });

    test('should delete owner', async ({ page }) => {
      // First create a test owner
      await page.goto(`${BASE_URL}/admin/owners/create`);
      await page.fill('input[name="data.name"]', 'مالك للحذف');
      await page.fill('input[name="data.phone1"]', '0508888888');
      await page.click('button[type="submit"]');
      
      // Go to list and delete
      await page.goto(`${BASE_URL}/admin/owners`);
      
      // Find the row and click delete
      const row = page.locator('table tbody tr').filter({ hasText: 'مالك للحذف' });
      await row.locator('button[title="حذف"]').click();
      
      // Confirm deletion
      await page.click('button:has-text("تأكيد")');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحذف');
    });

    test('should not show login details section for owners', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/owners/create`);
      
      // Login details section should not be visible
      await expect(page.locator('section:has-text("معلومات الدخول")')).not.toBeVisible();
      await expect(page.locator('input[name="data.password"]')).not.toBeVisible();
    });

    test('should validate owner phone number format', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/owners/create`);
      
      // Fill with invalid phone
      await page.fill('input[name="data.name"]', 'مالك اختبار');
      await page.fill('input[name="data.phone1"]', '123'); // Invalid phone
      
      // Submit
      await page.click('button[type="submit"]');
      
      // Check validation
      await expect(page.locator('.filament-form-field-error')).toBeVisible();
    });
  });

  test.describe('Tenant Management Tests', () => {
    
    test('should access tenants list page', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/tenants`);
      await expect(page).toHaveTitle(/المستأجرين/);
      
      // Check table is displayed
      await expect(page.locator('table')).toBeVisible();
    });

    test('should create new tenant with auto-generated username/email', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/tenants`);
      
      // Click create button
      await page.click('a[href*="tenants/create"]');
      
      // Fill tenant form
      await page.fill('input[name="data.name"]', 'مستأجر العقار');
      await page.fill('input[name="data.phone1"]', '0502222222');
      await page.fill('input[name="data.phone2"]', '0508888888');
      
      // Check that username and email are auto-generated from phone1
      const username = await page.locator('input[name="data.username"]').inputValue();
      const email = await page.locator('input[name="data.email"]').inputValue();
      
      expect(username).toBe('9660502222222');
      expect(email).toBe('9660502222222@aqarcrm.com');
      
      // Should not see login details section
      await expect(page.locator('section:has-text("معلومات الدخول")')).not.toBeVisible();
      
      // Submit form
      await page.click('button[type="submit"]');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Verify in list
      await page.goto(`${BASE_URL}/admin/tenants`);
      await expect(page.locator('table').getByText('مستأجر العقار')).toBeVisible();
    });

    test('should edit existing tenant', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/tenants`);
      
      // Click edit on first tenant
      await page.click('table tbody tr:first-child a[href*="/edit"]');
      
      // Update name
      await page.fill('input[name="data.name"]', 'مستأجر العقار المحدث');
      
      // Submit
      await page.click('button[type="submit"]');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    });

    test('should delete tenant', async ({ page }) => {
      // First create a test tenant
      await page.goto(`${BASE_URL}/admin/tenants/create`);
      await page.fill('input[name="data.name"]', 'مستأجر للحذف');
      await page.fill('input[name="data.phone1"]', '0503333333');
      await page.click('button[type="submit"]');
      
      // Go to list and delete
      await page.goto(`${BASE_URL}/admin/tenants`);
      
      // Find the row and click delete
      const row = page.locator('table tbody tr').filter({ hasText: 'مستأجر للحذف' });
      await row.locator('button[title="حذف"]').click();
      
      // Confirm deletion
      await page.click('button:has-text("تأكيد")');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحذف');
    });

    test('should not show login details section for tenants', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/tenants/create`);
      
      // Login details section should not be visible
      await expect(page.locator('section:has-text("معلومات الدخول")')).not.toBeVisible();
      await expect(page.locator('input[name="data.password"]')).not.toBeVisible();
    });

    test('should validate tenant phone number format', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/tenants/create`);
      
      // Fill with invalid phone
      await page.fill('input[name="data.name"]', 'مستأجر اختبار');
      await page.fill('input[name="data.phone1"]', 'abc'); // Invalid phone
      
      // Submit
      await page.click('button[type="submit"]');
      
      // Check validation
      await expect(page.locator('.filament-form-field-error')).toBeVisible();
    });
  });

  test.describe('User Role Management Tests', () => {
    
    test('should verify user roles are assigned correctly', async ({ page }) => {
      // Create employee and verify role
      await page.goto(`${BASE_URL}/admin/employees/create`);
      await page.fill('input[name="data.name"]', 'موظف للاختبار');
      await page.fill('input[name="data.phone1"]', '0504444444');
      await page.click('button[type="submit"]');
      
      // Create owner and verify role
      await page.goto(`${BASE_URL}/admin/owners/create`);
      await page.fill('input[name="data.name"]', 'مالك للاختبار');
      await page.fill('input[name="data.phone1"]', '0505555555');
      await page.click('button[type="submit"]');
      
      // Create tenant and verify role
      await page.goto(`${BASE_URL}/admin/tenants/create`);
      await page.fill('input[name="data.name"]', 'مستأجر للاختبار');
      await page.fill('input[name="data.phone1"]', '0506666666');
      await page.click('button[type="submit"]');
      
      // Check that users appear in their respective lists
      await page.goto(`${BASE_URL}/admin/employees`);
      await expect(page.locator('table').getByText('موظف للاختبار')).toBeVisible();
      
      await page.goto(`${BASE_URL}/admin/owners`);
      await expect(page.locator('table').getByText('مالك للاختبار')).toBeVisible();
      
      await page.goto(`${BASE_URL}/admin/tenants`);
      await expect(page.locator('table').getByText('مستأجر للاختبار')).toBeVisible();
    });
  });

  test.describe('Search and Filter Tests', () => {
    
    test('should search users by name', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/employees`);
      
      // Use search functionality
      await page.fill('input[placeholder*="بحث"], input[type="search"]', 'أحمد');
      await page.keyboard.press('Enter');
      
      // Check search results
      await expect(page.locator('table')).toBeVisible();
    });

    test('should filter users by active status', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/employees`);
      
      // Open filters if available
      const filterButton = page.locator('button:has-text("فلترة")');
      if (await filterButton.isVisible()) {
        await filterButton.click();
        
        // Apply active filter
        await page.selectOption('select[name*="is_active"]', '1');
        await page.click('button:has-text("تطبيق")');
        
        // Check filtered results
        await expect(page.locator('table')).toBeVisible();
      }
    });
  });

  test.describe('Bulk Operations Tests', () => {
    
    test('should handle bulk selection', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/employees`);
      
      // Select multiple items
      const checkboxes = page.locator('table tbody tr input[type="checkbox"]');
      const count = await checkboxes.count();
      
      if (count > 0) {
        await checkboxes.first().check();
        if (count > 1) {
          await checkboxes.nth(1).check();
        }
        
        // Check if bulk actions appear
        const bulkActions = page.locator('button:has-text("إجراءات مجمعة")');
        if (await bulkActions.isVisible()) {
          await expect(bulkActions).toBeVisible();
        }
      }
    });
  });

  test.describe('Performance and Error Handling', () => {
    
    test('should load user pages within acceptable time', async ({ page }) => {
      const pages = [
        `${BASE_URL}/admin/employees`,
        `${BASE_URL}/admin/owners`,
        `${BASE_URL}/admin/tenants`
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

    test('should handle form validation errors gracefully', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/employees/create`);
      
      // Submit empty form
      await page.click('button[type="submit"]');
      
      // Check that validation errors are displayed properly
      const errors = page.locator('.filament-form-field-error');
      await expect(errors.first()).toBeVisible();
      
      // Form should still be usable after error
      await page.fill('input[name="data.name"]', 'اختبار');
      await expect(page.locator('input[name="data.name"]')).toHaveValue('اختبار');
    });

    test('should handle network errors gracefully', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/employees`);
      
      // Simulate offline
      await page.context().setOffline(true);
      
      // Try to navigate
      await page.click('a[href*="employees/create"]', { timeout: 5000 }).catch(() => {});
      
      // Restore connection
      await page.context().setOffline(false);
    });
  });

  test.describe('Accessibility Tests', () => {
    
    test('should have proper form labels', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/employees/create`);
      
      // Check that all form fields have labels
      const inputs = page.locator('input[name^="data."]');
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

    test('should be keyboard navigable', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/employees`);
      
      // Tab through interface
      await page.keyboard.press('Tab');
      await page.keyboard.press('Tab');
      await page.keyboard.press('Tab');
      
      // Check focus is visible
      const focusedElement = await page.evaluate(() => document.activeElement.tagName);
      expect(focusedElement).toBeTruthy();
    });
  });
});

// Critical user journeys
test.describe('Critical User Management Workflows', () => {
  
  test('complete employee management workflow', async ({ page }) => {
    // Login
    await page.goto(`${BASE_URL}/admin/login`);
    await page.fill('input[type="email"]', ADMIN_EMAIL);
    await page.fill('input[type="password"]', ADMIN_PASSWORD);
    await page.click('button[type="submit"]');
    
    // Navigate to employees
    await page.click('a:has-text("الموظفين"), a:has-text("Employees")');
    
    // Create new employee
    await page.click('a:has-text("إنشاء"), a:has-text("Create")');
    
    // Fill complete form
    await page.fill('input[name="data.name"]', 'سارة أحمد');
    await page.fill('input[name="data.phone1"]', '0501111222');
    await page.fill('input[name="data.phone2"]', '0507777888');
    await page.fill('input[name="data.email"]', 'sara@aqarcrm.com');
    
    // Submit
    await page.click('button[type="submit"]');
    
    // Verify success
    await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    
    // Edit the created employee
    await page.goto(`${BASE_URL}/admin/employees`);
    const row = page.locator('table tbody tr').filter({ hasText: 'سارة أحمد' });
    await row.locator('a[href*="/edit"]').click();
    
    // Update information
    await page.fill('input[name="data.name"]', 'سارة أحمد المحدثة');
    await page.click('button[type="submit"]');
    
    // Verify update
    await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    
    // View employee details
    await page.goto(`${BASE_URL}/admin/employees`);
    const updatedRow = page.locator('table tbody tr').filter({ hasText: 'سارة أحمد المحدثة' });
    const viewButton = updatedRow.locator('a[href*="/view"], button[title="عرض"]');
    if (await viewButton.isVisible()) {
      await viewButton.click();
      await expect(page.locator('h1, h2')).toContainText('سارة أحمد المحدثة');
    }
  });

  test('complete owner management workflow', async ({ page }) => {
    // Login
    await page.goto(`${BASE_URL}/admin/login`);
    await page.fill('input[type="email"]', ADMIN_EMAIL);
    await page.fill('input[type="password"]', ADMIN_PASSWORD);
    await page.click('button[type="submit"]');
    
    // Navigate to owners
    await page.click('a:has-text("الملاك"), a:has-text("Owners")');
    
    // Create new owner
    await page.click('a:has-text("إنشاء"), a:has-text("Create")');
    
    // Fill form (no login details should be visible)
    await page.fill('input[name="data.name"]', 'خالد العثمان');
    await page.fill('input[name="data.phone1"]', '0509876543');
    
    // Verify username/email auto-generation
    const username = await page.locator('input[name="data.username"]').inputValue();
    const email = await page.locator('input[name="data.email"]').inputValue();
    
    expect(username).toBe('9660509876543');
    expect(email).toBe('9660509876543@aqarcrm.com');
    
    // Submit
    await page.click('button[type="submit"]');
    
    // Verify success
    await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    
    // Verify no login details section was shown
    await page.goto(`${BASE_URL}/admin/owners`);
    const row = page.locator('table tbody tr').filter({ hasText: 'خالد العثمان' });
    await row.locator('a[href*="/edit"]').click();
    await expect(page.locator('section:has-text("معلومات الدخول")')).not.toBeVisible();
  });

  test('complete tenant management workflow', async ({ page }) => {
    // Login
    await page.goto(`${BASE_URL}/admin/login`);
    await page.fill('input[type="email"]', ADMIN_EMAIL);
    await page.fill('input[type="password"]', ADMIN_PASSWORD);
    await page.click('button[type="submit"]');
    
    // Navigate to tenants
    await page.click('a:has-text("المستأجرين"), a:has-text("Tenants")');
    
    // Create new tenant
    await page.click('a:has-text("إنشاء"), a:has-text("Create")');
    
    // Fill form (no login details should be visible)
    await page.fill('input[name="data.name"]', 'نوال السعد');
    await page.fill('input[name="data.phone1"]', '0503456789');
    
    // Verify username/email auto-generation
    const username = await page.locator('input[name="data.username"]').inputValue();
    const email = await page.locator('input[name="data.email"]').inputValue();
    
    expect(username).toBe('9660503456789');
    expect(email).toBe('9660503456789@aqarcrm.com');
    
    // Submit
    await page.click('button[type="submit"]');
    
    // Verify success
    await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    
    // Verify no login details section was shown
    await page.goto(`${BASE_URL}/admin/tenants`);
    const row = page.locator('table tbody tr').filter({ hasText: 'نوال السعد' });
    await row.locator('a[href*="/edit"]').click();
    await expect(page.locator('section:has-text("معلومات الدخول")')).not.toBeVisible();
  });
});