<?php

namespace App\Filament\Pages;

use Exception;
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
use App\Services\SystemPurgeService;
use App\Helpers\DateHelper;
use BackedEnum;

class SystemManagement extends Page implements HasSchemas
{
    use InteractsWithSchemas;
    
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'إدارة النظام';
    protected static ?string $title = 'إدارة النظام';
    protected static ?string $slug = 'system-management';
    protected static ?int $navigationSort = 100;
    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

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
                            ->numeric()
                            ->default(7)
                            ->minValue(0)
                            ->maxValue(30)
                            ->suffix('أيام'),
                            
                        TextInput::make('allowed_delay_days')
                            ->label('أيام إضافية قبل الإجراءات')
                            ->numeric()
                            ->default(5)
                            ->minValue(0)
                            ->maxValue(30)
                            ->suffix('أيام')
                            ->disabled(),
                            
                        DatePicker::make('test_date')
                            ->label('التاريخ الاختباري للنظام')
                            ->native(false)
                            ->displayFormat('Y-m-d')
                            ->minDate(fn() => now()->subDays(floor(config('session.lifetime', 120) / 60 / 24)))
                            ->maxDate(fn() => now()->addDays(floor(config('session.lifetime', 120) / 60 / 24)))
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
                                'financial' => 'حذف البيانات المالية فقط (دفعات المستأجرين + دفعات الملاك + المصروفات)',
                                'financial_contracts' => 'حذف البيانات المالية + التعاقدات (عقود الوحدات + عقود العقارات)',
                                'financial_contracts_properties' => 'حذف البيانات المالية + التعاقدات + العقارات (العقارات + الوحدات + المستأجرين + الملاك)',
                                'all' => 'حذف كافة البيانات (الماليات + التعاقدات + العقارات + التأسيس)',
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
                ->modalDescription(fn () => match ($this->cleanupData['cleanup_type'] ?? 'financial') {
                    'financial' => '⚠️ سيتم حذف جميع دفعات المستأجرين ودفعات الملاك والمصروفات نهائياً. هذا الإجراء لا يمكن التراجع عنه!',
                    'financial_contracts' => '⚠️ سيتم حذف الماليات بالإضافة إلى جميع عقود الوحدات وعقود العقارات نهائياً.',
                    'financial_contracts_properties' => '⚠️ سيتم حذف الماليات + العقود + العقارات والوحدات، وسيتم حذف الملاك والمستأجرين (كمستخدمين).',
                    default => '⚠️⚠️⚠️ سيتم حذف كافة البيانات (الماليات + العقود + العقارات + بيانات التأسيس). هذا الإجراء خطير جداً ولا يمكن التراجع عنه!'
                })
                ->modalSubmitActionLabel(fn () => match ($this->cleanupData['cleanup_type'] ?? 'financial') {
                    'financial' => 'نعم، امسح البيانات المالية نهائياً',
                    'financial_contracts' => 'نعم، امسح الماليات والتعاقدات',
                    'financial_contracts_properties' => 'نعم، امسح الماليات + التعاقدات + العقارات',
                    default => 'نعم، امسح كافة البيانات'
                })
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->modalIconColor('danger')
                ->modalCancelActionLabel('إلغاء'),
        ];
    }
    
    public function executeCleanup(): void
    {
        try {
            $data = $this->cleanupForm->getState();
            $cleanupType = $data['cleanup_type'] ?? 'financial';

            // Execute centralized purge service
            $service = new SystemPurgeService();
            $summary = $service->purge($cleanupType);
            
            $message = match ($cleanupType) {
                'financial' => 'تم حذف جميع البيانات المالية بنجاح',
                'financial_contracts' => 'تم حذف الماليات وجميع التعاقدات بنجاح',
                'financial_contracts_properties' => 'تم حذف الماليات + التعاقدات + العقارات والملاك والمستأجرين بنجاح',
                default => 'تم حذف كافة البيانات (الماليات + التعاقدات + العقارات + التأسيس) بنجاح',
            };
            
            logger()->info('System Data Cleanup Executed', [
                'user' => Auth::user()->email,
                'cleanup_type' => $cleanupType,
                'summary' => $summary,
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
            
        } catch (Exception $e) {
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
            
            // Set test date using DateHelper
            if (!empty($data['test_date'])) {
                DateHelper::setTestDate($data['test_date']);
            } else {
                DateHelper::clearTestDate();
            }
            
            logger()->info('System Settings Saved', [
                'user' => Auth::user()->email,
                'settings' => $data,
                'timestamp' => now(),
            ]);

            Notification::make()
                ->title('تم الحفظ بنجاح')
                ->body('تم حفظ الإعدادات بنجاح. سيتم إعادة تحميل الصفحة...')
                ->success()
                ->duration(2000)
                ->send();
            
            // Reload the page after a short delay to apply the new test date
            $this->redirect(static::getUrl(), navigate: true);

        } catch (Exception $e) {
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

}