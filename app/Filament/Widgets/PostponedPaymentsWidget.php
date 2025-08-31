<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\CollectionPayment;
use Carbon\Carbon;
use App\Helpers\DateHelper;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use App\Filament\Exports\PostponedPaymentExporter;

class PostponedPaymentsWidget extends BaseWidget
{
    protected static ?string $heading = 'المستأجرين الذين تم تأجيل دفعاتهم';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $pollingInterval = '30s';
    
    protected static bool $isLazy = false;
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                CollectionPayment::postponedPayments()
                    ->with(['tenant:id,name,phone', 'unit:id,name', 'property:id,name'])
                    ->select(['id', 'payment_number', 'tenant_id', 'unit_id', 'property_id', 
                             'amount', 'total_amount', 'delay_reason', 'delay_duration',
                             'due_date_start', 'due_date_end', 'late_payment_notes',
                             'collection_status', 'created_at'])
                    ->orderBy('due_date_end', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),
                    
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('رقم الدفعة')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('المستأجر')
                    ->searchable()
                    ->description(fn ($record) => $record->tenant?->phone ?? '-'),
                    
                Tables\Columns\TextColumn::make('property.name')
                    ->label('العقار')
                    ->searchable()
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->property?->name),
                    
                Tables\Columns\TextColumn::make('unit.name')
                    ->label('رقم الوحدة')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('المبلغ')
                    ->money('SAR')
                    ->alignCenter()
                    ->weight('bold')
                    ->color('danger'),
                    
                Tables\Columns\TextColumn::make('due_period')
                    ->label('فترة التحصيل')
                    ->getStateUsing(function ($record) {
                        $start = Carbon::parse($record->due_date_start)->format('Y/m/d');
                        $end = Carbon::parse($record->due_date_end)->format('Y/m/d');
                        return "من {$start} إلى {$end}";
                    })
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('delay_duration')
                    ->label('مدة التأجيل')
                    ->getStateUsing(function ($record) {
                        if (!$record->delay_duration) {
                            // احسب المدة من التاريخ باستخدام DateHelper
                            $days = Carbon::parse($record->due_date_end)->diffInDays(DateHelper::getCurrentDate());
                            return "{$days} يوم";
                        }
                        return "{$record->delay_duration} يوم";
                    })
                    ->badge()
                    ->color(fn ($state) => intval($state) > 30 ? 'danger' : 'warning'),
                    
                Tables\Columns\TextColumn::make('delay_reason')
                    ->label('سبب التأجيل')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->delay_reason)
                    ->default('غير محدد')
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('late_payment_notes')
                    ->label('ملاحظات')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->late_payment_notes)
                    ->default('-')
                    ->wrap(),
                    
                Tables\Columns\BadgeColumn::make('collection_status')
                    ->label('الحالة')
                    ->getStateUsing(fn () => 'مؤجلة')
                    ->color('warning')
                    ->icon('heroicon-o-clock'),
            ])
            ->defaultSort('due_date_end', 'asc')
            ->filters([
                Tables\Filters\Filter::make('critical')
                    ->label('حرجة (أكثر من 30 يوم)')
                    ->query(fn ($query) => $query->criticalPostponed()),
                    
                Tables\Filters\Filter::make('recent')
                    ->label('مؤجلة حديثاً (آخر 7 أيام)')
                    ->query(fn ($query) => $query->recentPostponed()),
                    
                Tables\Filters\SelectFilter::make('property_id')
                    ->label('العقار')
                    ->relationship('property', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('to')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->where('due_date_start', '>=', $data['from']))
                            ->when($data['to'], fn ($q) => $q->where('due_date_end', '<=', $data['to']));
                    }),
            ])
            ->headerActions([
                ExportAction::make('export')
                ->successNotificationTitle(false)
                    ->label('تصدير')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->modelLabel('دفعة مؤجلة')
                    ->pluralModelLabel('الدفعات المؤجلة')
                    ->exporter(PostponedPaymentExporter::class)
                    ->columnMapping(false)
                    ->formats([
                        ExportFormat::Xlsx,
                        ExportFormat::Csv,
                    ])
                    ->fileName(fn () => 'postponed-payments-' . date('Y-m-d')),
           ])
            ->bulkActions([
            ])
            ->paginated([5, 10, 25, 50])
            ->striped()
            ->poll('30s')
            ->emptyStateHeading('لا توجد دفعات مؤجلة')
            ->emptyStateDescription('جميع الدفعات في حالة جيدة')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
    
    // إحصائيات إضافية في الهيدر
    protected function getTableHeading(): ?string
    {
        $totalPostponed = CollectionPayment::postponed()->count();
        $criticalCount = CollectionPayment::criticalPostponed()->count();
        $totalAmount = CollectionPayment::postponed()->sum('total_amount');
        
        $formattedAmount = number_format($totalAmount, 2) . ' ريال';
        
        // إضافة التاريخ الحالي للاختبار
        $currentDate = DateHelper::formatDate();
        $dateLabel = DateHelper::isTestMode() ? " [تاريخ الاختبار: {$currentDate}]" : "";
        
        return static::$heading . " ({$totalPostponed} دفعة - {$criticalCount} حرجة - إجمالي: {$formattedAmount})" . $dateLabel;
    }
    
    public function getHeading(): string
    {
        return static::$heading ?? 'المستأجرين الذين تم تأجيل دفعاتهم';
    }
}