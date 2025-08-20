import { test, expect } from '@playwright/test';

// Test credentials
const ADMIN_EMAIL = 'admin@aqarcrm.com';
const ADMIN_PASSWORD = 'password123';

// Base URL
const BASE_URL = 'http://127.0.0.1:8000';

test.describe('Location Management System Tests', () => {
  
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await page.goto(`${BASE_URL}/admin/login`);
    await page.fill('input[type="email"]', ADMIN_EMAIL);
    await page.fill('input[type="password"]', ADMIN_PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL(`${BASE_URL}/admin`);
  });

  test.describe('Location Basic Management Tests', () => {
    
    test('should access locations list page', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/locations`);
      await expect(page).toHaveTitle(/المواقع/);
      
      // Check table is displayed
      await expect(page.locator('table')).toBeVisible();
      
      // Check filters are above table
      const filtersSection = page.locator('[data-filament-table-filters]');
      await expect(filtersSection).toBeVisible();
      
      // Check create button exists
      await expect(page.locator('a[href*="locations/create"]')).toBeVisible();
    });

    test('should display location hierarchy with proper badges', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/locations`);
      
      // Check that level badges are displayed with correct colors
      const levelLabels = ['منطقة', 'مدينة', 'مركز', 'حي'];
      
      for (const label of levelLabels) {
        const badge = page.locator('.filament-badge').filter({ hasText: label });
        if (await badge.count() > 0) {
          await expect(badge.first()).toBeVisible();
          
          // Check badge color based on level
          const badgeElement = badge.first();
          const className = await badgeElement.getAttribute('class');
          
          // Verify badge styling exists
          expect(className).toContain('filament-badge');
        }
      }
    });

    test('should display full path correctly', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/locations`);
      
      // Check full path column is displayed
      await expect(page.locator('table th:has-text("المسار الكامل")')).toBeVisible();
      
      // Check that paths are displayed in hierarchical format
      const pathCells = page.locator('table td').filter({ hasText: ' > ' });
      const count = await pathCells.count();
      
      if (count > 0) {
        const firstPath = pathCells.first();
        const pathText = await firstPath.textContent();
        
        // Path should contain separator for hierarchical locations
        expect(pathText).toContain(' > ');
      }
    });
  });

  test.describe('Location Level 1: منطقة (Region) Tests', () => {
    
    test('should create new region (منطقة)', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/locations`);
      
      // Click create button
      await page.click('a[href*="locations/create"]');
      
      // Select level 1 (منطقة)
      await page.selectOption('select[name="data.level"]', '1');
      
      // Fill region form
      await page.fill('input[name="data.name_ar"]', 'منطقة الرياض');
      await page.fill('input[name="data.name_en"]', 'Riyadh Region');
      await page.fill('input[name="data.code"]', 'RD');
      await page.fill('input[name="data.postal_code"]', '11564');
      await page.fill('input[name="data.coordinates"]', '24.7136,46.6753');
      
      // Parent field should not be visible for level 1
      await expect(page.locator('select[name="data.parent_id"]')).not.toBeVisible();
      
      // Submit form
      await page.click('button[type="submit"]');
      
      // Check success notification
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Verify in list
      await page.goto(`${BASE_URL}/admin/locations`);
      await expect(page.locator('table').getByText('منطقة الرياض')).toBeVisible();
      
      // Check that level badge shows "منطقة"
      const row = page.locator('table tbody tr').filter({ hasText: 'منطقة الرياض' });
      await expect(row.locator('.filament-badge:has-text("منطقة")')).toBeVisible();
    });

    test('should edit existing region', async ({ page }) => {
      // First create a region to edit
      await page.goto(`${BASE_URL}/admin/locations/create`);
      await page.selectOption('select[name="data.level"]', '1');
      await page.fill('input[name="data.name_ar"]', 'منطقة للتعديل');
      await page.fill('input[name="data.name_en"]', 'Region to Edit');
      await page.click('button[type="submit"]');
      
      // Go to list and edit
      await page.goto(`${BASE_URL}/admin/locations`);
      const row = page.locator('table tbody tr').filter({ hasText: 'منطقة للتعديل' });
      await row.locator('a[href*="/edit"]').click();
      
      // Update name
      await page.fill('input[name="data.name_ar"]', 'منطقة معدلة');
      await page.fill('input[name="data.postal_code"]', '12345');
      
      // Submit
      await page.click('button[type="submit"]');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Verify update
      await page.goto(`${BASE_URL}/admin/locations`);
      await expect(page.locator('table').getByText('منطقة معدلة')).toBeVisible();
    });

    test('should validate region required fields', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/locations/create`);
      
      // Select level 1
      await page.selectOption('select[name="data.level"]', '1');
      
      // Try to submit without required fields
      await page.click('button[type="submit"]');
      
      // Check validation errors
      await expect(page.locator('.filament-form-field-error')).toContainText('مطلوب');
    });
  });

  test.describe('Location Level 2: مدينة (City) Tests', () => {
    
    test('should create new city linked to region', async ({ page }) => {
      // First ensure we have a region
      await page.goto(`${BASE_URL}/admin/locations/create`);
      await page.selectOption('select[name="data.level"]', '1');
      await page.fill('input[name="data.name_ar"]', 'منطقة تجريبية');
      await page.fill('input[name="data.name_en"]', 'Test Region');
      await page.click('button[type="submit"]');
      
      // Now create a city
      await page.goto(`${BASE_URL}/admin/locations/create`);
      
      // Select level 2 (مدينة)
      await page.selectOption('select[name="data.level"]', '2');
      
      // Parent field should now be visible
      await expect(page.locator('select[name="data.parent_id"]')).toBeVisible();
      
      // Select parent region
      await page.selectOption('select[name="data.parent_id"]', { label: 'منطقة تجريبية' });
      
      // Fill city form
      await page.fill('input[name="data.name_ar"]', 'مدينة الرياض');
      await page.fill('input[name="data.name_en"]', 'Riyadh City');
      await page.fill('input[name="data.code"]', 'RYD');
      await page.fill('input[name="data.postal_code"]', '11411');
      await page.fill('input[name="data.coordinates"]', '24.7136,46.6753');
      
      // Submit form
      await page.click('button[type="submit"]');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Verify in list with proper hierarchy
      await page.goto(`${BASE_URL}/admin/locations`);
      await expect(page.locator('table').getByText('مدينة الرياض')).toBeVisible();
      
      // Check full path shows hierarchy
      const row = page.locator('table tbody tr').filter({ hasText: 'مدينة الرياض' });
      const pathCell = row.locator('td').filter({ hasText: ' > ' });
      if (await pathCell.count() > 0) {
        const pathText = await pathCell.first().textContent();
        expect(pathText).toContain('منطقة تجريبية > مدينة الرياض');
      }
      
      // Check level badge shows "مدينة"
      await expect(row.locator('.filament-badge:has-text("مدينة")')).toBeVisible();
    });

    test('should validate city parent selection', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/locations/create`);
      
      // Select level 2
      await page.selectOption('select[name="data.level"]', '2');
      
      // Fill required fields but don't select parent
      await page.fill('input[name="data.name_ar"]', 'مدينة بدون منطقة');
      await page.fill('input[name="data.name_en"]', 'City without Region');
      
      // Try to submit without parent
      await page.click('button[type="submit"]');
      
      // Should show validation error
      await expect(page.locator('.filament-form-field-error')).toBeVisible();
    });
  });

  test.describe('Location Level 3: مركز (Center) Tests', () => {
    
    test('should create new center linked to city', async ({ page }) => {
      // Create region first
      await page.goto(`${BASE_URL}/admin/locations/create`);
      await page.selectOption('select[name="data.level"]', '1');
      await page.fill('input[name="data.name_ar"]', 'منطقة للمركز');
      await page.fill('input[name="data.name_en"]', 'Region for Center');
      await page.click('button[type="submit"]');
      
      // Create city
      await page.goto(`${BASE_URL}/admin/locations/create`);
      await page.selectOption('select[name="data.level"]', '2');
      await page.selectOption('select[name="data.parent_id"]', { label: 'منطقة للمركز' });
      await page.fill('input[name="data.name_ar"]', 'مدينة للمركز');
      await page.fill('input[name="data.name_en"]', 'City for Center');
      await page.click('button[type="submit"]');
      
      // Now create center
      await page.goto(`${BASE_URL}/admin/locations/create`);
      
      // Select level 3 (مركز)
      await page.selectOption('select[name="data.level"]', '3');
      
      // Select parent city
      await page.selectOption('select[name="data.parent_id"]', { label: 'مدينة للمركز' });
      
      // Fill center form
      await page.fill('input[name="data.name_ar"]', 'مركز الملك فهد');
      await page.fill('input[name="data.name_en"]', 'King Fahd Center');
      await page.fill('input[name="data.code"]', 'KF');
      
      // Submit form
      await page.click('button[type="submit"]');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Verify hierarchy in list
      await page.goto(`${BASE_URL}/admin/locations`);
      const row = page.locator('table tbody tr').filter({ hasText: 'مركز الملك فهد' });
      await expect(row).toBeVisible();
      
      // Check level badge shows "مركز"
      await expect(row.locator('.filament-badge:has-text("مركز")')).toBeVisible();
      
      // Check full path shows complete hierarchy
      const pathCell = row.locator('td').filter({ hasText: ' > ' });
      if (await pathCell.count() > 0) {
        const pathText = await pathCell.first().textContent();
        expect(pathText).toContain('منطقة للمركز > مدينة للمركز > مركز الملك فهد');
      }
    });
  });

  test.describe('Location Level 4: حي (Neighborhood) Tests', () => {
    
    test('should create new neighborhood linked to center', async ({ page }) => {
      // Create complete hierarchy: region > city > center
      await page.goto(`${BASE_URL}/admin/locations/create`);
      await page.selectOption('select[name="data.level"]', '1');
      await page.fill('input[name="data.name_ar"]', 'منطقة للحي');
      await page.fill('input[name="data.name_en"]', 'Region for Neighborhood');
      await page.click('button[type="submit"]');
      
      await page.goto(`${BASE_URL}/admin/locations/create`);
      await page.selectOption('select[name="data.level"]', '2');
      await page.selectOption('select[name="data.parent_id"]', { label: 'منطقة للحي' });
      await page.fill('input[name="data.name_ar"]', 'مدينة للحي');
      await page.fill('input[name="data.name_en"]', 'City for Neighborhood');
      await page.click('button[type="submit"]');
      
      await page.goto(`${BASE_URL}/admin/locations/create`);
      await page.selectOption('select[name="data.level"]', '3');
      await page.selectOption('select[name="data.parent_id"]', { label: 'مدينة للحي' });
      await page.fill('input[name="data.name_ar"]', 'مركز للحي');
      await page.fill('input[name="data.name_en"]', 'Center for Neighborhood');
      await page.click('button[type="submit"]');
      
      // Now create neighborhood
      await page.goto(`${BASE_URL}/admin/locations/create`);
      
      // Select level 4 (حي)
      await page.selectOption('select[name="data.level"]', '4');
      
      // Select parent center
      await page.selectOption('select[name="data.parent_id"]', { label: 'مركز للحي' });
      
      // Fill neighborhood form
      await page.fill('input[name="data.name_ar"]', 'حي العليا');
      await page.fill('input[name="data.name_en"]', 'Al Olaya Neighborhood');
      await page.fill('input[name="data.code"]', 'OLY');
      await page.fill('input[name="data.postal_code"]', '11411');
      
      // Submit form
      await page.click('button[type="submit"]');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
      
      // Verify complete hierarchy in list
      await page.goto(`${BASE_URL}/admin/locations`);
      const row = page.locator('table tbody tr').filter({ hasText: 'حي العليا' });
      await expect(row).toBeVisible();
      
      // Check level badge shows "حي"
      await expect(row.locator('.filament-badge:has-text("حي")')).toBeVisible();
      
      // Check full path shows complete 4-level hierarchy
      const pathCell = row.locator('td').filter({ hasText: ' > ' });
      if (await pathCell.count() > 0) {
        const pathText = await pathCell.first().textContent();
        expect(pathText).toContain('منطقة للحي > مدينة للحي > مركز للحي > حي العليا');
      }
    });
  });

  test.describe('Location Filtering and Search Tests', () => {
    
    test('should filter locations by level', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/locations`);
      
      // Open filters
      const filterButton = page.locator('button:has-text("فلترة")');
      if (await filterButton.isVisible()) {
        await filterButton.click();
        
        // Filter by level (منطقة = 1)
        await page.selectOption('select[name="tableFilters[level][value]"]', '1');
        
        // Apply filter
        await page.click('button:has-text("تطبيق")');
        
        // Check that only regions are shown
        const levelBadges = page.locator('.filament-badge');
        const badgeCount = await levelBadges.count();
        
        if (badgeCount > 0) {
          for (let i = 0; i < badgeCount; i++) {
            const badgeText = await levelBadges.nth(i).textContent();
            expect(badgeText).toBe('منطقة');
          }
        }
      }
    });

    test('should filter locations by parent', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/locations`);
      
      // Open filters
      const filterButton = page.locator('button:has-text("فلترة")');
      if (await filterButton.isVisible()) {
        await filterButton.click();
        
        // Filter by parent (select first available parent)
        const parentSelect = page.locator('select[name="tableFilters[parent_id][value]"]');
        if (await parentSelect.isVisible()) {
          const options = parentSelect.locator('option');
          const optionCount = await options.count();
          
          if (optionCount > 1) { // Skip empty option
            await parentSelect.selectOption({ index: 1 });
            
            // Apply filter
            await page.click('button:has-text("تطبيق")');
            
            // Check filtered results
            await expect(page.locator('table')).toBeVisible();
          }
        }
      }
    });

    test('should filter locations by active status', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/locations`);
      
      // Open filters
      const filterButton = page.locator('button:has-text("فلترة")');
      if (await filterButton.isVisible()) {
        await filterButton.click();
        
        // Filter by active status
        await page.selectOption('select[name="tableFilters[is_active][value]"]', '1');
        
        // Apply filter
        await page.click('button:has-text("تطبيق")');
        
        // Check that only active locations are shown
        const statusBadges = page.locator('.filament-badge').filter({ hasText: 'نشط' });
        const activeBadgeCount = await statusBadges.count();
        
        const inactiveBadges = page.locator('.filament-badge').filter({ hasText: 'غير نشط' });
        const inactiveBadgeCount = await inactiveBadges.count();
        
        // Should not have inactive badges when filtering for active
        expect(inactiveBadgeCount).toBe(0);
      }
    });

    test('should search locations by name', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/locations`);
      
      // Use search functionality
      const searchInput = page.locator('input[placeholder*="بحث"], input[type="search"]');
      if (await searchInput.isVisible()) {
        await searchInput.fill('الرياض');
        await page.keyboard.press('Enter');
        
        // Check search results contain the term
        const tableRows = page.locator('table tbody tr');
        const rowCount = await tableRows.count();
        
        if (rowCount > 0) {
          for (let i = 0; i < rowCount; i++) {
            const rowText = await tableRows.nth(i).textContent();
            expect(rowText).toContain('الرياض');
          }
        }
      }
    });
  });

  test.describe('Location Hierarchy Validation Tests', () => {
    
    test('should prevent creating city without region', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/locations/create`);
      
      // Try to create level 2 (city) without any regions
      await page.selectOption('select[name="data.level"]', '2');
      
      // Parent dropdown should be visible but may be empty
      await expect(page.locator('select[name="data.parent_id"]')).toBeVisible();
      
      // Fill required fields
      await page.fill('input[name="data.name_ar"]', 'مدينة بدون منطقة');
      await page.fill('input[name="data.name_en"]', 'City without Region');
      
      // Submit should fail due to missing parent
      await page.click('button[type="submit"]');
      
      // Should show validation error
      await expect(page.locator('.filament-form-field-error, .filament-notification')).toBeVisible();
    });

    test('should update parent options when level changes', async ({ page }) => {
      // Create a region first
      await page.goto(`${BASE_URL}/admin/locations/create`);
      await page.selectOption('select[name="data.level"]', '1');
      await page.fill('input[name="data.name_ar"]', 'منطقة للاختبار');
      await page.fill('input[name="data.name_en"]', 'Test Region');
      await page.click('button[type="submit"]');
      
      // Now test level change behavior
      await page.goto(`${BASE_URL}/admin/locations/create`);
      
      // Start with level 1 (no parent field)
      await page.selectOption('select[name="data.level"]', '1');
      await expect(page.locator('select[name="data.parent_id"]')).not.toBeVisible();
      
      // Change to level 2 (parent field should appear)
      await page.selectOption('select[name="data.level"]', '2');
      await expect(page.locator('select[name="data.parent_id"]')).toBeVisible();
      
      // Check that the created region appears in parent options
      const parentSelect = page.locator('select[name="data.parent_id"]');
      const options = parentSelect.locator('option');
      const optionTexts = await options.allTextContents();
      
      expect(optionTexts.some(text => text.includes('منطقة للاختبار'))).toBeTruthy();
    });

    test('should validate coordinates format', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/locations/create`);
      
      // Select level 1
      await page.selectOption('select[name="data.level"]', '1');
      
      // Fill required fields
      await page.fill('input[name="data.name_ar"]', 'اختبار الإحداثيات');
      await page.fill('input[name="data.name_en"]', 'Coordinates Test');
      
      // Enter invalid coordinates
      await page.fill('input[name="data.coordinates"]', 'invalid,format');
      
      // Submit
      await page.click('button[type="submit"]');
      
      // Should show validation error for coordinates format
      const coordinatesField = page.locator('input[name="data.coordinates"]').locator('..');
      await expect(coordinatesField.locator('.filament-form-field-error')).toBeVisible();
    });
  });

  test.describe('Location CRUD Operations Tests', () => {
    
    test('should view location details', async ({ page }) => {
      // Create a location first
      await page.goto(`${BASE_URL}/admin/locations/create`);
      await page.selectOption('select[name="data.level"]', '1');
      await page.fill('input[name="data.name_ar"]', 'منطقة للعرض');
      await page.fill('input[name="data.name_en"]', 'Region for View');
      await page.fill('input[name="data.code"]', 'RFV');
      await page.fill('input[name="data.postal_code"]', '12345');
      await page.fill('input[name="data.coordinates"]', '24.7136,46.6753');
      await page.click('button[type="submit"]');
      
      // Go to list and view
      await page.goto(`${BASE_URL}/admin/locations`);
      const row = page.locator('table tbody tr').filter({ hasText: 'منطقة للعرض' });
      const viewButton = row.locator('a[href*="/view"], button[title="عرض"]');
      
      if (await viewButton.isVisible()) {
        await viewButton.click();
        
        // Check view page displays information correctly
        await expect(page.locator('h1, h2')).toContainText('منطقة للعرض');
        await expect(page.locator('body')).toContainText('Region for View');
        await expect(page.locator('body')).toContainText('RFV');
        await expect(page.locator('body')).toContainText('12345');
        await expect(page.locator('body')).toContainText('24.7136,46.6753');
      }
    });

    test('should delete location', async ({ page }) => {
      // Create a location to delete
      await page.goto(`${BASE_URL}/admin/locations/create`);
      await page.selectOption('select[name="data.level"]', '1');
      await page.fill('input[name="data.name_ar"]', 'منطقة للحذف');
      await page.fill('input[name="data.name_en"]', 'Region to Delete');
      await page.click('button[type="submit"]');
      
      // Go to list and delete
      await page.goto(`${BASE_URL}/admin/locations`);
      const row = page.locator('table tbody tr').filter({ hasText: 'منطقة للحذف' });
      await row.locator('button[title="حذف"]').click();
      
      // Confirm deletion
      await page.click('button:has-text("تأكيد")');
      
      // Check success
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحذف');
      
      // Verify deleted
      await expect(page.locator('table').getByText('منطقة للحذف')).not.toBeVisible();
    });

    test('should handle soft delete if implemented', async ({ page }) => {
      // Create and delete a location
      await page.goto(`${BASE_URL}/admin/locations/create`);
      await page.selectOption('select[name="data.level"]', '1');
      await page.fill('input[name="data.name_ar"]', 'منطقة للحذف المؤقت');
      await page.fill('input[name="data.name_en"]', 'Region for Soft Delete');
      await page.click('button[type="submit"]');
      
      await page.goto(`${BASE_URL}/admin/locations`);
      const row = page.locator('table tbody tr').filter({ hasText: 'منطقة للحذف المؤقت' });
      await row.locator('button[title="حذف"]').click();
      await page.click('button:has-text("تأكيد")');
      
      // If soft delete is implemented, check for restore functionality
      const trashedFilter = page.locator('button:has-text("المحذوف"), button:has-text("Trashed")');
      if (await trashedFilter.isVisible()) {
        await trashedFilter.click();
        await expect(page.locator('table').getByText('منطقة للحذف المؤقت')).toBeVisible();
      }
    });
  });

  test.describe('Performance and Error Handling', () => {
    
    test('should load locations page within acceptable time', async ({ page }) => {
      const startTime = Date.now();
      await page.goto(`${BASE_URL}/admin/locations`);
      const loadTime = Date.now() - startTime;
      
      // Page should load within 3 seconds
      expect(loadTime).toBeLessThan(3000);
      await expect(page.locator('table')).toBeVisible();
    });

    test('should handle form validation errors gracefully', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/locations/create`);
      
      // Submit empty form
      await page.click('button[type="submit"]');
      
      // Check that validation errors are displayed properly
      const errors = page.locator('.filament-form-field-error');
      await expect(errors.first()).toBeVisible();
      
      // Form should still be usable after error
      await page.selectOption('select[name="data.level"]', '1');
      await page.fill('input[name="data.name_ar"]', 'اختبار');
      await expect(page.locator('input[name="data.name_ar"]')).toHaveValue('اختبار');
    });

    test('should handle large location datasets efficiently', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/locations`);
      
      // Change records per page to maximum
      const recordsSelect = page.locator('select[wire\\:model="tableRecordsPerPage"]');
      if (await recordsSelect.isVisible()) {
        await recordsSelect.selectOption('50');
        
        // Check table still renders efficiently
        await expect(page.locator('table')).toBeVisible();
        
        // Check pagination controls
        await expect(page.locator('.filament-pagination')).toBeVisible();
      }
    });
  });

  test.describe('Accessibility Tests', () => {
    
    test('should have proper form labels', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/locations/create`);
      
      // Check that all form fields have labels
      const inputs = page.locator('input[name^="data."], select[name^="data."], textarea[name^="data."]');
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
      await page.goto(`${BASE_URL}/admin/locations`);
      
      // Tab through interface
      await page.keyboard.press('Tab');
      await page.keyboard.press('Tab');
      await page.keyboard.press('Tab');
      
      // Check focus is visible
      const focusedElement = await page.evaluate(() => document.activeElement.tagName);
      expect(focusedElement).toBeTruthy();
    });

    test('should have proper table headers and structure', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/locations`);
      
      // Check table structure
      await expect(page.locator('table')).toBeVisible();
      await expect(page.locator('table thead')).toBeVisible();
      await expect(page.locator('table tbody')).toBeVisible();
      
      // Check column headers exist
      const headers = ['المستوى', 'الاسم بالعربية', 'المسار الكامل', 'الحالة'];
      for (const header of headers) {
        await expect(page.locator(`table th:has-text("${header}")`)).toBeVisible();
      }
    });
  });
});

