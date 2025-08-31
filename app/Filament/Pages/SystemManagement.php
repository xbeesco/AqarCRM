<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;
use App\Models\UnitContract;
use App\Models\PropertyContract;
use App\Models\CollectionPayment;
use App\Models\SupplyPayment;
use App\Models\Expense;
use BackedEnum;

class SystemManagement extends Page implements HasSchemas
{
    use InteractsWithSchemas;
    
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'إدارة النظام';
    protected static ?string $title = 'إدارة النظام';
    protected static ?string $slug = 'system-management';
    protected static ?int $navigationSort = 100;
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected string $view = 'filament.pages.system-management';

    public ?array $data = [];
    public ?array $cleanupData = [];

    public static function canAccess(): bool
    {
        return Auth::user()?->type === 'super_admin';
    }

    protected function getSchemas(): array
    {
        return [
            'form',
            'cleanupForm',
        ];
    }
    
    public function mount(): void
    {
        if (!static::canAccess()) {
            abort(403, 'Unauthorized');
        }

        // Load settings from database or use defaults
        $this->form->fill([
            'payment_due_days' => Setting::get('payment_due_days', 7),
            'allowed_delay_days' => Setting::get('allowed_delay_days', 5),
            'test_date' => Setting::get('test_date', null),
        ]);
        
        // Initialize cleanup form
        $this->cleanupForm->fill([
            'cleanup_type' => 'financial',
            'confirmation_text' => '',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('إعدادات النظام')
                    ->columns(2)
                    ->schema([
                        TextInput::make('payment_due_days')
                            ->label('فترة السماح بعد تاريخ الاستحقاق')
                            ->helperText('عدد الأيام المسموح بها للتأخير قبل اعتبار الدفعة متأخرة (مثال: 7 أيام من بداية الشهر)')
                            ->numeric()
                            ->default(7)
                            ->minValue(0)
                            ->maxValue(30)
                            ->suffix('أيام'),
                            
                        TextInput::make('allowed_delay_days')
                            ->label('أيام إضافية قبل الإجراءات')
                            ->helperText('أيام إضافية بعد انتهاء فترة السماح قبل اتخاذ إجراءات (غير مستخدم حالياً)')
                            ->numeric()
                            ->default(5)
                            ->minValue(0)
                            ->maxValue(30)
                            ->suffix('أيام')
                            ->disabled(),
                            
                        DatePicker::make('test_date')
                            ->label('يوم الاختبار المطلوب')
                            ->native(false)
                            ->displayFormat('Y-m-d')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }
    
    public function cleanupForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('⚠️ تحذير: هذا الإجراء لا يمكن التراجع عنه!')
                    ->schema([
                        Select::make('cleanup_type')
                            ->label('حذف بيانات النظام')
                            ->options([
                                'financial' => 'الماليات فقط (دفعات المستأجرين + دفعات الملاك + المصروفات)',
                                'all' => 'كل البيانات (العقود + الدفعات + المصروفات)',
                            ])
                            ->default('financial')
                            ->required()
                            ->native(false),
                            
                    ])
                    ->columns(1),
            ])
            ->statePath('cleanupData');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('حفظ الإعدادات')
                ->color('primary')
                ->icon('heroicon-o-check')
                ->action('saveSettings')
                ->requiresConfirmation()
                ->modalHeading('تأكيد الحفظ')
                ->modalDescription('هل أنت متأكد من حفظ هذه الإعدادات؟')
                ->modalSubmitActionLabel('نعم، احفظ'),
        ];
    }
    
    protected function getCleanupFormActions(): array
    {
        return [
            Action::make('executeCleanup')
                ->label('تنفيذ التنظيف')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->action('executeCleanup')
                ->requiresConfirmation()
                ->modalHeading('⚠️ تأكيد نهائي للحذف')
                ->modalDescription(fn () => 
                    ($this->cleanupData['cleanup_type'] ?? 'financial') === 'financial'
                        ? '⚠️ سيتم حذف جميع دفعات المستأجرين ودفعات الملاك والمصروفات نهائياً. هذا الإجراء لا يمكن التراجع عنه!'
                        : '⚠️⚠️⚠️ سيتم حذف جميع العقود والدفعات والمصروفات نهائياً. هذا الإجراء خطير جداً ولا يمكن التراجع عنه!'
                )
                ->modalSubmitActionLabel(fn () => 
                    ($this->cleanupData['cleanup_type'] ?? 'financial') === 'financial'
                        ? 'نعم، امسح البيانات المالية نهائياً'
                        : 'نعم، امسح كل شيء نهائياً'
                )
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->modalIconColor('danger')
                ->modalCancelActionLabel('إلغاء'),
        ];
    }
    
