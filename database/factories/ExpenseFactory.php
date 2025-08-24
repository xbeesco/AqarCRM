<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = array_keys(Expense::TYPES);
        $type = $this->faker->randomElement($types);

        return [
            'desc' => $this->generateDescription($type),
            'type' => $type,
            'cost' => $this->faker->randomFloat(2, 50, 5000),
            'date' => $this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'docs' => $this->generateDocuments($type),
        ];
    }

    /**
     * Generate description based on type
     */
    private function generateDescription(string $type): string
    {
        $descriptions = [
            'maintenance' => [
                'صيانة مكيف الهواء للوحدة رقم ' . $this->faker->numberBetween(101, 505),
                'إصلاح السباكة في الحمام الرئيسي',
                'تغيير قفل الباب الخارجي',
                'صيانة المصعد الكهربائي',
                'إصلاح التسريب في السقف',
                'تنظيف خزان المياه',
                'صيانة نظام الإنذار',
            ],
            'government' => [
                'تجديد رخصة البلدية',
                'رسوم الدفاع المدني',
                'رخصة تجارية سنوية',
                'رسوم كهرباء حكومية',
                'تصاريح البناء',
                'رسوم المرور والنقل',
            ],
            'utilities' => [
                'فاتورة الكهرباء لشهر ' . $this->faker->monthName(),
                'فاتورة المياه الشهرية',
                'اشتراك الإنترنت',
                'فاتورة الغاز الطبيعي',
                'رسوم التلفون والاتصالات',
            ],
            'purchases' => [
                'شراء أدوات تنظيف',
                'شراء لمبات وكهربائيات',
                'شراء مواد بناء',
                'شراء طلاء ومعدات الصيانة',
                'شراء قطع غيار المصعد',
            ],
            'salaries' => [
                'راتب حارس الأمن لشهر ' . $this->faker->monthName(),
                'راتب عامل النظافة',
                'راتب مدير العقار',
                'مكافآت نهاية العام للموظفين',
            ],
            'commissions' => [
                'عمولة وسيط عقاري',
                'عمولة البنك على التحويلات',
                'عمولة شركة الصيانة',
            ],
            'other' => [
                'مصاريف متنوعة',
                'تكاليف إدارية',
                'رسوم قانونية',
                'تأمين العقار',
            ],
        ];

        return $this->faker->randomElement($descriptions[$type] ?? $descriptions['other']);
    }

    /**
     * Generate documents based on type
     */
    private function generateDocuments(string $type): ?array
    {
        // 50% chance of having documents
        if ($this->faker->boolean(50)) {
            return null;
        }

        $docs = [];

        switch ($type) {
            case 'maintenance':
                if ($this->faker->boolean(70)) {
                    $docs[] = [
                        'type' => 'فاتورة مشتريات',
                        'number' => 'INV-' . $this->faker->year() . '-' . $this->faker->numberBetween(1000, 9999),
                        'amount' => $this->faker->randomFloat(2, 50, 1000),
                        'file' => 'expenses/invoices/' . $this->faker->uuid() . '.pdf',
                    ];
                }

                if ($this->faker->boolean(60)) {
                    $docs[] = [
                        'type' => 'عمالة',
                        'worker' => $this->faker->name(),
                        'hours' => $this->faker->numberBetween(2, 12),
                        'rate' => $this->faker->randomElement([30, 40, 50, 60, 70, 80]),
                        'file' => 'expenses/labor/' . $this->faker->uuid() . '.pdf',
                    ];
                }
                break;

            case 'government':
                $docs[] = [
                    'type' => 'وثيقة حكومية',
                    'entity' => $this->faker->randomElement(['البلدية', 'الدفاع المدني', 'التجارة', 'الكهرباء']),
                    'reference' => 'REF-' . $this->faker->year() . '-' . $this->faker->numberBetween(100000, 999999),
                    'file' => 'expenses/government/' . $this->faker->uuid() . '.pdf',
                ];
                break;

            case 'utilities':
            case 'purchases':
                $docs[] = [
                    'type' => 'فاتورة مشتريات',
                    'number' => 'BILL-' . $this->faker->year() . '-' . $this->faker->numberBetween(1000, 9999),
                    'amount' => $this->faker->randomFloat(2, 100, 2000),
                    'file' => 'expenses/invoices/' . $this->faker->uuid() . '.pdf',
                ];
                break;
        }

        return empty($docs) ? null : $docs;
    }

    /**
     * Create expense with specific type
     */
    public function ofType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
            'desc' => $this->generateDescription($type),
            'docs' => $this->generateDocuments($type),
        ]);
    }

    /**
     * Create expensive expense (high cost)
     */
    public function expensive(): static
    {
        return $this->state(fn (array $attributes) => [
            'cost' => $this->faker->randomFloat(2, 2000, 10000),
        ]);
    }

    /**
     * Create expense with documents
     */
    public function withDocuments(): static
    {
        return $this->state(fn (array $attributes) => [
            'docs' => $this->generateDocuments($attributes['type']),
        ]);
    }

    /**
     * Create expense without documents
     */
    public function withoutDocuments(): static
    {
        return $this->state(fn (array $attributes) => [
            'docs' => null,
        ]);
    }

    /**
     * Create expense from this month
     */
    public function thisMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $this->faker->dateTimeBetween('first day of this month', 'now')->format('Y-m-d'),
        ]);
    }

    /**
     * Create expense from last month
     */
    public function lastMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $this->faker->dateTimeBetween('first day of last month', 'last day of last month')->format('Y-m-d'),
        ]);
    }
}