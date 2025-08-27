<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use BackedEnum;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'لوحة التحكم';
    
    protected static ?string $navigationLabel = 'لوحة التحكم';
    
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';
    
    /**
     * تحديد الـ widgets المطلوب عرضها في Dashboard
     */
    public function getWidgets(): array
    {
        return [
            // لا توجد widgets حالياً
        ];
    }
}