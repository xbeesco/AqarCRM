<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UnitContract;
use App\Models\CollectionPayment;
use App\Models\Property;
use App\Models\Unit;
use App\Services\PaymentGeneratorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentRescheduleTest extends TestCase
{
    use RefreshDatabase;
    
    protected PaymentGeneratorService $service;
    protected User $superAdmin;
    protected Property $property;
    protected Unit $unit;
    protected User $tenant;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // إنشاء مستخدم super_admin
        $this->superAdmin = User::factory()->create(['type' => 'super_admin']);
        $this->actingAs($this->superAdmin);
        
        // إنشاء الخدمة
        $this->service = app(PaymentGeneratorService::class);
        
        // إنشاء البيانات الأساسية
        $this->property = Property::factory()->create();
        $this->unit = Unit::factory()->create(['property_id' => $this->property->id]);
        $this->tenant = User::factory()->create(['type' => 'tenant']);
    }
    
    /**
     * Helper: إنشاء عقد مع دفعات
     */
    private function createContractWithPayments(
        int $months, 
        string $frequency, 
        int $paidCount = 0,
        float $monthlyRent = 1000
    ): UnitContract {
        $contract = UnitContract::create([
            'contract_number' => 'UC-TEST-' . rand(1000, 9999),
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'property_id' => $this->property->id,
            'monthly_rent' => $monthlyRent,
            'duration_months' => $months,
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => Carbon::now()->startOfMonth()->addMonths($months)->subDay(),
            'payment_frequency' => $frequency,
            'contract_status' => 'active',
        ]);
        
        // توليد الدفعات
        $payments = $this->service->generateTenantPayments($contract);
        
        // تحديد بعض الدفعات كمدفوعة
        for ($i = 0; $i < $paidCount && $i < count($payments); $i++) {
            $payments[$i]->update([
                'collection_status' => 'paid',
                'paid_date' => Carbon::now()
            ]);
        }
        
        return $contract->fresh();
    }
    
    /**
     * Helper: التحقق من وجود دفعة
     */
    private function assertPaymentExists(CollectionPayment $payment, array $expectedData): void
    {
        $this->assertDatabaseHas('collection_payments', [
            'id' => $payment->id,
            'collection_status' => $expectedData['status'] ?? $payment->collection_status,
            'amount' => $expectedData['amount'] ?? $payment->amount,
        ]);
    }
    
    /**
     * Helper: التحقق من حذف دفعة
     */
    private function assertPaymentDeleted(int $paymentId): void
    {
        $this->assertDatabaseMissing('collection_payments', ['id' => $paymentId]);
    }
    
    /**
     * Helper: التحقق من استمرارية التواريخ
     */
    private function assertDatesAreContinuous(array $payments): void
    {
        for ($i = 1; $i < count($payments); $i++) {
            $prevEnd = Carbon::parse($payments[$i - 1]->due_date_end);
            $currentStart = Carbon::parse($payments[$i]->due_date_start);
            
            // يجب أن يكون تاريخ البداية هو اليوم التالي لنهاية الفترة السابقة
            $this->assertEquals(
                $prevEnd->addDay()->format('Y-m-d'),
                $currentStart->format('Y-m-d'),
                "فجوة في التواريخ بين الدفعة {$i} والدفعة " . ($i + 1)
            );
        }
    }
    
    // ============ مجموعة 1: اختبارات تقليل المدة ============
    
    /** @test */
    public function test_reschedule_reduce_duration_from_12_to_7_months()
    {
        // العقد الأصلي: 12 شهر ربع سنوي (4 دفعات)
        $contract = $this->createContractWithPayments(12, 'quarterly', 2); // دفعتان مدفوعتان
        
        $paidPayments = $contract->getPaidPayments();
        $unpaidPayments = $contract->getUnpaidPayments();
        
        $this->assertCount(2, $paidPayments);
        $this->assertCount(2, $unpaidPayments);
        
        // إعادة الجدولة: إضافة شهر واحد شهري
        $result = $this->service->rescheduleContractPayments(
            $contract,
            1500, // إيجار جديد
            1,    // شهر واحد إضافي
            'monthly'
        );
        
        // التحقق من النتائج
        $this->assertEquals(2, $result['deleted_count']);
        $this->assertCount(1, $result['new_payments']);
        $this->assertEquals(6, $result['paid_months']);
        $this->assertEquals(7, $result['total_months']);
        
        // التحقق من الدفعات
        $contract->refresh();
        $allPayments = $contract->payments()->orderBy('due_date_start')->get();
        
        $this->assertCount(3, $allPayments); // 2 مدفوعة + 1 جديدة
        
        // الدفعات المدفوعة يجب أن تبقى كما هي
        $this->assertEquals('paid', $allPayments[0]->collection_status);
        $this->assertEquals('paid', $allPayments[1]->collection_status);
        $this->assertEquals(1000 * 3, $allPayments[0]->amount); // ربع سنوي بالسعر القديم
        
        // الدفعة الجديدة
        $this->assertEquals('due', $allPayments[2]->collection_status);
        $this->assertEquals(1500, $allPayments[2]->amount); // شهري بالسعر الجديد
        
        // التحقق من تحديث العقد
        $this->assertEquals(7, $contract->duration_months);
    }
    
    /** @test */
    public function test_reschedule_reduce_from_24_to_15_months_mixed_frequency()
    {
        // العقد الأصلي: 24 شهر نصف سنوي (4 دفعات)
        $contract = $this->createContractWithPayments(24, 'semi_annually', 2); // 12 شهر مدفوع
        
        // إعادة الجدولة: إضافة 3 أشهر شهري
        $result = $this->service->rescheduleContractPayments(
            $contract,
            2000,
            3,
            'monthly'
        );
        
        $this->assertEquals(2, $result['deleted_count']);
        $this->assertCount(3, $result['new_payments']);
        $this->assertEquals(15, $result['total_months']);
        
        // التحقق من الدفعات
        $allPayments = $contract->payments()->orderBy('due_date_start')->get();
        $this->assertCount(5, $allPayments); // 2 نصف سنوية + 3 شهرية
        
        // التحقق من استمرارية التواريخ
        $this->assertDatesAreContinuous($allPayments);
    }
    
    // ============ مجموعة 2: اختبارات زيادة المدة ============
    
    /** @test */
    public function test_reschedule_extend_from_6_to_18_months()
    {
        // العقد الأصلي: 6 شهر شهري
        $contract = $this->createContractWithPayments(6, 'monthly', 4); // 4 أشهر مدفوعة
        
        // إعادة الجدولة: إضافة 12 شهر سنوي
        $result = $this->service->rescheduleContractPayments(
            $contract,
            3000,
            12,
            'annually'
        );
        
        $this->assertEquals(2, $result['deleted_count']); // حذف الشهرين غير المدفوعين
        $this->assertCount(1, $result['new_payments']); // دفعة سنوية واحدة
        $this->assertEquals(16, $result['total_months']); // 4 مدفوعة + 12 جديدة
        
        $newPayment = $result['new_payments'][0];
        $this->assertEquals(3000 * 12, $newPayment->amount); // سنوي
    }
    
    /** @test */
    public function test_reschedule_triple_duration()
    {
        // العقد الأصلي: 12 شهر سنوي (دفعة واحدة)
        $contract = $this->createContractWithPayments(12, 'annually', 1); // الدفعة مدفوعة بالكامل
        
        // إعادة الجدولة: إضافة 24 شهر ربع سنوي
        $result = $this->service->rescheduleContractPayments(
            $contract,
            1200,
            24,
            'quarterly'
        );
        
        $this->assertEquals(0, $result['deleted_count']); // لا توجد دفعات غير مدفوعة
        $this->assertCount(8, $result['new_payments']); // 24÷3 = 8 دفعات ربع سنوية
        $this->assertEquals(36, $result['total_months']);
        
        // التحقق من المبالغ
        foreach ($result['new_payments'] as $payment) {
            $this->assertEquals(1200 * 3, $payment->amount); // ربع سنوي
        }
    }
    
    // ============ مجموعة 3: اختبارات تغيير التكرار ============
    
    /** @test */
    public function test_reschedule_change_frequency_quarterly_to_monthly()
    {
        // العقد الأصلي: 12 شهر ربع سنوي
        $contract = $this->createContractWithPayments(12, 'quarterly', 1); // دفعة واحدة مدفوعة (3 أشهر)
        
        // إعادة الجدولة: 9 أشهر شهري
        $result = $this->service->rescheduleContractPayments(
            $contract,
            800,
            9,
            'monthly'
        );
        
        $this->assertEquals(3, $result['deleted_count']); // حذف 3 دفعات ربع سنوية
        $this->assertCount(9, $result['new_payments']); // 9 دفعات شهرية
        
        // التحقق من التكرار الجديد
        foreach ($result['new_payments'] as $payment) {
            $this->assertEquals(800, $payment->amount); // شهري
        }
    }
    
    /** @test */
    public function test_reschedule_change_frequency_monthly_to_annual()
    {
        // العقد الأصلي: 12 شهر شهري
        $contract = $this->createContractWithPayments(12, 'monthly', 6); // 6 أشهر مدفوعة
        
        // إعادة الجدولة: 12 شهر سنوي
        $result = $this->service->rescheduleContractPayments(
            $contract,
            1100,
            12,
            'annually'
        );
        
        $this->assertEquals(6, $result['deleted_count']); // حذف 6 دفعات شهرية غير مدفوعة
        $this->assertCount(1, $result['new_payments']); // دفعة سنوية واحدة
        $this->assertEquals(1100 * 12, $result['new_payments'][0]->amount);
    }
    
    // ============ مجموعة 4: اختبارات تغيير المبلغ ============
    
    /** @test */
    public function test_reschedule_increase_rent_amount()
    {
        $contract = $this->createContractWithPayments(12, 'monthly', 6, 1000);
        
        $result = $this->service->rescheduleContractPayments(
            $contract,
            1500, // زيادة من 1000 إلى 1500
            6,
            'monthly'
        );
        
        // التحقق من المبالغ
        $paidPayments = $contract->getPaidPayments();
        foreach ($paidPayments as $payment) {
            $this->assertEquals(1000, $payment->amount); // المبلغ القديم للمدفوعة
        }
        
        foreach ($result['new_payments'] as $payment) {
            $this->assertEquals(1500, $payment->amount); // المبلغ الجديد
        }
    }
    
    /** @test */
    public function test_reschedule_decrease_rent_amount()
    {
        $contract = $this->createContractWithPayments(12, 'monthly', 3, 2000);
        
        $result = $this->service->rescheduleContractPayments(
            $contract,
            1200, // تقليل من 2000 إلى 1200
            9,
            'monthly'
        );
        
        // التحقق من المبالغ
        $paidPayments = $contract->getPaidPayments();
        foreach ($paidPayments as $payment) {
            $this->assertEquals(2000, $payment->amount); // المبلغ القديم
        }
        
        foreach ($result['new_payments'] as $payment) {
            $this->assertEquals(1200, $payment->amount); // المبلغ الجديد
        }
    }
    
    // ============ مجموعة 5: اختبارات الحالات الحرجة ============
    
    /** @test */
    public function test_reschedule_all_payments_paid()
    {
        // كل الدفعات مدفوعة
        $contract = $this->createContractWithPayments(6, 'monthly', 6);
        
        $result = $this->service->rescheduleContractPayments(
            $contract,
            1800,
            3, // إضافة 3 أشهر جديدة
            'monthly'
        );
        
        $this->assertEquals(0, $result['deleted_count']); // لا شيء للحذف
        $this->assertCount(3, $result['new_payments']);
        $this->assertEquals(9, $result['total_months']); // 6 + 3
        
        // التحقق من أن الدفعات الجديدة تبدأ بعد آخر دفعة مدفوعة
        $lastPaidDate = $contract->getLastPaidDate();
        $firstNewPayment = $result['new_payments'][0];
        $this->assertEquals(
            $lastPaidDate->addDay()->format('Y-m-d'),
            $firstNewPayment->due_date_start
        );
    }
    
    /** @test */
    public function test_reschedule_no_payments_paid()
    {
        // لا توجد دفعات مدفوعة
        $contract = $this->createContractWithPayments(12, 'quarterly', 0);
        
        $result = $this->service->rescheduleContractPayments(
            $contract,
            900,
            6,
            'semi_annually'
        );
        
        $this->assertEquals(4, $result['deleted_count']); // حذف كل الدفعات القديمة
        $this->assertCount(1, $result['new_payments']); // دفعة نصف سنوية واحدة
        $this->assertEquals(0, $result['paid_months']);
        $this->assertEquals(6, $result['total_months']);
    }
    
    // ============ مجموعة 6: اختبارات التواريخ ============
    
    /** @test */
    public function test_reschedule_dates_continuity()
    {
        $contract = $this->createContractWithPayments(12, 'quarterly', 1);
        
        $result = $this->service->rescheduleContractPayments(
            $contract,
            1000,
            9,
            'quarterly'
        );
        
        $allPayments = $contract->payments()->orderBy('due_date_start')->get();
        
        // التحقق من عدم وجود فجوات
        $this->assertDatesAreContinuous($allPayments);
        
        // التحقق من أن التاريخ الجديد يبدأ بعد آخر دفعة مدفوعة
        $lastPaidPayment = $contract->getPaidPayments()->last();
        $firstNewPayment = $result['new_payments'][0];
        
        $this->assertEquals(
            Carbon::parse($lastPaidPayment->due_date_end)->addDay()->format('Y-m-d'),
            $firstNewPayment->due_date_start
        );
    }
    
    /** @test */
    public function test_reschedule_end_date_calculation()
    {
        $contract = $this->createContractWithPayments(12, 'monthly', 6);
        
        $result = $this->service->rescheduleContractPayments(
            $contract,
            1000,
            12, // إضافة سنة
            'monthly'
        );
        
        // التحقق من حساب end_date
        $expectedEndDate = Carbon::parse($contract->getLastPaidDate())
            ->addMonths(12);
            
        $this->assertEquals(
            $expectedEndDate->format('Y-m-d'),
            $result['new_end_date']->format('Y-m-d')
        );
        
        $contract->refresh();
        $this->assertEquals(
            $expectedEndDate->format('Y-m-d'),
            $contract->end_date->format('Y-m-d')
        );
    }
    
    // ============ مجموعة 7: اختبارات Validation ============
    
    /** @test */
    public function test_reschedule_invalid_duration_for_frequency()
    {
        $contract = $this->createContractWithPayments(12, 'monthly', 3);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('المدة الإضافية لا تتوافق مع تكرار الدفع المختار');
        
        // محاولة إضافة 7 أشهر ربع سنوي (7 لا تقبل القسمة على 3)
        $this->service->rescheduleContractPayments(
            $contract,
            1000,
            7,
            'quarterly'
        );
    }
    
    /** @test */
    public function test_reschedule_invalid_amount()
    {
        $contract = $this->createContractWithPayments(12, 'monthly', 3);
        
        // مبلغ سالب
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('قيمة الإيجار يجب أن تكون أكبر من صفر');
        
        $this->service->rescheduleContractPayments(
            $contract,
            -500,
            9,
            'monthly'
        );
    }
    
    /** @test */
    public function test_reschedule_zero_amount()
    {
        $contract = $this->createContractWithPayments(12, 'monthly', 3);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('قيمة الإيجار يجب أن تكون أكبر من صفر');
        
        $this->service->rescheduleContractPayments(
            $contract,
            0,
            9,
            'monthly'
        );
    }
    
    /** @test */
    public function test_reschedule_invalid_additional_months()
    {
        $contract = $this->createContractWithPayments(12, 'monthly', 3);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('عدد الأشهر الإضافية يجب أن يكون أكبر من صفر');
        
        $this->service->rescheduleContractPayments(
            $contract,
            1000,
            0, // صفر أشهر
            'monthly'
        );
    }
    
    // ============ مجموعة 8: اختبارات الأداء والتكامل ============
    
    /** @test */
    public function test_reschedule_long_contract_performance()
    {
        $startTime = microtime(true);
        
        // عقد طويل: 60 شهر
        $contract = $this->createContractWithPayments(60, 'monthly', 12);
        
        $result = $this->service->rescheduleContractPayments(
            $contract,
            1500,
            24, // إضافة سنتين
            'monthly'
        );
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // يجب أن يكتمل في أقل من 2 ثانية
        $this->assertLessThan(2, $executionTime);
        
        // التحقق من صحة النتائج
        $this->assertEquals(48, $result['deleted_count']); // 60-12=48
        $this->assertCount(24, $result['new_payments']);
        $this->assertEquals(36, $result['total_months']); // 12+24
    }
    
    /** @test */
    public function test_reschedule_rollback_on_error()
    {
        $contract = $this->createContractWithPayments(12, 'monthly', 3);
        
        // عدد الدفعات قبل المحاولة
        $paymentCountBefore = $contract->payments()->count();
        
        try {
            // محاكاة خطأ بإرسال بيانات خاطئة
            $this->service->rescheduleContractPayments(
                $contract,
                -1000, // مبلغ خاطئ
                6,
                'monthly'
            );
        } catch (\Exception $e) {
            // متوقع
        }
        
        // التحقق من عدم تغيير البيانات
        $contract->refresh();
        $paymentCountAfter = $contract->payments()->count();
        
        $this->assertEquals($paymentCountBefore, $paymentCountAfter);
        $this->assertEquals(12, $contract->duration_months); // لم يتغير
    }
    
    /** @test */
    public function test_reschedule_permissions()
    {
        $contract = $this->createContractWithPayments(12, 'monthly', 3);
        
        // اختبار مع super_admin (يجب أن ينجح)
        $this->assertTrue($contract->canReschedule());
        
        // اختبار مع admin (لا يمكنه)
        $admin = User::factory()->create(['type' => 'admin']);
        $this->actingAs($admin);
        
        // في الواقع، canReschedule() لا تتحقق من الصلاحيات
        // الصلاحيات يتم التحقق منها في الـ Page
        // لكن يمكننا التحقق من أن الصفحة ترفض الوصول
        
        $response = $this->get(route('filament.admin.resources.unit-contracts.reschedule', $contract));
        $response->assertForbidden();
    }
    
    // ============ اختبارات إضافية للحالات الخاصة ============
    
    /** @test */
    public function test_reschedule_with_mixed_payment_frequencies()
    {
        // عقد بدأ بدفعات ربع سنوية
        $contract = $this->createContractWithPayments(12, 'quarterly', 2); // 6 أشهر مدفوعة
        
        // أول إعادة جدولة: إضافة 6 أشهر نصف سنوي
        $result1 = $this->service->rescheduleContractPayments(
            $contract,
            1200,
            6,
            'semi_annually'
        );
        
        $this->assertEquals(2, $result1['deleted_count']);
        $this->assertCount(1, $result1['new_payments']);
        
        // التحقق من الدفعات
        $allPayments = $contract->payments()->orderBy('due_date_start')->get();
        $this->assertCount(3, $allPayments); // 2 ربع سنوية + 1 نصف سنوية
        
        // محاكاة دفع الدفعة النصف سنوية
        $result1['new_payments'][0]->update([
            'collection_status' => 'paid',
            'paid_date' => Carbon::now()
        ]);
        
        // ثانية إعادة جدولة: إضافة 3 أشهر شهري
        $contract->refresh();
        $result2 = $this->service->rescheduleContractPayments(
            $contract,
            800,
            3,
            'monthly'
        );
        
        $this->assertCount(3, $result2['new_payments']);
        
        // النتيجة النهائية: عقد بـ 3 أنواع تكرار مختلفة
        $finalPayments = $contract->payments()->orderBy('due_date_start')->get();
        $this->assertCount(6, $finalPayments); // 2 ربع + 1 نصف + 3 شهري
        
        // التحقق من استمرارية التواريخ
        $this->assertDatesAreContinuous($finalPayments);
    }
    
    /** @test */
    public function test_reschedule_preserves_payment_numbers_sequence()
    {
        $contract = $this->createContractWithPayments(12, 'monthly', 3);
        
        // التحقق من أرقام الدفعات قبل إعادة الجدولة
        $originalPayments = $contract->payments()->orderBy('id')->get();
        $lastNumber = count($originalPayments);
        
        // إعادة الجدولة
        $result = $this->service->rescheduleContractPayments(
            $contract,
            1000,
            6,
            'monthly'
        );
        
        // التحقق من أن الدفعات الجديدة لها أرقام متسلسلة
        foreach ($result['new_payments'] as $index => $payment) {
            $expectedNumber = $lastNumber + $index + 1;
            $this->assertStringContainsString(
                sprintf('%04d', $expectedNumber),
                $payment->payment_number
            );
        }
    }
}