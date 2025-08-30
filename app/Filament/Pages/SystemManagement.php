<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;
use BackedEnum;

class SystemManagement extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'إدارة النظام';
    protected static ?string $title = 'إدارة النظام';
    protected static ?string $slug = 'system-management';
    protected static ?int $navigationSort = 100;
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected string $view = 'filament.pages.system-management';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::user()?->type === 'super_admin';
    }

    public function mount(): void
    {
        if (!static::canAccess()) {
            abort(403, 'Unauthorized');
        }

        // Load settings from database or use defaults
        $this->form->fill([
            'allowed_delay_days' => Setting::get('allowed_delay_days', 5),
            'test_date' => Setting::get('test_date', null),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Tabs::make('System Management')
                    ->tabs([
                        Tab::make('الإعدادات العامة')
                            ->icon('heroicon-o-cog')
                            ->schema([
                                TextInput::make('allowed_delay_days')
                                    ->label('مدة التأخير المسموحة (بالأيام)')
                                    ->numeric()
                                    ->default(5)
                                    ->minValue(0)
                                    ->maxValue(30)
                                    ->suffix('أيام'),
                            ]),

                        Tab::make('الاختبار')
                            ->icon('heroicon-o-beaker')
                            ->schema([
                                DatePicker::make('test_date')
                                    ->label('يوم الاختبار المطلوب')
                                    ->native(false)
                                    ->displayFormat('Y-m-d')
                                    ->helperText('حدد التاريخ الذي تريد اختبار النظام عليه'),
                            ]),
                    ]),
            ])
            ->statePath('data');
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

    public function saveSettings(): void
    {
        try {
            $data = $this->form->getState();
            
            // Save settings to database
            Setting::setMany([
                'allowed_delay_days' => $data['allowed_delay_days'] ?? 5,
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

}