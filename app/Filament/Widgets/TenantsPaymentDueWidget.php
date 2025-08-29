<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Grouping\Group;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\CollectionPayment;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Actions\Action;

class TenantsPaymentDueWidget extends BaseWidget
{
    protected static ?string $heading = 'المستأجرين المستحقين للتحصيل';
    
    protected static ?int $sort = 4;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $pollingInterval = '30s';
    
    protected static bool $isLazy = false;
    
    protected function getToday(): Carbon
    {
        return env('TEST_DATE') ? 
            Carbon::parse(env('TEST_DATE'))->startOfDay() : 
            Carbon::today();
    }
    
    public function table(Table $table): Table
    {
        $today = $this->getToday();
        
        return $table
            ->query(
                CollectionPayment::with(['tenant', 'property', 'unit'])
                    ->where('due_date_start', '<=', $today)
                    ->whereIn('collection_status', ['due', 'postponed'])
                    ->orderBy('property_id')
                    ->orderBy('due_date_start', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),
                    
                Tables\Columns\TextColumn::make('property.name')
                    ->label('العقار')
                    ->searchable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('المستأجر')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('unit.name')
                    ->label('الوحدة')
                    ->searchable()
                    ->badge()
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('قيمة الدفعة')
                    ->money('SAR')
                    ->alignCenter()
                    ->color('danger')
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('due_date_start')
                    ->label('تاريخ الدفعة')
                    ->date('Y-m-d')
                    ->alignCenter()
                    ->sortable()
                    ->color(fn ($record) => 
                        Carbon::parse($record->due_date_start)->isPast() ? 'danger' : 'success'
                    )
                    ->weight(fn ($record) => 
                        Carbon::parse($record->due_date_start)->isPast() ? 'bold' : 'normal'
                    ),
                    
                Tables\Columns\TextColumn::make('tenant.phone')
                    ->label('رقم الجوال')
                    ->searchable()
                    ->icon('heroicon-o-phone')
                    ->copyable()
                    ->copyMessage('تم نسخ رقم الهاتف')
                    ->default('-'),
            ])
            ->defaultGroup(
                Group::make('property.name')
                    ->label('العقار')
                    ->collapsible()
            )
            ->recordActions([
                Action::make('view_payment')
                    ->label('عرض الدفعة')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (CollectionPayment $record): string => 
                        route('filament.admin.resources.collection-payments.view', $record->id)
                    ),
            ])
            ->defaultSort('property_id', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('property_id')
                    ->label('العقار')
                    ->relationship('property', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('collection_status')
                    ->label('حالة التحصيل')
                    ->options([
                        'due' => 'مستحق',
                        'postponed' => 'مؤجل',
                    ]),
                    
                Tables\Filters\Filter::make('overdue')
                    ->label('متأخر')
                    ->query(fn ($query) => $query->where('due_date_start', '<', Carbon::today())),
            ])
            ->paginated([10, 25, 50])
            ->striped()
            ->poll('30s')
            ->emptyStateHeading('لا يوجد مستأجرون مستحقون للتحصيل')
            ->emptyStateDescription('جميع المستحقات محصلة أو غير مستحقة بعد')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
    
    protected function getTableHeading(): ?string
    {
        $today = $this->getToday();
        
        $totalDue = CollectionPayment::where('due_date_start', '<=', $today)
            ->whereIn('collection_status', ['due', 'postponed'])
            ->count();
            
        $totalAmount = CollectionPayment::where('due_date_start', '<=', $today)
            ->whereIn('collection_status', ['due', 'postponed'])
            ->sum('amount');
        
        $formattedAmount = number_format($totalAmount, 2) . ' ريال';
        
        return static::$heading . " ({$totalDue} دفعة - إجمالي: {$formattedAmount})";
    }
}