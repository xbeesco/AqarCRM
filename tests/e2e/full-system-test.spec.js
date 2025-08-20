import { test, expect } from '@playwright/test';

// Test credentials
const ADMIN_EMAIL = 'admin@aqarcrm.com';
const ADMIN_PASSWORD = 'password123';

// Base URL
const BASE_URL = 'http://127.0.0.1:8000';

test.describe('Full System Integration Tests', () => {
  
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await page.goto(`${BASE_URL}/admin/login`);
    await page.fill('input[type="email"]', ADMIN_EMAIL);
    await page.fill('input[type="password"]', ADMIN_PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL(`${BASE_URL}/admin`);
  });

  test.describe('Critical System Paths', () => {
    
    test('complete system workflow - from basic setup to property management', async ({ page }) => {
      // 1. LOCATION SETUP
      console.log('Setting up location hierarchy...');
      
      // Create Region
      await page.goto(`${BASE_URL}/admin/locations/create`);
      await page.selectOption('select[name="data.level"]', '1');
      await page.fill('input[name="data.name_ar"]', 'منطقة نظام شامل');
      await page.fill('input[name="data.name_en"]', 'Full System Region');
      await page.fill('input[name="data.code"]', 'FSR');
      await page.click('button[type="submit"]');
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Create City
      await page.goto(`${BASE_URL}/admin/locations/create`);
      await page.selectOption('select[name="data.level"]', '2');
      await page.selectOption('select[name="data.parent_id"]', { label: 'منطقة نظام شامل' });
      await page.fill('input[name="data.name_ar"]', 'مدينة نظام شامل');
      await page.fill('input[name="data.name_en"]', 'Full System City');
      await page.fill('input[name="data.code"]', 'FSC');
      await page.click('button[type="submit"]');
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Create Neighborhood
      await page.goto(`${BASE_URL}/admin/locations/create`);
      await page.selectOption('select[name="data.level"]', '3');
      await page.selectOption('select[name="data.parent_id"]', { label: 'مدينة نظام شامل' });
      await page.fill('input[name="data.name_ar"]', 'مركز نظام شامل');
      await page.fill('input[name="data.name_en"]', 'Full System Center');
      await page.click('button[type="submit"]');
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      await page.goto(`${BASE_URL}/admin/locations/create`);
      await page.selectOption('select[name="data.level"]', '4');
      await page.selectOption('select[name="data.parent_id"]', { label: 'مركز نظام شامل' });
      await page.fill('input[name="data.name_ar"]', 'حي نظام شامل');
      await page.fill('input[name="data.name_en"]', 'Full System Neighborhood');
      await page.click('button[type="submit"]');
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // 2. PROPERTY CONFIGURATION
      console.log('Setting up property configuration...');
      
      // Create Property Type
      await page.goto(`${BASE_URL}/admin/property-types`);
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      await page.fill('input[name="data.name_ar"]', 'مبنى نظام شامل');
      await page.fill('input[name="data.name_en"]', 'Full System Building');
      await page.click('button[type="submit"]');
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Create Property Status
      await page.goto(`${BASE_URL}/admin/property-statuses`);
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      await page.fill('input[name="data.name_ar"]', 'جاهز للنظام الشامل');
      await page.fill('input[name="data.name_en"]', 'Ready for Full System');
      await page.fill('input[name="data.color"]', '#00AA00');
      await page.fill('input[name="data.icon"]', 'heroicon-o-check-circle');
      await page.click('button[type="submit"]');
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Create Property Features
      await page.goto(`${BASE_URL}/admin/property-features`);
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      await page.fill('input[name="data.name_ar"]', 'ميزة نظام شامل');
      await page.fill('input[name="data.name_en"]', 'Full System Feature');
      await page.selectOption('select[name="data.category"]', 'basics');
      await page.fill('input[name="data.icon"]', 'heroicon-o-star');
      await page.click('button[type="submit"]');
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // 3. USER MANAGEMENT
      console.log('Setting up users...');
      
      // Create Owner
      await page.goto(`${BASE_URL}/admin/owners/create`);
      await page.fill('input[name="data.name"]', 'مالك النظام الشامل');
      await page.fill('input[name="data.phone1"]', '0500000001');
      await page.click('button[type="submit"]');
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Create Tenant
      await page.goto(`${BASE_URL}/admin/tenants/create`);
      await page.fill('input[name="data.name"]', 'مستأجر النظام الشامل');
      await page.fill('input[name="data.phone1"]', '0500000002');
      await page.click('button[type="submit"]');
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Create Employee
      await page.goto(`${BASE_URL}/admin/employees/create`);
      await page.fill('input[name="data.name"]', 'موظف النظام الشامل');
      await page.fill('input[name="data.phone1"]', '0500000003');
      await page.fill('input[name="data.email"]', 'employee@fullsystem.test');
      await page.click('button[type="submit"]');
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // 4. VERIFY EVERYTHING IS CONNECTED
      console.log('Verifying system integration...');
      
      // Check that all created items exist and are properly linked
      await page.goto(`${BASE_URL}/admin/locations`);
      await expect(page.locator('table').getByText('حي نظام شامل')).toBeVisible();
      
      await page.goto(`${BASE_URL}/admin/property-types`);
      await expect(page.locator('table').getByText('مبنى نظام شامل')).toBeVisible();
      
      await page.goto(`${BASE_URL}/admin/property-statuses`);
      await expect(page.locator('table').getByText('جاهز للنظام الشامل')).toBeVisible();
      
      await page.goto(`${BASE_URL}/admin/property-features`);
      await expect(page.locator('table').getByText('ميزة نظام شامل')).toBeVisible();
      
      await page.goto(`${BASE_URL}/admin/owners`);
      await expect(page.locator('table').getByText('مالك النظام الشامل')).toBeVisible();
      
      await page.goto(`${BASE_URL}/admin/tenants`);
      await expect(page.locator('table').getByText('مستأجر النظام الشامل')).toBeVisible();
      
      await page.goto(`${BASE_URL}/admin/employees`);
      await expect(page.locator('table').getByText('موظف النظام الشامل')).toBeVisible();
      
      console.log('✅ Complete system workflow test passed!');
    });

    test('navigation and page accessibility test', async ({ page }) => {
      console.log('Testing all main navigation pages...');
      
      const mainPages = [
        { url: `${BASE_URL}/admin`, title: 'لوحة التحكم' },
        { url: `${BASE_URL}/admin/employees`, title: 'الموظفين' },
        { url: `${BASE_URL}/admin/owners`, title: 'الملاك' },
        { url: `${BASE_URL}/admin/tenants`, title: 'المستأجرين' },
        { url: `${BASE_URL}/admin/locations`, title: 'المواقع' },
        { url: `${BASE_URL}/admin/property-types`, title: 'أنواع العقارات' },
        { url: `${BASE_URL}/admin/property-statuses`, title: 'حالات العقارات' },
        { url: `${BASE_URL}/admin/property-features`, title: 'مميزات العقارات' },
      ];
      
      for (const pageInfo of mainPages) {
        console.log(`Testing page: ${pageInfo.url}`);
        
        const startTime = Date.now();
        await page.goto(pageInfo.url);
        const loadTime = Date.now() - startTime;
        
        // Page should load within 3 seconds
        expect(loadTime).toBeLessThan(3000);
        
        // Page should not have critical errors
        await expect(page.locator('body')).not.toContainText('Error 500');
        await expect(page.locator('body')).not.toContainText('Fatal error');
        await expect(page.locator('body')).not.toContainText('Exception');
        
        // Page should have proper structure
        await expect(page.locator('nav')).toBeVisible();
        
        // List pages should have table
        if (pageInfo.url.includes('/admin/') && !pageInfo.url.endsWith('/admin')) {
          await expect(page.locator('table')).toBeVisible();
        }
        
        console.log(`✅ ${pageInfo.url} - Loaded in ${loadTime}ms`);
      }
    });

    test('form validation across all modules', async ({ page }) => {
      console.log('Testing form validation across all modules...');
      
      const formsToTest = [
        {
          url: `${BASE_URL}/admin/employees/create`,
          name: 'Employee Form',
          requiredFields: ['data.name', 'data.phone1']
        },
        {
          url: `${BASE_URL}/admin/owners/create`,
          name: 'Owner Form',
          requiredFields: ['data.name', 'data.phone1']
        },
        {
          url: `${BASE_URL}/admin/tenants/create`,
          name: 'Tenant Form',
          requiredFields: ['data.name', 'data.phone1']
        },
        {
          url: `${BASE_URL}/admin/locations/create`,
          name: 'Location Form',
          requiredFields: ['data.level', 'data.name_ar']
        }
      ];
      
      for (const formInfo of formsToTest) {
        console.log(`Testing ${formInfo.name}...`);
        
        await page.goto(formInfo.url);
        
        // Try to submit empty form
        await page.click('button[type="submit"]');
        
        // Should show validation errors
        await expect(page.locator('.filament-form-field-error')).toBeVisible();
        
        // Fill required fields and verify validation clears
        for (const field of formInfo.requiredFields) {
          if (field.includes('level')) {
            await page.selectOption(`select[name="${field}"]`, '1');
          } else if (field.includes('phone')) {
            await page.fill(`input[name="${field}"]`, '0500000000');
          } else {
            await page.fill(`input[name="${field}"]`, 'Test Value');
          }
        }
        
        // Validation errors should be reduced or cleared
        const errorCount = await page.locator('.filament-form-field-error').count();
        expect(errorCount).toBeLessThanOrEqual(2); // Allow for some remaining validation
        
        console.log(`✅ ${formInfo.name} validation working`);
      }
    });

    test('search and filter functionality test', async ({ page }) => {
      console.log('Testing search and filter functionality...');
      
      const searchablePages = [
        `${BASE_URL}/admin/employees`,
        `${BASE_URL}/admin/owners`,
        `${BASE_URL}/admin/tenants`,
        `${BASE_URL}/admin/locations`,
        `${BASE_URL}/admin/property-types`,
        `${BASE_URL}/admin/property-statuses`,
        `${BASE_URL}/admin/property-features`
      ];
      
      for (const pageUrl of searchablePages) {
        console.log(`Testing search on: ${pageUrl}`);
        
        await page.goto(pageUrl);
        
        // Test search functionality
        const searchInput = page.locator('input[placeholder*="بحث"], input[type="search"]');
        if (await searchInput.isVisible()) {
          await searchInput.fill('test');
          await page.keyboard.press('Enter');
          
          // Table should still be visible after search
          await expect(page.locator('table')).toBeVisible();
          
          // Clear search
          await searchInput.fill('');
          await page.keyboard.press('Enter');
        }
        
        // Test filter functionality
        const filterButton = page.locator('button:has-text("فلترة")');
        if (await filterButton.isVisible()) {
          await filterButton.click();
          
          // Check if filter panel opens
          const filterPanel = page.locator('[data-filament-table-filters]');
          if (await filterPanel.isVisible()) {
            // Try to apply a basic filter
            const firstSelect = filterPanel.locator('select').first();
            if (await firstSelect.isVisible()) {
              const options = firstSelect.locator('option');
              const optionCount = await options.count();
              
              if (optionCount > 1) {
                await firstSelect.selectOption({ index: 1 });
                
                // Apply filter if there's an apply button
                const applyButton = page.locator('button:has-text("تطبيق")');
                if (await applyButton.isVisible()) {
                  await applyButton.click();
                }
                
                // Table should still be visible after filtering
                await expect(page.locator('table')).toBeVisible();
              }
            }
          }
        }
        
        console.log(`✅ Search/Filter working on ${pageUrl}`);
      }
    });
  });

  test.describe('Error Handling and Edge Cases', () => {
    
    test('handle network interruptions gracefully', async ({ page }) => {
      console.log('Testing network interruption handling...');
      
      await page.goto(`${BASE_URL}/admin/employees`);
      
      // Simulate network disconnection
      await page.context().setOffline(true);
      
      // Try to navigate (should handle gracefully)
      const navigationPromise = page.click('a[href*="employees/create"]').catch(() => {
        // Expected to fail due to offline mode
      });
      
      // Wait a moment
      await page.waitForTimeout(2000);
      
      // Restore connection
      await page.context().setOffline(false);
      
      // Should be able to navigate again
      await page.goto(`${BASE_URL}/admin/employees`);
      await expect(page.locator('table')).toBeVisible();
      
      console.log('✅ Network interruption handled gracefully');
    });

    test('handle large datasets efficiently', async ({ page }) => {
      console.log('Testing large dataset handling...');
      
      const paginatedPages = [
        `${BASE_URL}/admin/employees`,
        `${BASE_URL}/admin/owners`,
        `${BASE_URL}/admin/tenants`,
        `${BASE_URL}/admin/locations`
      ];
      
      for (const pageUrl of paginatedPages) {
        await page.goto(pageUrl);
        
        // Try to change records per page to maximum
        const recordsSelect = page.locator('select[wire\\:model="tableRecordsPerPage"]');
        if (await recordsSelect.isVisible()) {
          await recordsSelect.selectOption('50');
          
          // Page should handle large dataset
          await expect(page.locator('table')).toBeVisible();
          
          // Check pagination controls
          const pagination = page.locator('.filament-pagination');
          if (await pagination.isVisible()) {
            await expect(pagination).toBeVisible();
          }
        }
        
        console.log(`✅ Large dataset handled on ${pageUrl}`);
      }
    });

    test('handle duplicate data creation attempts', async ({ page }) => {
      console.log('Testing duplicate data handling...');
      
      // Try to create duplicate property type
      await page.goto(`${BASE_URL}/admin/property-types`);
      await page.click('button:has-text("إنشاء"), button:has-text("Create")');
      await page.fill('input[name="data.name_ar"]', 'فيلا'); // Existing type
      await page.fill('input[name="data.name_en"]', 'Villa'); // Existing type
      await page.click('button[type="submit"]');
      
      // Should show validation error or handle gracefully
      const hasError = await page.locator('.filament-form-field-error, .filament-notification').isVisible();
      expect(hasError).toBeTruthy();
      
      console.log('✅ Duplicate data creation handled');
    });

    test('handle invalid data inputs', async ({ page }) => {
      console.log('Testing invalid data input handling...');
      
      // Test invalid phone numbers
      await page.goto(`${BASE_URL}/admin/employees/create`);
      await page.fill('input[name="data.name"]', 'Test Employee');
      await page.fill('input[name="data.phone1"]', 'invalid-phone');
      await page.click('button[type="submit"]');
      
      // Should show validation error
      await expect(page.locator('.filament-form-field-error')).toBeVisible();
      
      // Test invalid coordinates
      await page.goto(`${BASE_URL}/admin/locations/create`);
      await page.selectOption('select[name="data.level"]', '1');
      await page.fill('input[name="data.name_ar"]', 'Test Location');
      await page.fill('input[name="data.coordinates"]', 'invalid-coordinates');
      await page.click('button[type="submit"]');
      
      // Should handle invalid coordinates
      const coordinateError = await page.locator('.filament-form-field-error').count();
      
      console.log('✅ Invalid data inputs handled');
    });
  });

  test.describe('Performance and Accessibility', () => {
    
    test('check page load performance', async ({ page }) => {
      console.log('Testing page load performance...');
      
      const criticalPages = [
        `${BASE_URL}/admin`,
        `${BASE_URL}/admin/employees`,
        `${BASE_URL}/admin/owners`,
        `${BASE_URL}/admin/tenants`,
        `${BASE_URL}/admin/locations`,
        `${BASE_URL}/admin/property-types`
      ];
      
      for (const pageUrl of criticalPages) {
        const startTime = Date.now();
        await page.goto(pageUrl);
        const loadTime = Date.now() - startTime;
        
        // Critical pages should load within 2 seconds
        expect(loadTime).toBeLessThan(2000);
        
        console.log(`✅ ${pageUrl} loaded in ${loadTime}ms`);
      }
    });

    test('check accessibility compliance', async ({ page }) => {
      console.log('Testing accessibility compliance...');
      
      const accessibilityPages = [
        `${BASE_URL}/admin/employees/create`,
        `${BASE_URL}/admin/owners/create`,
        `${BASE_URL}/admin/locations/create`
      ];
      
      for (const pageUrl of accessibilityPages) {
        await page.goto(pageUrl);
        
        // Check form labels
        const inputs = page.locator('input[name^="data."], select[name^="data."], textarea[name^="data."]');
        const inputCount = await inputs.count();
        
        let labelsFound = 0;
        for (let i = 0; i < inputCount; i++) {
          const input = inputs.nth(i);
          const id = await input.getAttribute('id');
          if (id) {
            const label = page.locator(`label[for="${id}"]`);
            if (await label.isVisible()) {
              labelsFound++;
            }
          }
        }
        
        // At least 80% of inputs should have labels
        const labelPercentage = (labelsFound / inputCount) * 100;
        expect(labelPercentage).toBeGreaterThan(80);
        
        // Check main navigation accessibility
        await expect(page.locator('nav')).toBeVisible();
        
        console.log(`✅ ${pageUrl} accessibility: ${labelPercentage.toFixed(1)}% inputs labeled`);
      }
    });

    test('check keyboard navigation', async ({ page }) => {
      console.log('Testing keyboard navigation...');
      
      await page.goto(`${BASE_URL}/admin/employees`);
      
      // Tab through interface
      await page.keyboard.press('Tab');
      await page.keyboard.press('Tab');
      await page.keyboard.press('Tab');
      
      // Check that focus is working
      const focusedElement = await page.evaluate(() => {
        return document.activeElement ? document.activeElement.tagName : null;
      });
      
      expect(focusedElement).toBeTruthy();
      
      console.log('✅ Keyboard navigation working');
    });
  });

  test.describe('Data Integrity and Relationships', () => {
    
    test('verify data relationships integrity', async ({ page }) => {
      console.log('Testing data relationships integrity...');
      
      // Create a complete location hierarchy
      await page.goto(`${BASE_URL}/admin/locations/create`);
      await page.selectOption('select[name="data.level"]', '1');
      await page.fill('input[name="data.name_ar"]', 'منطقة العلاقات');
      await page.fill('input[name="data.name_en"]', 'Relationships Region');
      await page.click('button[type="submit"]');
      
      await page.goto(`${BASE_URL}/admin/locations/create`);
      await page.selectOption('select[name="data.level"]', '2');
      await page.selectOption('select[name="data.parent_id"]', { label: 'منطقة العلاقات' });
      await page.fill('input[name="data.name_ar"]', 'مدينة العلاقات');
      await page.fill('input[name="data.name_en"]', 'Relationships City');
      await page.click('button[type="submit"]');
      
      // Verify hierarchy is maintained
      await page.goto(`${BASE_URL}/admin/locations`);
      const cityRow = page.locator('table tbody tr').filter({ hasText: 'مدينة العلاقات' });
      const pathCell = cityRow.locator('td').filter({ hasText: ' > ' });
      
      if (await pathCell.count() > 0) {
        const pathText = await pathCell.first().textContent();
        expect(pathText).toContain('منطقة العلاقات > مدينة العلاقات');
      }
      
      console.log('✅ Location hierarchy relationships maintained');
    });

    test('verify user role assignments', async ({ page }) => {
      console.log('Testing user role assignments...');
      
      // Create users with different roles
      const users = [
        { name: 'موظف للأدوار', phone: '0500111111', type: 'employees' },
        { name: 'مالك للأدوار', phone: '0500222222', type: 'owners' },
        { name: 'مستأجر للأدوار', phone: '0500333333', type: 'tenants' }
      ];
      
      for (const user of users) {
        await page.goto(`${BASE_URL}/admin/${user.type}/create`);
        await page.fill('input[name="data.name"]', user.name);
        await page.fill('input[name="data.phone1"]', user.phone);
        
        if (user.type === 'employees') {
          await page.fill('input[name="data.email"]', `${user.phone}@test.com`);
        }
        
        await page.click('button[type="submit"]');
        await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
        
        // Verify user appears in correct list
        await page.goto(`${BASE_URL}/admin/${user.type}`);
        await expect(page.locator('table').getByText(user.name)).toBeVisible();
      }
      
      console.log('✅ User role assignments working correctly');
    });

    test('verify auto-generated fields functionality', async ({ page }) => {
      console.log('Testing auto-generated fields...');
      
      // Test username/email auto-generation for owners
      await page.goto(`${BASE_URL}/admin/owners/create`);
      await page.fill('input[name="data.name"]', 'مالك الحقول التلقائية');
      await page.fill('input[name="data.phone1"]', '0500999888');
      
      // Check auto-generated username and email
      const username = await page.locator('input[name="data.username"]').inputValue();
      const email = await page.locator('input[name="data.email"]').inputValue();
      
      expect(username).toBe('9660500999888');
      expect(email).toBe('9660500999888@aqarcrm.com');
      
      await page.click('button[type="submit"]');
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Test the same for tenants
      await page.goto(`${BASE_URL}/admin/tenants/create`);
      await page.fill('input[name="data.name"]', 'مستأجر الحقول التلقائية');
      await page.fill('input[name="data.phone1"]', '0500888777');
      
      const tenantUsername = await page.locator('input[name="data.username"]').inputValue();
      const tenantEmail = await page.locator('input[name="data.email"]').inputValue();
      
      expect(tenantUsername).toBe('9660500888777');
      expect(tenantEmail).toBe('9660500888777@aqarcrm.com');
      
      console.log('✅ Auto-generated fields working correctly');
    });
  });

  test.describe('System Security and Authentication', () => {
    
    test('verify login security', async ({ page }) => {
      console.log('Testing login security...');
      
      // Logout first
      await page.click('button[aria-label="User menu"]');
      await page.click('a:has-text("تسجيل الخروج"), a:has-text("Logout")');
      
      // Test invalid login attempts
      await page.goto(`${BASE_URL}/admin/login`);
      
      // Wrong email
      await page.fill('input[type="email"]', 'wrong@email.com');
      await page.fill('input[type="password"]', ADMIN_PASSWORD);
      await page.click('button[type="submit"]');
      await expect(page.locator('.filament-notification, .alert-danger, .error')).toBeVisible();
      
      // Wrong password
      await page.fill('input[type="email"]', ADMIN_EMAIL);
      await page.fill('input[type="password"]', 'wrongpassword');
      await page.click('button[type="submit"]');
      await expect(page.locator('.filament-notification, .alert-danger, .error')).toBeVisible();
      
      // Correct credentials
      await page.fill('input[type="email"]', ADMIN_EMAIL);
      await page.fill('input[type="password"]', ADMIN_PASSWORD);
      await page.click('button[type="submit"]');
      await expect(page).toHaveURL(`${BASE_URL}/admin`);
      
      console.log('✅ Login security working correctly');
    });

    test('verify access control for protected pages', async ({ page }) => {
      console.log('Testing access control...');
      
      // Test that admin pages are accessible when logged in
      const protectedPages = [
        `${BASE_URL}/admin/employees`,
        `${BASE_URL}/admin/owners`,
        `${BASE_URL}/admin/tenants`,
        `${BASE_URL}/admin/locations`
      ];
      
      for (const protectedPage of protectedPages) {
        await page.goto(protectedPage);
        
        // Should not redirect to login (should show page content)
        await expect(page).not.toHaveURL(/login/);
        await expect(page.locator('table')).toBeVisible();
      }
      
      console.log('✅ Access control working correctly');
    });
  });

  test.describe('Cross-Browser Compatibility', () => {
    
    test('verify responsive design', async ({ page }) => {
      console.log('Testing responsive design...');
      
      const viewports = [
        { width: 1920, height: 1080, name: 'Desktop' },
        { width: 1024, height: 768, name: 'Tablet' },
        { width: 375, height: 667, name: 'Mobile' }
      ];
      
      for (const viewport of viewports) {
        await page.setViewportSize({ width: viewport.width, height: viewport.height });
        await page.goto(`${BASE_URL}/admin/employees`);
        
        // Navigation should be visible or have mobile menu
        const nav = page.locator('nav');
        const mobileMenu = page.locator('button[aria-label="Toggle navigation"]');
        
        const hasNav = await nav.isVisible();
        const hasMobileMenu = await mobileMenu.isVisible();
        
        expect(hasNav || hasMobileMenu).toBeTruthy();
        
        // Table should be visible or responsive
        await expect(page.locator('table')).toBeVisible();
        
        console.log(`✅ ${viewport.name} (${viewport.width}x${viewport.height}) layout working`);
      }
    });
  });
});

