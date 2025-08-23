<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Collection;

class ModulesManager extends Page
{
    protected static ?string $navigationLabel = 'إدارة الوحدات';
    protected static ?string $title = 'إدارة وحدات النظام';
    
    protected string $view = 'filament.pages.modules-manager';
    
    public Collection $modules;
    
    public function mount(): void
    {
        // تحميل بيانات الوحدات
        $this->modules = collect($this->getModulesData());
    }
    
    protected function getModulesData(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'إدارة العقارات',
                'key' => 'properties',
                'description' => 'إدارة العقارات والوحدات السكنية',
                'status' => 'active',
                'completion' => 95,
                'tables' => ['properties', 'units'],
                'features' => 'إضافة، تعديل، حذف، البحث المتقدم',
                'notes' => 'يحتاج تحسين واجهة البحث',
            ],
            [
                'id' => 2,
                'name' => 'إدارة العقود',
                'key' => 'contracts',
                'description' => 'عقود الإيجار والملكية',
                'status' => 'in_progress',
                'completion' => 75,
                'tables' => ['unit_contracts', 'property_contracts'],
                'features' => 'إنشاء العقود، التجديد التلقائي، التنبيهات',
                'notes' => 'قيد التطوير - يحتاج ربط مع المدفوعات',
            ],
            [
                'id' => 3,
                'name' => 'النظام المالي',
                'key' => 'payments',
                'description' => 'المدفوعات والتحصيل والتوريد',
                'status' => 'in_progress',
                'completion' => 60,
                'tables' => ['collection_payments', 'supply_payments'],
                'features' => 'تسجيل المدفوعات، التقارير المالية، الفواتير',
                'notes' => 'يحتاج تطوير نظام التقارير',
            ],
            [
                'id' => 4,
                'name' => 'إدارة المستخدمين',
                'key' => 'users',
                'description' => 'الملاك والمستأجرين والموظفين',
                'status' => 'active',
                'completion' => 90,
                'tables' => ['users'],
                'features' => 'إدارة الصلاحيات، الأدوار، البحث الذكي',
                'notes' => 'تم إزالة Spatie والعمل بنظام مخصص',
            ],
            [
                'id' => 5,
                'name' => 'الصيانة والإصلاحات',
                'key' => 'maintenance',
                'description' => 'طلبات الصيانة وإدارة المقاولين',
                'status' => 'pending',
                'completion' => 20,
                'tables' => ['property_repairs', 'maintenance_requests'],
                'features' => 'طلبات الصيانة، جدولة الأعمال، تتبع التكاليف',
                'notes' => 'لم يبدأ التطوير بعد',
            ],
            [
                'id' => 6,
                'name' => 'التقارير والإحصائيات',
                'key' => 'reports',
                'description' => 'التقارير المالية والإدارية',
                'status' => 'pending',
                'completion' => 15,
                'tables' => [],
                'features' => 'تقارير مخصصة، لوحات معلومات، تصدير البيانات',
                'notes' => 'يحتاج تصميم واجهات التقارير',
            ],
            [
                'id' => 7,
                'name' => 'استيراد البيانات',
                'key' => 'import',
                'description' => 'استيراد البيانات من WordPress',
                'status' => 'pending',
                'completion' => 10,
                'tables' => [],
                'features' => 'استيراد الملاك، المستأجرين، العقارات، العقود',
                'notes' => 'يحتاج كتابة scripts للاستيراد',
            ],
            [
                'id' => 8,
                'name' => 'البيانات المرجعية',
                'key' => 'reference_data',
                'description' => 'المواقع، الأنواع، الفئات، المميزات',
                'status' => 'active',
                'completion' => 100,
                'tables' => ['locations', 'unit_types', 'unit_categories', 'unit_features'],
                'features' => 'إدارة كاملة للبيانات المرجعية',
                'notes' => 'مكتمل - تم التبسيط حسب المتطلبات',
            ],
        ];
    }
    
    public function toggleModuleStatus($moduleId): void
    {
        $module = collect($this->modules)->firstWhere('id', $moduleId);
        
        if ($module) {
            Notification::make()
                ->title('تم تحديث حالة الوحدة')
                ->body("تم تحديث حالة وحدة: {$module['name']}")
                ->success()
                ->send();
        }
    }
    
    public function getStatusColor(string $status): string
    {
        return match($status) {
            'active' => 'success',
            'in_progress' => 'warning',
            'pending' => 'gray',
            default => 'gray',
        };
    }
    
    public function getStatusLabel(string $status): string
    {
        return match($status) {
            'active' => 'مفعل',
            'in_progress' => 'قيد التطوير',
            'pending' => 'في الانتظار',
            default => 'غير محدد',
        };
    }
    
    public function getCompletionColor(int $completion): string
    {
        if ($completion >= 80) return 'success';
        if ($completion >= 50) return 'warning';
        return 'danger';
    }
}