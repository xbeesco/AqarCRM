<?php

namespace App\Filament\Resources\Owners;

use App\Filament\Resources\Owners\Pages;
use App\Models\CollectionPayment;
use App\Models\Owner;
use App\Models\SupplyPayment;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OwnerResource extends Resource
{
    protected static ?string $model = Owner::class;

    protected static ?string $navigationLabel = 'الملاك';

    protected static ?string $modelLabel = 'مالك';

    protected static ?string $pluralModelLabel = 'الملاك';

    protected static ?string $recordTitleAttribute = 'name';

    // صلاحيات الوصول للـ Resource
    public static function canViewAny(): bool
    {
        $userType = auth()->user()?->type;

        // الكل يمكنه رؤية الملاك ماعدا owner و tenant
        return ! in_array($userType, ['owner', 'tenant']);
    }

    public static function canCreate(): bool
    {
        $userType = auth()->user()?->type;

        // super_admin و admin و employee يمكنهم إضافة ملاك
        return in_array($userType, ['super_admin', 'admin', 'employee']);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $userType = auth()->user()?->type;

        // super_admin و admin و employee يمكنهم تعديل الملاك
        return in_array($userType, ['super_admin', 'admin', 'employee']);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        // فقط super_admin يمكنه حذف الملاك
        return auth()->user()?->type === 'super_admin';
    }

    public static function canDeleteAny(): bool
    {
        // فقط super_admin يمكنه الحذف الجماعي
        return auth()->user()?->type === 'super_admin';
    }

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
                            ->directory('owner--identity-file')
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
                    ->label('التليفون 1')
                    ->searchable(),

                TextColumn::make('secondary_phone')
                    ->label('التليفون 2')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('الإيميل')
                    ->searchable(),

                \Filament\Tables\Columns\ImageColumn::make('identity_file')
                    ->label('ملف الهوية')
                    ->disk('local')
                    ->height(40)
                    ->width(40)
                    ->defaultImageUrl(asset('images/no-image.png'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([])
            ->recordActions([
                ViewAction::make()
                    ->label('تقرير')
                    ->icon('heroicon-o-document-text'),
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square'),
                ActionGroup::make([
                    Action::make('view_properties')
                        ->label('العقارات')
                        ->url(fn ($record) => PropertyResource::getUrl('index').'?owner_id='.$record->id),
                    Action::make('view_units')
                        ->label('الوحدات')
                        ->url(fn ($record) => UnitResource::getUrl('index').'?owner_id='.$record->id),
                    Action::make('view_property_contracts')
                        ->label('عقود العقارات')
                        ->url(fn ($record) => PropertyContractResource::getUrl('index').'?owner_id='.$record->id),
                    Action::make('view_unit_contracts')
                        ->label('عقود الوحدات')
                        ->url(fn ($record) => UnitContractResource::getUrl('index').'?owner_id='.$record->id),
                    Action::make('view_supply_payments')
                        ->label('دفعات المالك')
                        ->url(fn ($record) => SupplyPaymentResource::getUrl('index').'?owner_id='.$record->id),
                    Action::make('view_collection_payments')
                        ->label('دفعات المستأجرين')
                        ->url(fn ($record) => CollectionPaymentResource::getUrl('index').'?owner_id='.$record->id),
                    Action::make('view_expenses')
                        ->label('النفقات')
                        ->url(fn ($record) => ExpenseResource::getUrl('index').'?owner_id='.$record->id),
                ])->label('عرض البيانات'),
            ])
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
                match ($englishStatus) {
                    'collected' => $q->collected(),
                    'pending' => $q->pending(),
                    'worth_collecting' => $q->worthCollecting(),
                    default => $q
                };
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
                    $count = match ($englishStatus) {
                        'collected' => $record->supplyPayments()->collected()->count(),
                        'pending' => $record->supplyPayments()->pending()->count(),
                        'worth_collecting' => $record->supplyPayments()->worthCollecting()->count(),
                        default => 0
                    };

                    $statusLabel = match ($englishStatus) {
                        'collected' => 'محول',
                        'worth_collecting' => 'جاهز للتحصيل',
                        'pending' => 'معلق',
                        default => 'غير محدد'
                    };

                    $details = array_merge([
                        "مدفوعات {$statusLabel}" => $count.' دفعة',
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
            // Action::make('view_report')
            //     ->label('عرض التقرير')
            //     ->url(\App\Filament\Pages\Reports\OwnerReport::getUrl() . '?owner_id=' . $record->id)
            //     ->icon('heroicon-o-document-text')
            //     ->color('info')
        ];
    }

    public static function getRecentPayments($owner)
    {
        return SupplyPayment::where('owner_id', $owner->id)
            ->collected()
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
        $totalUnits = $owner->properties->sum(function ($property) {
            return $property->units->count();
        });
        $occupiedUnits = $owner->properties->sum(function ($property) {
            return $property->units->where('status', 'occupied')->count();
        });
        $vacantUnits = $totalUnits - $occupiedUnits;
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100) : 0;

        // إحصائيات مالية - آخر 12 شهر
        $dateFrom = now()->subYear();
        $dateTo = now();

        // إجمالي التحصيل من عقارات المالك
        $totalCollection = CollectionPayment::where('property_id', function ($query) use ($owner) {
            $query->select('id')
                ->from('properties')
                ->where('owner_id', $owner->id);
        })
            ->collectedPayments()
            ->whereBetween('paid_date', [$dateFrom, $dateTo])
            ->sum('total_amount');

        // الرسوم الإدارية (10% افتراضي)
        $managementFees = $totalCollection * 0.10;

        // صافي المبلغ المستحق للمالك
        $ownerDue = $totalCollection - $managementFees;

        // المبالغ المحولة للمالك فعلياً
        $paidToOwner = SupplyPayment::where('owner_id', $owner->id)
            ->collected()
            ->whereBetween('paid_date', [$dateFrom, $dateTo])
            ->sum('net_amount');

        // الرصيد المعلق
        $pendingBalance = $ownerDue - $paidToOwner;

        // عدد العمليات المالية
        $totalOperations = SupplyPayment::where('owner_id', $owner->id)->count();
        $completedOperations = SupplyPayment::where('owner_id', $owner->id)
            ->collected()
            ->count();

        // آخر عملية تحويل
        $lastPayment = SupplyPayment::where('owner_id', $owner->id)
            ->collected()
            ->latest('paid_date')
            ->first();

        // العملية القادمة (المعلقة)
        $nextPayment = SupplyPayment::where('owner_id', $owner->id)
            ->pending()
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