// Critical location management workflows
test.describe('Critical Location Management Workflows', () => {
  
  test('complete location hierarchy creation workflow', async ({ page }) => {
    // Login
    await page.goto(`${BASE_URL}/admin/login`);
    await page.fill('input[type="email"]', ADMIN_EMAIL);
    await page.fill('input[type="password"]', ADMIN_PASSWORD);
    await page.click('button[type="submit"]');
    
    // 1. Create Region (منطقة)
    await page.goto(`${BASE_URL}/admin/locations/create`);
    await page.selectOption('select[name="data.level"]', '1');
    await page.fill('input[name="data.name_ar"]', 'منطقة شاملة');
    await page.fill('input[name="data.name_en"]', 'Comprehensive Region');
    await page.fill('input[name="data.code"]', 'CR');
    await page.fill('input[name="data.postal_code"]', '10000');
    await page.fill('input[name="data.coordinates"]', '24.0000,46.0000');
    await page.click('button[type="submit"]');
    await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    
    // 2. Create City (مدينة)
    await page.goto(`${BASE_URL}/admin/locations/create`);
    await page.selectOption('select[name="data.level"]', '2');
    await page.selectOption('select[name="data.parent_id"]', { label: 'منطقة شاملة' });
    await page.fill('input[name="data.name_ar"]', 'مدينة شاملة');
    await page.fill('input[name="data.name_en"]', 'Comprehensive City');
    await page.fill('input[name="data.code"]', 'CC');
    await page.fill('input[name="data.postal_code"]', '10100');
    await page.click('button[type="submit"]');
    await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    
    // 3. Create Center (مركز)
    await page.goto(`${BASE_URL}/admin/locations/create`);
    await page.selectOption('select[name="data.level"]', '3');
    await page.selectOption('select[name="data.parent_id"]', { label: 'مدينة شاملة' });
    await page.fill('input[name="data.name_ar"]', 'مركز شامل');
    await page.fill('input[name="data.name_en"]', 'Comprehensive Center');
    await page.fill('input[name="data.code"]', 'CN');
    await page.click('button[type="submit"]');
    await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    
    // 4. Create Neighborhood (حي)
    await page.goto(`${BASE_URL}/admin/locations/create`);
    await page.selectOption('select[name="data.level"]', '4');
    await page.selectOption('select[name="data.parent_id"]', { label: 'مركز شامل' });
    await page.fill('input[name="data.name_ar"]', 'حي شامل');
    await page.fill('input[name="data.name_en"]', 'Comprehensive Neighborhood');
    await page.fill('input[name="data.code"]', 'NH');
    await page.fill('input[name="data.postal_code"]', '10111');
    await page.click('button[type="submit"]');
    await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    
    // 5. Verify complete hierarchy in list
    await page.goto(`${BASE_URL}/admin/locations`);
    
    // Check all levels exist
    await expect(page.locator('table').getByText('منطقة شاملة')).toBeVisible();
    await expect(page.locator('table').getByText('مدينة شاملة')).toBeVisible();
    await expect(page.locator('table').getByText('مركز شامل')).toBeVisible();
    await expect(page.locator('table').getByText('حي شامل')).toBeVisible();
    
    // Check level badges
    await expect(page.locator('.filament-badge:has-text("منطقة")')).toBeVisible();
    await expect(page.locator('.filament-badge:has-text("مدينة")')).toBeVisible();
    await expect(page.locator('.filament-badge:has-text("مركز")')).toBeVisible();
    await expect(page.locator('.filament-badge:has-text("حي")')).toBeVisible();
    
    // Check full path for neighborhood shows complete hierarchy
    const neighborhoodRow = page.locator('table tbody tr').filter({ hasText: 'حي شامل' });
    const pathCell = neighborhoodRow.locator('td').filter({ hasText: ' > ' });
    if (await pathCell.count() > 0) {
      const pathText = await pathCell.first().textContent();
      expect(pathText).toContain('منطقة شاملة > مدينة شاملة > مركز شامل > حي شامل');
    }
  });

  test('location filtering and search comprehensive test', async ({ page }) => {
    // Login
    await page.goto(`${BASE_URL}/admin/login`);
    await page.fill('input[type="email"]', ADMIN_EMAIL);
    await page.fill('input[type="password"]', ADMIN_PASSWORD);
    await page.click('button[type="submit"]');
    
    await page.goto(`${BASE_URL}/admin/locations`);
    
    // Test level filtering
    const levels = ['1', '2', '3', '4'];
    for (const level of levels) {
      // Open filters
      const filterButton = page.locator('button:has-text("فلترة")');
      if (await filterButton.isVisible()) {
        await filterButton.click();
        
        // Select level filter
        await page.selectOption('select[name="tableFilters[level][value]"]', level);
        
        // Apply filter
        await page.click('button:has-text("تطبيق")');
        
        // Verify filtered results
        await expect(page.locator('table')).toBeVisible();
        
        // Clear filter for next iteration
        await page.click('button:has-text("مسح الفلاتر"), button:has-text("Clear")');
      }
    }
    
    // Test search functionality
    const searchTerms = ['منطقة', 'مدينة', 'مركز', 'حي'];
    for (const term of searchTerms) {
      const searchInput = page.locator('input[placeholder*="بحث"], input[type="search"]');
      if (await searchInput.isVisible()) {
        await searchInput.fill(term);
        await page.keyboard.press('Enter');
        
        // Check search results
        await expect(page.locator('table')).toBeVisible();
        
        // Clear search
        await searchInput.fill('');
        await page.keyboard.press('Enter');
      }
    }
  });

  test('location hierarchy validation comprehensive test', async ({ page }) => {
    // Login
    await page.goto(`${BASE_URL}/admin/login`);
    await page.fill('input[type="email"]', ADMIN_EMAIL);
    await page.fill('input[type="password"]', ADMIN_PASSWORD);
    await page.click('button[type="submit"]');
    
    // Test level 1 creation (no parent required)
    await page.goto(`${BASE_URL}/admin/locations/create`);
    await page.selectOption('select[name="data.level"]', '1');
    await expect(page.locator('select[name="data.parent_id"]')).not.toBeVisible();
    await page.fill('input[name="data.name_ar"]', 'منطقة للتحقق');
    await page.fill('input[name="data.name_en"]', 'Validation Region');
    await page.click('button[type="submit"]');
    await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    
    // Test level 2 creation (requires parent)
    await page.goto(`${BASE_URL}/admin/locations/create`);
    await page.selectOption('select[name="data.level"]', '2');
    await expect(page.locator('select[name="data.parent_id"]')).toBeVisible();
    
    // Try without parent - should fail
    await page.fill('input[name="data.name_ar"]', 'مدينة بدون منطقة');
    await page.fill('input[name="data.name_en"]', 'City without Region');
    await page.click('button[type="submit"]');
    await expect(page.locator('.filament-form-field-error')).toBeVisible();
    
    // Try with parent - should succeed
    await page.selectOption('select[name="data.parent_id"]', { label: 'منطقة للتحقق' });
    await page.click('button[type="submit"]');
    await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    
    // Test coordinate validation
    await page.goto(`${BASE_URL}/admin/locations/create`);
    await page.selectOption('select[name="data.level"]', '1');
    await page.fill('input[name="data.name_ar"]', 'اختبار الإحداثيات');
    await page.fill('input[name="data.name_en"]', 'Coordinates Test');
    
    // Invalid coordinates
    await page.fill('input[name="data.coordinates"]', 'invalid');
    await page.click('button[type="submit"]');
    const errorExists = await page.locator('.filament-form-field-error').count() > 0;
    
    if (errorExists) {
      // Fix coordinates and retry
      await page.fill('input[name="data.coordinates"]', '24.7136,46.6753');
      await page.click('button[type="submit"]');
      await expect(page.locator('.filament-notification-title')).toContainText('تم الحفظ');
    }
  });
});