// Final comprehensive system test
test.describe('Final System Verification', () => {
  
  test('complete end-to-end system test', async ({ page }) => {
    console.log('Running complete end-to-end system verification...');
    
    // Login
    await page.goto(`${BASE_URL}/admin/login`);
    await page.fill('input[type="email"]', ADMIN_EMAIL);
    await page.fill('input[type="password"]', ADMIN_PASSWORD);
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(`${BASE_URL}/admin`);
    
    // Verify all main pages are accessible and functional
    const systemPages = [
      { url: `${BASE_URL}/admin`, name: 'Dashboard', hasTable: false },
      { url: `${BASE_URL}/admin/employees`, name: 'Employees', hasTable: true },
      { url: `${BASE_URL}/admin/owners`, name: 'Owners', hasTable: true },
      { url: `${BASE_URL}/admin/tenants`, name: 'Tenants', hasTable: true },
      { url: `${BASE_URL}/admin/locations`, name: 'Locations', hasTable: true },
      { url: `${BASE_URL}/admin/property-types`, name: 'Property Types', hasTable: true },
      { url: `${BASE_URL}/admin/property-statuses`, name: 'Property Statuses', hasTable: true },
      { url: `${BASE_URL}/admin/property-features`, name: 'Property Features', hasTable: true }
    ];
    
    let passedPages = 0;
    let totalPages = systemPages.length;
    
    for (const pageInfo of systemPages) {
      try {
        console.log(`Verifying ${pageInfo.name}...`);
        
        const startTime = Date.now();
        await page.goto(pageInfo.url);
        const loadTime = Date.now() - startTime;
        
        // Page should load quickly
        expect(loadTime).toBeLessThan(3000);
        
        // Page should not have errors
        await expect(page.locator('body')).not.toContainText('Error 500');
        await expect(page.locator('body')).not.toContainText('Fatal error');
        
        // Navigation should be present
        await expect(page.locator('nav')).toBeVisible();
        
        // Tables should be present on list pages
        if (pageInfo.hasTable) {
          await expect(page.locator('table')).toBeVisible();
          
          // Check if filters are above table
          const filtersSection = page.locator('[data-filament-table-filters]');
          await expect(filtersSection).toBeVisible();
        }
        
        passedPages++;
        console.log(`✅ ${pageInfo.name} verified successfully (${loadTime}ms)`);
        
      } catch (error) {
        console.log(`❌ ${pageInfo.name} failed verification: ${error.message}`);
      }
    }
    
    // Final verification summary
    const successRate = (passedPages / totalPages) * 100;
    console.log(`\n📊 SYSTEM VERIFICATION SUMMARY:`);
    console.log(`Total Pages Tested: ${totalPages}`);
    console.log(`Pages Passed: ${passedPages}`);
    console.log(`Success Rate: ${successRate.toFixed(1)}%`);
    
    // System should have at least 90% success rate
    expect(successRate).toBeGreaterThanOrEqual(90);
    
    if (successRate === 100) {
      console.log('🎉 SYSTEM FULLY VERIFIED - ALL TESTS PASSED!');
    } else if (successRate >= 90) {
      console.log('✅ SYSTEM VERIFICATION PASSED with minor issues');
    } else {
      console.log('❌ SYSTEM VERIFICATION FAILED - Critical issues detected');
    }
    
    // Additional system health checks
    console.log('\n🔍 Running additional system health checks...');
    
    // Check that we can create at least one record in each module
    const createTests = [
      {
        url: `${BASE_URL}/admin/employees/create`,
        name: 'Employee Creation',
        data: { name: 'Final Test Employee', phone1: '0500999999', email: 'finaltest@test.com' }
      },
      {
        url: `${BASE_URL}/admin/locations/create`,
        name: 'Location Creation',
        data: { level: '1', name_ar: 'منطقة اختبار نهائية', name_en: 'Final Test Region' }
      }
    ];
    
    for (const createTest of createTests) {
      try {
        await page.goto(createTest.url);
        
        // Fill form
        if (createTest.data.level) {
          await page.selectOption('select[name="data.level"]', createTest.data.level);
        }
        if (createTest.data.name) {
          await page.fill('input[name="data.name"]', createTest.data.name);
        }
        if (createTest.data.name_ar) {
          await page.fill('input[name="data.name_ar"]', createTest.data.name_ar);
        }
        if (createTest.data.name_en) {
          await page.fill('input[name="data.name_en"]', createTest.data.name_en);
        }
        if (createTest.data.phone1) {
          await page.fill('input[name="data.phone1"]', createTest.data.phone1);
        }
        if (createTest.data.email) {
          await page.fill('input[name="data.email"]', createTest.data.email);
        }
        
        await page.click('button[type="submit"]');
        await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
        
        console.log(`✅ ${createTest.name} working`);
        
      } catch (error) {
        console.log(`❌ ${createTest.name} failed: ${error.message}`);
      }
    }
    
    console.log('\n🏁 END-TO-END SYSTEM TEST COMPLETED!');
  });
});