<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Models\Tenant;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\TextInput as FilterTextInput;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Filament\GlobalSearch\GlobalSearchResult;
use Illuminate\Support\Collection;
use App\Models\CollectionPayment;
use App\Models\UnitContract;
use Carbon\Carbon;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationLabel = 'المستأجرين';

    protected static ?string $modelLabel = 'مستأجر';

    protected static ?string $pluralModelLabel = 'المستأجرين';
    
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
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(12)
            ->recordActions([
                ViewAction::make()
                    ->label(''),
                EditAction::make()
                    ->label(''),
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
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
            'view' => Pages\ViewTenant::route('/{record}'),
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
        
        $query = static::getModel()::query();
        
        return $query->where(function (Builder $query) use ($normalizedSearch, $searchWithoutSpaces, $searchWithSpaces, $search) {
            // البحث العادي
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
        })
        ->limit(50)
        ->get()
        ->map(function ($record) {
            return new \Filament\GlobalSearch\GlobalSearchResult(
                title: $record->name,
                url: static::getUrl('edit', ['record' => $record]),
                details: [
                    'الهاتف' => $record->phone ?? 'غير محدد',
                    'الهاتف الثاني' => $record->secondary_phone ?? 'غير محدد',
                    'تاريخ الإنشاء' => $record->created_at?->format('Y-m-d') ?? 'غير محدد',
                    'تاريخ الحذف' => $record->deleted_at?->format('Y-m-d') ?? 'نشط',
                ],
                actions: []
            );
        });
    }

    public static function getTenantStatistics($tenant): array
    {
        // التأكد من أننا نعمل مع Tenant وليس User
        if (!($tenant instanceof \App\Models\Tenant)) {
            // إذا كان User، نحوله إلى Tenant
            $tenant = \App\Models\Tenant::find($tenant->id);
            if (!$tenant) {
                return [];
            }
        }
        
        // تحميل العلاقات
        $tenant->load(['rentalContracts', 'paymentHistory']);
        
        // العقد النشط الحالي
        $activeContract = $tenant->rentalContracts()
            ->where('contract_status', 'active')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->with(['unit', 'property'])
            ->first();
        
        // إجمالي المدفوعات
        $totalPayments = $tenant->paymentHistory()
            ->where('collection_status', 'collected')
            ->sum('total_amount');
        
        // المستحقات غير المدفوعة
        $outstandingPayments = $tenant->paymentHistory()
            ->whereIn('collection_status', ['due', 'overdue'])
            ->sum('total_amount');
        
        // عدد المدفوعات المتأخرة
        $overdueCount = $tenant->paymentHistory()
            ->where('collection_status', 'overdue')
            ->count();
        
        // تاريخ آخر دفعة
        $lastPayment = $tenant->paymentHistory()
            ->where('collection_status', 'collected')
            ->latest('paid_date')
            ->first();
        
        // تاريخ الدفعة القادمة
        $nextPayment = $tenant->paymentHistory()
            ->where('collection_status', 'due')
            ->oldest('due_date_start')
            ->first();
        
        // عدد العقود الإجمالي
        $totalContracts = $tenant->rentalContracts()->count();
        
        // عدد العقود المنتهية
        $expiredContracts = $tenant->rentalContracts()
            ->where('contract_status', 'expired')
            ->count();
        
        // متوسط مدة الإيجار
        $avgContractDuration = $tenant->rentalContracts()
            ->whereIn('contract_status', ['active', 'completed', 'expired'])
            ->selectRaw('AVG(DATEDIFF(end_date, start_date)) as avg_days')
            ->value('avg_days');
        
        $avgContractMonths = $avgContractDuration ? round($avgContractDuration / 30) : 0;
        
        // حساب نسبة الالتزام بالدفع
        $totalDuePayments = $tenant->paymentHistory()
            ->whereIn('collection_status', ['collected', 'due', 'overdue'])
            ->count();
        
        $paidOnTimePayments = $tenant->paymentHistory()
            ->where('collection_status', 'collected')
            ->whereColumn('paid_date', '<=', 'due_date_end')
            ->count();
        
        $paymentComplianceRate = $totalDuePayments > 0 ? 
            round(($paidOnTimePayments / $totalDuePayments) * 100) : 0;
        
        // إجمالي غرامات التأخير
        $totalLateFees = $tenant->paymentHistory()
            ->sum('late_fee');
        
        // معلومات الوحدة والعقار الحالي
        $currentUnit = $activeContract ? $activeContract->unit : null;
        $currentProperty = $activeContract ? $activeContract->property : null;
        
        return [
            // معلومات المستأجر
            'tenant_name' => $tenant->name,
            'tenant_phone' => $tenant->phone,
            'tenant_secondary_phone' => $tenant->secondary_phone,
            'tenant_email' => $tenant->email,
            'identity_file' => $tenant->identity_file,
            'created_at' => $tenant->created_at,
            
            // معلومات العقد الحالي
            'has_active_contract' => $activeContract !== null,
            'current_contract' => $activeContract ? [
                'contract_number' => $activeContract->contract_number,
                'start_date' => $activeContract->start_date,
                'end_date' => $activeContract->end_date,
                'monthly_rent' => $activeContract->monthly_rent,
                'security_deposit' => $activeContract->security_deposit,
                'payment_method' => $activeContract->payment_method,
                'days_remaining' => now()->diffInDays($activeContract->end_date, false),
            ] : null,
            
            // معلومات الوحدة الحالية
            'current_unit' => $currentUnit ? [
                'name' => $currentUnit->name,
                'floor_number' => $currentUnit->floor_number,
                'rooms_count' => $currentUnit->rooms_count,
                'area_sqm' => $currentUnit->area_sqm,
                'rent_price' => $currentUnit->rent_price,
            ] : null,
            
            // معلومات العقار الحالي
            'current_property' => $currentProperty ? [
                'name' => $currentProperty->name,
                'address' => $currentProperty->address,
                'type' => $currentProperty->type,
            ] : null,
            
            // الإحصائيات المالية
            'total_payments' => $totalPayments,
            'outstanding_payments' => $outstandingPayments,
            'overdue_count' => $overdueCount,
            'total_late_fees' => $totalLateFees,
            'payment_compliance_rate' => $paymentComplianceRate,
            
            // معلومات الدفعات
            'last_payment' => $lastPayment ? [
                'payment_number' => $lastPayment->payment_number,
                'amount' => $lastPayment->total_amount,
                'paid_date' => $lastPayment->paid_date,
            ] : null,
            
            'next_payment' => $nextPayment ? [
                'payment_number' => $nextPayment->payment_number,
                'amount' => $nextPayment->total_amount,
                'due_date' => $nextPayment->due_date_start,
                'days_until_due' => now()->diffInDays($nextPayment->due_date_start, false),
            ] : null,
            
            // إحصائيات العقود
            'total_contracts' => $totalContracts,
            'expired_contracts' => $expiredContracts,
            'avg_contract_months' => $avgContractMonths,
            
            // معدلات الأداء
            'is_good_standing' => $overdueCount === 0 && $outstandingPayments === 0,
            'risk_level' => $overdueCount > 3 ? 'high' : ($overdueCount > 1 ? 'medium' : 'low'),
        ];
    }

    public static function getRecentPayments($tenant, $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        // التأكد من أننا نعمل مع Tenant وليس User
        if (!($tenant instanceof \App\Models\Tenant)) {
            $tenant = \App\Models\Tenant::find($tenant->id);
            if (!$tenant) {
                return collect();
            }
        }
        
        return CollectionPayment::where('tenant_id', $tenant->id)
            ->with(['paymentStatus', 'paymentMethod', 'unit', 'property', 'unitContract'])
            ->orderBy('due_date_start', 'desc')
            ->limit($limit)
            ->get();
    }

}