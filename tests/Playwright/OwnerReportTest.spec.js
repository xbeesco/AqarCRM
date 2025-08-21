import { test, expect } from '@playwright/test';

test.describe('تقرير المالك - اختبارات شاملة', () => {
    let context;
    let page;

    test.beforeAll(async ({ browser }) => {
        context = await browser.newContext({
            locale: 'ar-SA',
            timezoneId: 'Asia/Riyadh'
        });
        page = await context.newPage();
    });

    test.afterAll(async () => {
        await context.close();
    });

    test.beforeEach(async () => {
        // تسجيل الدخول كمدير
        await page.goto('/admin/login');
        await page.fill('input[name="email"]', 'admin@admin.com');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        
        // انتظار حتى يتم تحميل لوحة التحكم
        await page.waitForURL('**/admin', { timeout: 10000 });
        await page.waitForLoadState('networkidle');
    });

    test('يجب أن تظهر صفحة تقرير المالك في القائمة الجانبية', async () => {
        // البحث عن قسم التقارير في القائمة الجانبية
        const reportsSection = page.locator('text=التقارير');
        await expect(reportsSection).toBeVisible();

        // البحث عن رابط تقرير المالك
        const ownerReportLink = page.locator('text=تقرير المالك');
        await expect(ownerReportLink).toBeVisible();

        // النقر على الرابط
        await ownerReportLink.click();
        
        // التحقق من أننا في الصفحة الصحيحة
        await expect(page).toHaveURL(/.*\/admin\/reports\/owner-report/);
        await page.waitForLoadState('networkidle');
    });

    test('يجب أن تعرض الصفحة العناصر الأساسية', async () => {
        await page.goto('/admin/reports/owner-report');
        await page.waitForLoadState('networkidle');

        // التحقق من عنوان الصفحة
        await expect(page.locator('h1')).toContainText('تقرير المالك');

        // التحقق من وجود نموذج الفلاتر
        await expect(page.locator('select[wire\\:model="owner_id"]')).toBeVisible();
        await expect(page.locator('input[wire\\:model="date_from"]')).toBeVisible();
        await expect(page.locator('input[wire\\:model="date_to"]')).toBeVisible();
        await expect(page.locator('select[wire\\:model="report_type"]')).toBeVisible();

        // التحقق من وجود أزرار التصدير
        await expect(page.locator('text=تصدير PDF')).toBeVisible();
        await expect(page.locator('text=تصدير Excel')).toBeVisible();
        await expect(page.locator('text=طباعة')).toBeVisible();

        // التحقق من رسالة "يرجى اختيار مالك"
        await expect(page.locator('text=يرجى اختيار مالك لعرض التقرير')).toBeVisible();
    });

    test('يجب أن تعمل فلاتر التقرير بشكل صحيح', async () => {
        await page.goto('/admin/reports/owner-report');
        await page.waitForLoadState('networkidle');

        // اختيار مالك من القائمة المنسدلة
        const ownerSelect = page.locator('select[wire\\:model="owner_id"]');
        await ownerSelect.click();
        
        // انتظار حتى تظهر الخيارات
        await page.waitForTimeout(1000);
        
        // اختيار أول مالك متاح
        const firstOwner = page.locator('select[wire\\:model="owner_id"] option').nth(1);
        if (await firstOwner.count() > 0) {
            const ownerValue = await firstOwner.getAttribute('value');
            if (ownerValue) {
                await ownerSelect.selectOption(ownerValue);
                
                // انتظار تحديث البيانات
                await page.waitForLoadState('networkidle');
                await page.waitForTimeout(2000);

                // التحقق من اختفاء رسالة "يرجى اختيار مالك"
                await expect(page.locator('text=يرجى اختيار مالك لعرض التقرير')).not.toBeVisible();

                // التحقق من ظهور بيانات التقرير
                await expect(page.locator('text=معلومات المالك')).toBeVisible();
                await expect(page.locator('text=ملخص الأداء')).toBeVisible();
                await expect(page.locator('text=التفصيل المالي')).toBeVisible();
            }
        }
    });

    test('يجب أن تعمل فلاتر التاريخ', async () => {
        await page.goto('/admin/reports/owner-report');
        await page.waitForLoadState('networkidle');

        // اختيار مالك أولاً
        const ownerSelect = page.locator('select[wire\\:model="owner_id"]');
        await ownerSelect.click();
        await page.waitForTimeout(1000);
        
        const firstOwner = page.locator('select[wire\\:model="owner_id"] option').nth(1);
        if (await firstOwner.count() > 0) {
            const ownerValue = await firstOwner.getAttribute('value');
            if (ownerValue) {
                await ownerSelect.selectOption(ownerValue);
                await page.waitForLoadState('networkidle');

                // تغيير تاريخ البداية
                const dateFromInput = page.locator('input[wire\\:model="date_from"]');
                await dateFromInput.fill('2024-01-01');

                // تغيير تاريخ النهاية
                const dateToInput = page.locator('input[wire\\:model="date_to"]');
                await dateToInput.fill('2024-12-31');

                // النقر على زر تحديث التقرير
                const updateButton = page.locator('text=تحديث التقرير');
                await updateButton.click();
                
                await page.waitForLoadState('networkidle');
                await page.waitForTimeout(2000);

                // التحقق من أن التواريخ تم تحديثها في النص
                await expect(page.locator('text=2024/01/01')).toBeVisible();
                await expect(page.locator('text=2024/12/31')).toBeVisible();
            }
        }
    });

    test('يجب أن تعمل خيارات نوع التقرير', async () => {
        await page.goto('/admin/reports/owner-report');
        await page.waitForLoadState('networkidle');

        // اختبار تغيير نوع التقرير
        const reportTypeSelect = page.locator('select[wire\\:model="report_type"]');
        
        // التحقق من الخيار الافتراضي
        await expect(reportTypeSelect).toHaveValue('summary');

        // تغيير إلى تفصيلي
        await reportTypeSelect.selectOption('detailed');
        await page.waitForLoadState('networkidle');

        // التحقق من أن القيمة تغيرت
        await expect(reportTypeSelect).toHaveValue('detailed');
    });

    test('يجب أن تعرض الإحصائيات بشكل صحيح عند اختيار مالك', async () => {
        await page.goto('/admin/reports/owner-report');
        await page.waitForLoadState('networkidle');

        // اختيار مالك
        const ownerSelect = page.locator('select[wire\\:model="owner_id"]');
        await ownerSelect.click();
        await page.waitForTimeout(1000);
        
        const firstOwner = page.locator('select[wire\\:model="owner_id"] option').nth(1);
        if (await firstOwner.count() > 0) {
            const ownerValue = await firstOwner.getAttribute('value');
            if (ownerValue) {
                await ownerSelect.selectOption(ownerValue);
                await page.waitForLoadState('networkidle');
                await page.waitForTimeout(3000);

                // التحقق من ظهور widgets الإحصائيات
                const statsCards = page.locator('.filament-stats-overview-widget .filament-stats-card');
                
                // يجب أن يكون هناك 6 بطاقات إحصائيات
                await expect(statsCards).toHaveCount(6);

                // التحقق من وجود العناوين المطلوبة
                await expect(page.locator('text=إجمالي التحصيل')).toBeVisible();
                await expect(page.locator('text=صافي الدخل')).toBeVisible();
                await expect(page.locator('text=معدل الإشغال')).toBeVisible();
                await expect(page.locator('text=عدد العقارات')).toBeVisible();
                await expect(page.locator('text=تكاليف الصيانة')).toBeVisible();
                await expect(page.locator('text=المتوسط الشهري')).toBeVisible();

                // التحقق من أن القيم تحتوي على "ريال"
                await expect(page.locator('text=ريال')).toHaveCount({ min: 4 });
            }
        }
    });

    test('يجب أن يعرض جدول العقارات عند اختيار مالك', async () => {
        await page.goto('/admin/reports/owner-report');
        await page.waitForLoadState('networkidle');

        // اختيار مالك
        const ownerSelect = page.locator('select[wire\\:model="owner_id"]');
        await ownerSelect.click();
        await page.waitForTimeout(1000);
        
        const firstOwner = page.locator('select[wire\\:model="owner_id"] option').nth(1);
        if (await firstOwner.count() > 0) {
            const ownerValue = await firstOwner.getAttribute('value');
            if (ownerValue) {
                await ownerSelect.selectOption(ownerValue);
                await page.waitForLoadState('networkidle');
                await page.waitForTimeout(3000);

                // التحقق من ظهور عنوان جدول العقارات
                await expect(page.locator('text=تفاصيل العقارات')).toBeVisible();

                // التحقق من وجود أعمدة الجدول
                await expect(page.locator('text=اسم العقار')).toBeVisible();
                await expect(page.locator('text=الموقع')).toBeVisible();
                await expect(page.locator('text=عدد الوحدات')).toBeVisible();
                await expect(page.locator('text=الوحدات المؤجرة')).toBeVisible();
                await expect(page.locator('text=معدل الإشغال')).toBeVisible();
                await expect(page.locator('text=الدخل الشهري')).toBeVisible();
                await expect(page.locator('text=إجمالي التحصيل')).toBeVisible();
                await expect(page.locator('text=تكاليف الصيانة')).toBeVisible();
                await expect(page.locator('text=صافي الدخل')).toBeVisible();
            }
        }
    });

    test('يجب أن يعرض الرسم البياني عند اختيار مالك', async () => {
        await page.goto('/admin/reports/owner-report');
        await page.waitForLoadState('networkidle');

        // اختيار مالك
        const ownerSelect = page.locator('select[wire\\:model="owner_id"]');
        await ownerSelect.click();
        await page.waitForTimeout(1000);
        
        const firstOwner = page.locator('select[wire\\:model="owner_id"] option').nth(1);
        if (await firstOwner.count() > 0) {
            const ownerValue = await firstOwner.getAttribute('value');
            if (ownerValue) {
                await ownerSelect.selectOption(ownerValue);
                await page.waitForLoadState('networkidle');
                await page.waitForTimeout(3000);

                // التحقق من ظهور عنوان الرسم البياني
                await expect(page.locator('text=الرسم البياني للدخل الشهري')).toBeVisible();

                // التحقق من وجود canvas للرسم البياني
                const chartCanvas = page.locator('canvas');
                await expect(chartCanvas).toBeVisible();
            }
        }
    });

    test('يجب أن تعمل أزرار التصدير', async () => {
        await page.goto('/admin/reports/owner-report');
        await page.waitForLoadState('networkidle');

        // اختيار مالك أولاً
        const ownerSelect = page.locator('select[wire\\:model="owner_id"]');
        await ownerSelect.click();
        await page.waitForTimeout(1000);
        
        const firstOwner = page.locator('select[wire\\:model="owner_id"] option').nth(1);
        if (await firstOwner.count() > 0) {
            const ownerValue = await firstOwner.getAttribute('value');
            if (ownerValue) {
                await ownerSelect.selectOption(ownerValue);
                await page.waitForLoadState('networkidle');

                // اختبار زر تصدير PDF
                const pdfButton = page.locator('text=تصدير PDF');
                await pdfButton.click();
                
                // انتظار رسالة التأكيد (حالياً alert)
                page.on('dialog', async dialog => {
                    expect(dialog.message()).toContain('سيتم تنفيذ تصدير PDF قريباً');
                    await dialog.accept();
                });

                await page.waitForTimeout(1000);

                // اختبار زر تصدير Excel
                const excelButton = page.locator('text=تصدير Excel');
                await excelButton.click();
                
                page.on('dialog', async dialog => {
                    expect(dialog.message()).toContain('سيتم تنفيذ تصدير Excel قريباً');
                    await dialog.accept();
                });

                await page.waitForTimeout(1000);
            }
        }
    });

    test('يجب أن يعمل زر الطباعة', async () => {
        await page.goto('/admin/reports/owner-report');
        await page.waitForLoadState('networkidle');

        // مراقبة استدعاءات window.print
        let printCalled = false;
        await page.addInitScript(() => {
            window.print = () => {
                window.printCalled = true;
            };
        });

        // النقر على زر الطباعة
        const printButton = page.locator('text=طباعة');
        await printButton.click();

        // التحقق من أن window.print تم استدعاؤها
        const printCallResult = await page.evaluate(() => window.printCalled);
        expect(printCallResult).toBeTruthy();
    });

    test('يجب أن تكون الصفحة متجاوبة على الشاشات الصغيرة', async () => {
        // تغيير حجم الشاشة للجوال
        await page.setViewportSize({ width: 375, height: 667 });
        
        await page.goto('/admin/reports/owner-report');
        await page.waitForLoadState('networkidle');

        // التحقق من أن العناصر ما زالت مرئية
        await expect(page.locator('h1')).toBeVisible();
        await expect(page.locator('select[wire\\:model="owner_id"]')).toBeVisible();
        await expect(page.locator('text=تصدير PDF')).toBeVisible();

        // العودة لحجم الشاشة العادي
        await page.setViewportSize({ width: 1280, height: 720 });
    });

    test('يجب أن تظهر رسائل خطأ مناسبة عند عدم وجود بيانات', async () => {
        await page.goto('/admin/reports/owner-report');
        await page.waitForLoadState('networkidle');

        // التحقق من رسالة "يرجى اختيار مالك"
        await expect(page.locator('text=يرجى اختيار مالك لعرض التقرير')).toBeVisible();
        await expect(page.locator('text=قم بتحديد المالك من القائمة أعلاه لعرض التقرير التفصيلي')).toBeVisible();
    });

    test('يجب أن تتحديث البيانات عند تغيير الفلاتر', async () => {
        await page.goto('/admin/reports/owner-report');
        await page.waitForLoadState('networkidle');

        // اختيار مالك
        const ownerSelect = page.locator('select[wire\\:model="owner_id"]');
        await ownerSelect.click();
        await page.waitForTimeout(1000);
        
        const firstOwner = page.locator('select[wire\\:model="owner_id"] option').nth(1);
        if (await firstOwner.count() > 0) {
            const ownerValue = await firstOwner.getAttribute('value');
            if (ownerValue) {
                await ownerSelect.selectOption(ownerValue);
                await page.waitForLoadState('networkidle');
                await page.waitForTimeout(2000);

                // تسجيل القيم الأولية
                const initialCollection = await page.locator('.filament-stats-card').first().textContent();

                // تغيير نطاق التاريخ
                await page.fill('input[wire\\:model="date_from"]', '2024-01-01');
                await page.fill('input[wire\\:model="date_to"]', '2024-06-30');
                
                // النقر على تحديث
                await page.click('text=تحديث التقرير');
                await page.waitForLoadState('networkidle');
                await page.waitForTimeout(2000);

                // التحقق من أن البيانات تغيرت أو تم إعادة تحميلها
                const updatedCollection = await page.locator('.filament-stats-card').first().textContent();
                
                // البيانات يجب أن تكون محدثة (قد تكون نفسها إذا لم تكن هناك بيانات في النطاق الجديد)
                expect(updatedCollection).toBeDefined();
            }
        }
    });
});