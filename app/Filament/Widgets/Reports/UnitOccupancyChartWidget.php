<?php

namespace App\Filament\Widgets\Reports;

use Filament\Widgets\ChartWidget;
use App\Models\Unit;
use App\Models\UnitContract;
use Carbon\Carbon;

class UnitOccupancyChartWidget extends ChartWidget
{
    protected ?string $heading = 'معدل الإشغال الشهري';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $months = [];
        $occupancyRates = [];
        $rentedUnits = [];
        $vacantUnits = [];
        
        // حساب معدل الإشغال لآخر 6 شهور
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = $date->locale('ar')->monthName;
            
            $totalUnits = Unit::count();
            
            // حساب الوحدات المؤجرة في ذلك الشهر
            $occupiedCount = UnitContract::where('contract_status', 'active')
                ->whereDate('start_date', '<=', $date->endOfMonth())
                ->whereDate('end_date', '>=', $date->startOfMonth())
                ->distinct('unit_id')
                ->count('unit_id');
            
            $rentedUnits[] = $occupiedCount;
            $vacantUnits[] = $totalUnits - $occupiedCount;
            $occupancyRates[] = $totalUnits > 0 ? round(($occupiedCount / $totalUnits) * 100, 1) : 0;
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'معدل الإشغال %',
                    'data' => $occupancyRates,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 2,
                    'tension' => 0.3,
                    'fill' => true,
                    'yAxisID' => 'percentage',
                ],
                [
                    'label' => 'وحدات مؤجرة',
                    'data' => $rentedUnits,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'type' => 'bar',
                    'yAxisID' => 'units',
                ],
                [
                    'label' => 'وحدات شاغرة',
                    'data' => $vacantUnits,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.5)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                    'type' => 'bar',
                    'yAxisID' => 'units',
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'percentage' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'grid' => [
                        'display' => false,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'معدل الإشغال %',
                    ],
                    'min' => 0,
                    'max' => 100,
                ],
                'units' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'عدد الوحدات',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'font' => [
                            'family' => "'IBM Plex Sans Arabic', sans-serif",
                        ],
                    ],
                ],
                'tooltip' => [
                    'rtl' => true,
                    'textDirection' => 'rtl',
                    'callbacks' => [
                        'label' => "function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.datasetIndex === 0) {
                                label += context.parsed.y + '%';
                            } else {
                                label += context.parsed.y + ' وحدة';
                            }
                            return label;
                        }",
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}