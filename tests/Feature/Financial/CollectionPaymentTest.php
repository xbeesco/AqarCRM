<?php

namespace Tests\Feature\Financial;

use Tests\TestCase;
use App\Models\CollectionPayment;
use App\Models\PaymentStatus;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Property;
use App\Models\Unit;
use App\Models\UnitContract;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CollectionPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\FinancialSeeder::class);
    }

    public function test_collection_payment_has_required_fields(): void
    {
        $payment = CollectionPayment::factory()->create([
            'amount' => 5000.00,
            'late_fee' => 100.00,
        ]);

        $this->assertInstanceOf(CollectionPayment::class, $payment);
        $this->assertEquals(5000.00, $payment->amount);
        $this->assertEquals(5100.00, $payment->total_amount);
        $this->assertNotNull($payment->payment_number);
    }

    public function test_payment_number_generation(): void
    {
        $payment = CollectionPayment::factory()->create();
        
        $this->assertStringStartsWith('COLLECTION-' . date('Y'), $payment->payment_number);
        $this->assertEquals(17, strlen($payment->payment_number));
    }

    public function test_late_fee_calculation(): void
    {
        $payment = CollectionPayment::factory()->create([
            'amount' => 5000.00,
            'due_date_end' => now()->subDays(10),
        ]);

        // Mock overdue status
        $payment->payment_status_id = PaymentStatus::where('slug', 'overdue')->first()->id;
        $payment->save();

        $this->assertTrue($payment->isOverdue());
        $this->assertEquals(10, $payment->getDaysOverdue());
    }

    public function test_payment_processing(): void
    {
        $payment = CollectionPayment::factory()->create([
            'payment_status_id' => PaymentStatus::where('slug', 'worth_collecting')->first()->id,
        ]);

        $paymentMethod = PaymentMethod::first();
        
        $result = $payment->processPayment($paymentMethod->id, now()->toDateString(), 'REF123');

        $this->assertTrue($result);
        $this->assertEquals(PaymentStatus::COLLECTED, $payment->fresh()->payment_status_id);
        $this->assertNotNull($payment->fresh()->receipt_number);
    }

    public function test_total_amount_calculation(): void
    {
        $payment = CollectionPayment::factory()->create([
            'amount' => 5000.00,
            'late_fee' => 350.00,
        ]);

        $this->assertEquals(5350.00, $payment->total_amount);
        $this->assertEquals(5350.00, $payment->getTotalAmountAttribute());
    }

    public function test_payment_relationships(): void
    {
        $payment = CollectionPayment::factory()->create();

        $this->assertInstanceOf(Property::class, $payment->property);
        $this->assertInstanceOf(Unit::class, $payment->unit);
        $this->assertInstanceOf(User::class, $payment->tenant);
        $this->assertInstanceOf(PaymentStatus::class, $payment->paymentStatus);
    }
}