    public function executeCleanup(): void
    {
        try {
            $data = $this->cleanupForm->getState();
            
            DB::transaction(function () use ($data) {
                $cleanupType = $data['cleanup_type'] ?? 'financial';
                
                if ($cleanupType === 'financial') {
                    $this->cleanFinancialData();
                } else {
                    $this->cleanAllDataCompletely();
                }
            });
            
            $cleanupType = $data['cleanup_type'] ?? 'financial';
            $message = $cleanupType === 'financial' 
                ? 'تم حذف جميع البيانات المالية بنجاح'
                : 'تم حذف جميع العقود والدفعات والمصروفات بنجاح';
            
            logger()->info('System Data Cleanup Executed', [
                'user' => Auth::user()->email,
                'cleanup_type' => $cleanupType,
                'timestamp' => now(),
            ]);
            
            Notification::make()
                ->title('تم التنظيف بنجاح')
                ->body($message)
                ->success()
                ->duration(5000)
                ->send();
            
            // Reset the form
            $this->cleanupForm->fill([
                'cleanup_type' => 'financial',
            ]);
            
        } catch (\Exception $e) {
            logger()->error('Data Cleanup Failed', [
                'error' => $e->getMessage(),
                'user' => Auth::user()->email,
            ]);
            
            Notification::make()
                ->title('فشلت عملية التنظيف')
                ->body('حدث خطأ: ' . $e->getMessage())
                ->danger()
                ->duration(10000)
                ->send();
        }
    }

    public function saveSettings(): void
    {
        try {
            $data = $this->form->getState();
            
            // Save settings to database
            Setting::setMany([
                'allowed_delay_days' => $data['allowed_delay_days'] ?? 5,
                'payment_due_days' => $data['payment_due_days'] ?? 5,
                'test_date' => $data['test_date'] ?? null,
            ]);
            
            logger()->info('System Settings Saved', [
                'user' => Auth::user()->email,
                'settings' => $data,
                'timestamp' => now(),
            ]);

            Notification::make()
                ->title('تم الحفظ بنجاح')
                ->body('تم حفظ الإعدادات بنجاح')
                ->success()
                ->duration(3000)
                ->send();

        } catch (\Exception $e) {
            logger()->error('System Settings Save Failed', [
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('فشل الحفظ')
                ->body($e->getMessage())
                ->danger()
                ->duration(5000)
                ->send();
        }
    }

    public function cleanAllData(): void
    {
        try {
            $cleanupType = $this->form->getState()['cleanup_type'] ?? 'financial';
            
            DB::transaction(function () use ($cleanupType) {
                if ($cleanupType === 'financial') {
                    $this->cleanFinancialData();
                } else {
                    $this->cleanAllDataCompletely();
                }
            });
            
            $message = $cleanupType === 'financial' 
                ? 'تم حذف جميع البيانات المالية بنجاح'
                : 'تم حذف جميع العقود والدفعات والمصروفات بنجاح';
            
            Notification::make()
                ->title('تم المسح بنجاح')
                ->body($message)
                ->success()
                ->duration(5000)
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('فشل المسح')
                ->body('حدث خطأ أثناء المسح: ' . $e->getMessage())
                ->danger()
                ->duration(10000)
                ->send();
        }
    }
    
    /**
     * مسح البيانات المالية فقط
     */
    private function cleanFinancialData(): void
    {
        // مسح الدفعات والمصروفات فقط
        CollectionPayment::query()->delete();  // دفعات المستأجرين
        SupplyPayment::query()->delete();      // دفعات الملاك
        Expense::query()->delete();            // المصروفات
    }
    
    /**
     * مسح جميع البيانات بالكامل
     */
    private function cleanAllDataCompletely(): void
    {
        // 1. مسح الجداول التابعة أولاً
        $this->cleanFinancialData();
        
        // 2. مسح الجداول الرئيسية
        UnitContract::query()->delete();       // عقود الوحدات
        PropertyContract::query()->delete();   // عقود الملاك
    }

}