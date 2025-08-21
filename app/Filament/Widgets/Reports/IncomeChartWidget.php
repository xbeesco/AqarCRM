<?php

namespace App\Filament\Widgets\Reports;

use Filament\Widgets\ChartWidget;
use App\Models\Owner;
use App\Models\CollectionPayment;
use App\Models\PropertyRepair;
use Carbon\Carbon;

class IncomeChartWidget extends ChartWidget
{
    protected int | string | array $columnSpan = 'full';
    
    public function getHeading(): ?string
    {
        return 'الرسم البياني للدخل الشهري';
    }
    
    public ?int $owner_id = null;
    public ?string $date_from = null;
    public ?string $date_to = null;
    public string $report_type = 'summary';

    protected $listeners = [
        'owner-filters-updated' => 'updateFilters',
    ];

    public function updateFilters($filters): void
    {
        $this->owner_id = $filters['owner_id'] ?? null;
        $this->date_from = $filters['date_from'] ?? null;
        $this->date_to = $filters['date_to'] ?? null;
        $this->report_type = $filters['report_type'] ?? 'summary';
    }

    protected function getData(): array
    {
        if (!$this->owner_id) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $owner = Owner::find($this->owner_id);
        if (!$owner) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $dateFrom = $this->date_from ? Carbon::parse($this->date_from) : now()->subMonths(11)->startOfMonth();
        $dateTo = $this->date_to ? Carbon::parse($this->date_to) : now()->endOfMonth();

        $labels = [];
        $collectionData = [];
        $maintenanceData = [];
        $netIncomeData = [];

        $period = $dateFrom->copy();
        
        while ($period->lte($dateTo)) {
            $monthLabel = $period->locale('ar')->format('M Y');
            $labels[] = $monthLabel;

            // حساب التحصيل الشهري
            $monthlyCollection = CollectionPayment::whereHas('property', function ($query) use ($owner) {
                    $query->where('owner_id', $owner->id);
                })
                ->whereYear('paid_date', $period->year)
                ->whereMonth('paid_date', $period->month)
                ->whereHas('paymentStatus', function ($query) {
                    $query->where('is_paid_status', true);
                })
                ->sum('total_amount');

            // حساب تكاليف الصيانة الشهرية
            $monthlyMaintenance = PropertyRepair::whereHas('property', function ($query) use ($owner) {
                    $query->where('owner_id', $owner->id);
                })
                ->whereYear('completion_date', $period->year)
                ->whereMonth('completion_date', $period->month)
                ->whereIn('status', ['completed'])
                ->sum('actual_cost');

            // حساب النسبة الإدارية (10%)
            $managementFee = $monthlyCollection * 0.1;

            // حساب صافي الدخل
            $netIncome = $monthlyCollection - $managementFee - $monthlyMaintenance;

            $collectionData[] = round($monthlyCollection, 2);
            $maintenanceData[] = round($monthlyMaintenance, 2);
            $netIncomeData[] = round($netIncome, 2);

            $period->addMonth();
        }

        return [
            'datasets' => [
                [
                    'label' => 'إجمالي التحصيل',
                    'data' => $collectionData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => 'rgba(34, 197, 94, 1)',
                    'borderWidth' => 2,
                    'fill' => false,
                ],
                [
                    'label' => 'تكاليف الصيانة',
                    'data' => $maintenanceData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'borderColor' => 'rgba(239, 68, 68, 1)',
                    'borderWidth' => 2,
                    'fill' => false,
                ],
                [
                    'label' => 'صافي الدخل',
                    'data' => $netIncomeData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 3,
                    'fill' => false,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => 'function(context) {
                            let label = context.dataset.label || "";
                            if (label) {
                                label += ": ";
                            }
                            label += new Intl.NumberFormat("ar-SA", {
                                style: "currency",
                                currency: "SAR"
                            }).format(context.parsed.y);
                            return label;
                        }',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'الشهر',
                    ],
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'المبلغ (ريال)',
                    ],
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) {
                            return new Intl.NumberFormat("ar-SA").format(value) + " ريال";
                        }',
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
            'elements' => [
                'point' => [
                    'radius' => 4,
                    'hoverRadius' => 8,
                ],
            ],
        ];
    }

    public function getDescription(): ?string
    {
        if (!$this->owner_id) {
            return 'يرجى اختيار مالك لعرض الرسم البياني';
        }

        $dateFrom = $this->date_from ? Carbon::parse($this->date_from) : now()->subMonths(11);
        $dateTo = $this->date_to ? Carbon::parse($this->date_to) : now();

        return 'مقارنة التحصيل وتكاليف الصيانة وصافي الدخل للفترة من ' 
            . $dateFrom->format('Y/m/d') . ' إلى ' . $dateTo->format('Y/m/d');
    }
}