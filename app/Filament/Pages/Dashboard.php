<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Helpers\DateHelper;
use Carbon\Carbon;
use BackedEnum;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'الرئيسية';
    
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';
    
    protected static ?int $navigationSort = -2;
    
    public function getTitle(): string
    {
        // Get current date (either test date or real date)
        $currentDate = Carbon::now()->format('Y-m-d');
        
        // Add indicator if in test mode
        $title = 'لوحة التحكم';
        
        if (DateHelper::isTestMode()) {
            $title .= ' (محاكاة يوم: ' . $currentDate . ')';
        }
        
        return $title;
    }
    
    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? 'الرئيسية';
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\StatsOverviewWidget::class,
        ];
    }
    
    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\VacantUnitsWidget::class,
            \App\Filament\Widgets\TenantsPaymentDueWidget::class,
            \App\Filament\Widgets\ExpiredContractsWidget::class,
        ];
    }
    
    public function getColumns(): int | array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}
