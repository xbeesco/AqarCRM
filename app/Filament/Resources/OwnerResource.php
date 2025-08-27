<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OwnerResource\Pages;
use App\Models\Owner;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Filament\GlobalSearch\GlobalSearchResult;
use Illuminate\Support\Collection;
use App\Models\CollectionPayment;
use App\Models\SupplyPayment;
use App\Models\Property;
use Carbon\Carbon;
class OwnerResource extends Resource
{
    protected static ?string $model = Owner::class;

    protected static ?string $navigationLabel = 'الملاك';

    protected static ?string $modelLabel = 'مالك';

    protected static ?string $pluralModelLabel = 'الملاك';
    
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('معلومات عامة')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->label('الاسم الكامل')
                            ->maxLength(255)
                            ->columnSpan('full'),

                        TextInput::make('phone')
                            ->numeric()
                            ->required()
                            ->label('الهاتف الأول')
                            ->maxLength(20)
                            ->columnSpan(6),

                        TextInput::make('secondary_phone')
                            ->numeric()
                            ->label('الهاتف الثاني')
                            ->maxLength(20)
                            ->columnSpan(6),

                        FileUpload::make('identity_file')
                            ->label('ملف الهوية')
                            ->directory('employees/identities')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(5120)
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->columnSpan('full'),
                    ])
                    ->columns(12)
                    ->columnSpan('full'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('رقم الهاتف الأول')
                    ->searchable(),

                TextColumn::make('secondary_phone')
                    ->label('رقم الهاتف الثاني')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label('تاريخ الحذف')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                Action::make('view_report')
                    ->label('تقرير')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->modalHeading(fn ($record) => 'تقرير المالك: ' . $record->name)
                    ->modalContent(fn ($record) => view('filament.reports.owner-comprehensive-report', [
                        'owner' => $record,
                        'stats' => static::getOwnerStatistics($record),
                        'recentPayments' => static::getRecentPayments($record),
                        'dateRange' => [
                            'from' => now()->subYear()->format('Y-m-d'),
                            'to' => now()->format('Y-m-d')
                        ]
                    ]))
                    ->modalWidth('7xl')
                    ->modalCancelActionLabel('إلغاء')
                    ->modalSubmitAction(
                        Action::make('print')
                            ->label('طباعة')
                            ->icon('heroicon-o-printer')
                            ->color('success')
                            ->action(fn () => null)
                            ->extraAttributes([
                                'onclick' => 'window.print(); return false;',
                            ])
                    ),
            ])
            ->toolbarActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOwners::route('/'),
            'create' => Pages\CreateOwner::route('/create'),
            'edit' => Pages\EditOwner::route('/{record}/edit'),
            'view' => Pages\ViewOwner::route('/{record}'),
        ];
    }
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'phone', 'secondary_phone'];
    }
    
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery();
    }
    
    public static function getGlobalSearchResults(string $search): Collection
    {
        // تنظيف البحث وإزالة الهمزات
        $normalizedSearch = str_replace(
            ['أ', 'إ', 'آ', 'ء', 'ؤ', 'ئ'],
            ['ا', 'ا', 'ا', '', 'و', 'ي'],
            $search
        );
        
        // إزالة المسافات الزائدة
        $searchWithoutSpaces = str_replace(' ', '', $normalizedSearch);
        $searchWithSpaces = str_replace(' ', '%', $normalizedSearch);
        
        // خريطة الترجمات العربية لحالات التوريد
        $statusMap = [
            'محول' => 'collected',
            'تم التحويل' => 'collected',
            'محصل' => 'collected',
            'تم التحصيل' => 'collected',
            'معلق' => 'pending',
            'انتظار' => 'pending',
            'جاهز للتحصيل' => 'worth_collecting',
            'جاهز' => 'worth_collecting',
        ];
        
        // تحويل البحث إلى حروف صغيرة للبحث في حالات التوريد
        $searchLower = mb_strtolower($search, 'UTF-8');
        $englishStatus = null;
        foreach ($statusMap as $arabic => $english) {
            if (str_contains(mb_strtolower($arabic, 'UTF-8'), $searchLower)) {
                $englishStatus = $english;
                break;
            }
        }
        
        $query = static::getModel()::query();
        
        // إذا كان البحث عن حالة توريد، ابحث عن الملاك الذين لديهم دفعات بهذه الحالة
        if ($englishStatus) {
            $query->whereHas('supplyPayments', function (Builder $q) use ($englishStatus) {
                $q->where('supply_status', $englishStatus);
            });
        } else {
            // البحث العادي
            $query->where(function (Builder $query) use ($normalizedSearch, $searchWithoutSpaces, $searchWithSpaces, $search) {
                $query->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('phone', 'LIKE', "%{$search}%")
                      ->orWhere('secondary_phone', 'LIKE', "%{$search}%")
                      // البحث بدون همزات
                      ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ء', ''), 'ؤ', 'و'), 'ئ', 'ي') LIKE ?", ["%{$normalizedSearch}%"])
                      // البحث بدون مسافات
                      ->orWhereRaw("REPLACE(name, ' ', '') LIKE ?", ["%{$searchWithoutSpaces}%"])
                      // البحث مع تجاهل المسافات في الكلمة المبحوث عنها
                      ->orWhere('name', 'LIKE', "%{$searchWithSpaces}%")
                      // البحث بالتواريخ - تاريخ الإنشاء
                      ->orWhereRaw("DATE_FORMAT(created_at, '%Y-%m-%d') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("DATE_FORMAT(created_at, '%d-%m-%Y') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("DATE_FORMAT(created_at, '%Y/%m/%d') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("DATE_FORMAT(created_at, '%d/%m/%Y') LIKE ?", ["%{$search}%"])
                      // البحث بالتواريخ - تاريخ الحذف
                      ->orWhereRaw("DATE_FORMAT(deleted_at, '%Y-%m-%d') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("DATE_FORMAT(deleted_at, '%d-%m-%Y') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("DATE_FORMAT(deleted_at, '%Y/%m/%d') LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("DATE_FORMAT(deleted_at, '%d/%m/%Y') LIKE ?", ["%{$search}%"]);
            });
        }
        
        return $query->limit(50)
            ->get()
            ->map(function ($record) use ($englishStatus) {
                // إضافة معلومات عن حالة التوريد في التفاصيل إذا كان البحث عنها
                $details = [
                    'الهاتف' => $record->phone ?? 'غير محدد',
                    'الهاتف الثاني' => $record->secondary_phone ?? 'غير محدد',
                    'تاريخ الإنشاء' => $record->created_at?->format('Y-m-d') ?? 'غير محدد',
                    'تاريخ الحذف' => $record->deleted_at?->format('Y-m-d') ?? 'نشط',
                ];
                
                // إضافة عدد المدفوعات بحالة التوريد المبحوث عنها
                if ($englishStatus) {
                    $count = $record->supplyPayments()
                        ->where('supply_status', $englishStatus)
                        ->count();
                    
                    $statusLabel = match($englishStatus) {
                        'collected' => 'محول',
                        'worth_collecting' => 'جاهز للتحصيل',
                        'pending' => 'معلق',
                        default => 'غير محدد'
                    };
                    
                    $details = array_merge([
                        "مدفوعات {$statusLabel}" => $count . ' دفعة'
                    ], $details);
                }
                
                return new \Filament\GlobalSearch\GlobalSearchResult(
                    title: $record->name,
                    url: static::getUrl('edit', ['record' => $record]),
                    details: $details,
                    actions: static::getGlobalSearchResultActions($record)
                );
            });
    }
    
    public static function getGlobalSearchResultActions(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            Action::make('view_report')
                ->label('عرض التقرير')
                ->url(\App\Filament\Pages\Reports\OwnerReport::getUrl() . '?owner_id=' . $record->id)
                ->icon('heroicon-o-document-text')
                ->color('info')
        ];
    }

    public static function getRecentPayments($owner)
    {
        return SupplyPayment::where('owner_id', $owner->id)
            ->where('supply_status', 'collected')
            ->latest('paid_date')
            ->limit(5)
            ->get();
    }

    public static function getOwnerStatistics($owner): array
    {
        // تحميل العلاقات
        $owner->load(['properties', 'properties.units']);
        
        // إحصائيات العقارات
        $propertiesCount = $owner->properties->count();
        $totalUnits = $owner->properties->sum(function($property) {
            return $property->units->count();
        });
        $occupiedUnits = $owner->properties->sum(function($property) {
            return $property->units->where('status', 'occupied')->count();
        });
        $vacantUnits = $totalUnits - $occupiedUnits;
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100) : 0;

        // إحصائيات مالية - آخر 12 شهر
        $dateFrom = now()->subYear();
        $dateTo = now();
        
        // إجمالي التحصيل من عقارات المالك
        $totalCollection = CollectionPayment::whereHas('property', function($q) use ($owner) {
            $q->where('owner_id', $owner->id);
        })
        ->where('collection_status', 'collected')
        ->whereBetween('paid_date', [$dateFrom, $dateTo])
        ->sum('total_amount');
        
        // الرسوم الإدارية (10% افتراضي)
        $managementFees = $totalCollection * 0.10;
        
        // صافي المبلغ المستحق للمالك
        $ownerDue = $totalCollection - $managementFees;
        
        // المبالغ المحولة للمالك فعلياً
        $paidToOwner = SupplyPayment::where('owner_id', $owner->id)
            ->where('supply_status', 'collected')
            ->whereBetween('paid_date', [$dateFrom, $dateTo])
            ->sum('net_amount');
        
        // الرصيد المعلق
        $pendingBalance = $ownerDue - $paidToOwner;
        
        // عدد العمليات المالية
        $totalOperations = SupplyPayment::where('owner_id', $owner->id)->count();
        $completedOperations = SupplyPayment::where('owner_id', $owner->id)
            ->where('supply_status', 'collected')
            ->count();
        
        // آخر عملية تحويل
        $lastPayment = SupplyPayment::where('owner_id', $owner->id)
            ->where('supply_status', 'collected')
            ->latest('paid_date')
            ->first();
        
        // العملية القادمة (المعلقة)
        $nextPayment = SupplyPayment::where('owner_id', $owner->id)
            ->where('supply_status', 'pending')
            ->oldest('created_at')
            ->first();
        
        // متوسط الدخل الشهري
        $averageMonthlyIncome = $paidToOwner / 12;
        
        // نسبة التحويل
        $transferRate = $ownerDue > 0 ? round(($paidToOwner / $ownerDue) * 100) : 0;

        return [
            // معلومات المالك
            'owner_name' => $owner->name,
            'owner_phone' => $owner->phone,
            'owner_secondary_phone' => $owner->secondary_phone,
            'owner_email' => $owner->email,
            'identity_file' => $owner->identity_file,
            'created_at' => $owner->created_at,
            
            // إحصائيات العقارات
            'properties_count' => $propertiesCount,
            'total_units' => $totalUnits,
            'occupied_units' => $occupiedUnits,
            'vacant_units' => $vacantUnits,
            'occupancy_rate' => $occupancyRate,
            'properties_list' => $owner->properties->pluck('name')->toArray(),
            
            // الإحصائيات المالية
            'total_collection' => $totalCollection,
            'management_fees' => $managementFees,
            'owner_due' => $ownerDue,
            'paid_to_owner' => $paidToOwner,
            'pending_balance' => $pendingBalance,
            'transfer_rate' => $transferRate,
            'average_monthly_income' => $averageMonthlyIncome,
            
            // معلومات العمليات
            'total_operations' => $totalOperations,
            'completed_operations' => $completedOperations,
            'completion_rate' => $totalOperations > 0 ? round(($completedOperations / $totalOperations) * 100) : 0,
            
            // آخر دفعة والدفعة القادمة
            'last_payment' => $lastPayment ? [
                'payment_number' => $lastPayment->payment_number,
                'amount' => $lastPayment->net_amount,
                'payment_date' => $lastPayment->paid_date,
            ] : null,
            
            'next_payment' => $nextPayment ? [
                'payment_number' => $nextPayment->payment_number,
                'amount' => $nextPayment->net_amount,
                'created_date' => $nextPayment->created_at,
            ] : null,
            
            // حالة المالك
            'is_active' => $propertiesCount > 0 && $occupancyRate > 0,
            'performance_level' => $transferRate >= 80 ? 'excellent' : ($transferRate >= 50 ? 'good' : 'needs_attention'),
        ];
    }

}