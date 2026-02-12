<?php

namespace App\Filament\Resources\Tenants;

use App\Filament\Concerns\HasFormComponents;
use App\Filament\Resources\Tenants\Schemas\TenantForm;
use App\Filament\Resources\Tenants\Tables\TenantsTable;
use App\Models\CollectionPayment;
use App\Models\Tenant;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TenantResource extends Resource
{
    use HasFormComponents;

    protected static ?string $model = Tenant::class;

    protected static ?string $navigationLabel = 'المستأجرين';

    protected static ?string $modelLabel = 'مستأجر';

    protected static ?string $pluralModelLabel = 'المستأجرين';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        $userType = auth()->user()?->type;

        return ! in_array($userType, ['owner', 'tenant']);
    }

    public static function canCreate(): bool
    {
        $userType = auth()->user()?->type;

        return in_array($userType, ['super_admin', 'admin', 'employee']);
    }

    public static function canEdit(Model $record): bool
    {
        $userType = auth()->user()?->type;

        return in_array($userType, ['super_admin', 'admin', 'employee']);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->type === 'super_admin';
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->type === 'super_admin';
    }

    public static function form(Schema $schema): Schema
    {
        return TenantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantsTable::configure($table);
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
            'view' => Pages\ViewTenant::route('/{record}'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
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
        $normalizedSearch = str_replace(['أ', 'إ', 'آ', 'ء', 'ؤ', 'ئ'], ['ا', 'ا', 'ا', '', 'و', 'ي'], $search);
        $searchWithoutSpaces = str_replace(' ', '', $normalizedSearch);
        $searchWithSpaces = str_replace(' ', '%', $normalizedSearch);

        $statusMap = [
            'محصل' => 'collected',
            'تم التحصيل' => 'collected',
            'مستحق' => 'due',
            'قادم' => 'upcoming',
            'متأخر' => 'overdue',
            'مؤجل' => 'postponed',
        ];

        $searchLower = mb_strtolower($search, 'UTF-8');
        $englishStatus = null;
        foreach ($statusMap as $arabic => $english) {
            if (str_contains(mb_strtolower($arabic, 'UTF-8'), $searchLower)) {
                $englishStatus = $english;
                break;
            }
        }

        $query = static::getModel()::query();

        if ($englishStatus) {
            $query->whereHas('collectionPayments', function (Builder $q) use ($englishStatus) {
                match ($englishStatus) {
                    'collected' => $q->collectedPayments(),
                    'due' => $q->dueForCollection(),
                    'upcoming' => $q->upcomingPayments(),
                    'overdue' => $q->overduePayments(),
                    'postponed' => $q->postponedPayments(),
                    default => $q
                };
            });
        } else {
            $query->where(function (Builder $query) use ($normalizedSearch, $searchWithoutSpaces, $searchWithSpaces, $search) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('secondary_phone', 'LIKE', "%{$search}%")
                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ء', ''), 'ؤ', 'و'), 'ئ', 'ي') LIKE ?", ["%{$normalizedSearch}%"])
                    ->orWhereRaw("REPLACE(name, ' ', '') LIKE ?", ["%{$searchWithoutSpaces}%"])
                    ->orWhere('name', 'LIKE', "%{$searchWithSpaces}%");

                // Date search only works on MySQL (DATE_FORMAT is MySQL-specific)
                if (DB::connection()->getDriverName() === 'mysql') {
                    $query->orWhereRaw("DATE_FORMAT(created_at, '%Y-%m-%d') LIKE ?", ["%{$search}%"])
                        ->orWhereRaw("DATE_FORMAT(created_at, '%d-%m-%Y') LIKE ?", ["%{$search}%"]);
                }
            });
        }

        return $query->limit(50)
            ->get()
            ->map(function ($record) use ($englishStatus) {
                $activeContract = $record->activeContract;
                $details = [
                    'الهاتف' => $record->phone ?? 'غير محدد',
                    'الهاتف الثاني' => $record->secondary_phone ?? 'غير محدد',
                    'العقار الحالي' => $activeContract ? $activeContract->property->name : 'لا يوجد عقد',
                    'الوحدة' => $activeContract ? $activeContract->unit->name : '-',
                ];

                if ($englishStatus) {
                    $count = match ($englishStatus) {
                        'collected' => $record->collectionPayments()->collectedPayments()->count(),
                        'due' => $record->collectionPayments()->dueForCollection()->count(),
                        'upcoming' => $record->collectionPayments()->upcomingPayments()->count(),
                        'overdue' => $record->collectionPayments()->overduePayments()->count(),
                        'postponed' => $record->collectionPayments()->postponedPayments()->count(),
                        default => 0
                    };

                    $statusLabel = match ($englishStatus) {
                        'collected' => 'محصلة',
                        'due' => 'مستحقة',
                        'upcoming' => 'قادمة',
                        'overdue' => 'متأخرة',
                        'postponed' => 'مؤجلة',
                        default => 'غير محدد'
                    };

                    $details = array_merge(['دفعات '.$statusLabel => $count.' دفعة'], $details);
                }

                return new GlobalSearchResult(
                    title: $record->name,
                    url: static::getUrl('view', ['record' => $record]),
                    details: $details,
                    actions: []
                );
            });
    }

    public static function getTenantStatistics($tenant): array
    {
        $tenant->load(['activeContract', 'unitContracts']);

        $activeContract = $tenant->activeContract;
        $totalContracts = $tenant->unitContracts->count();
        $activeContractsCount = $tenant->unitContracts->where('contract_status', 'active')->count();

        $totalPaid = CollectionPayment::where('tenant_id', $tenant->id)
            ->collectedPayments()
            ->sum('total_amount');

        $pendingPayments = CollectionPayment::where('tenant_id', $tenant->id)
            ->dueForCollection()
            ->sum('total_amount');

        $overduePayments = CollectionPayment::where('tenant_id', $tenant->id)
            ->overduePayments()
            ->sum('total_amount');

        $lastPayment = CollectionPayment::where('tenant_id', $tenant->id)
            ->collectedPayments()
            ->latest('collection_date')
            ->first();

        return [
            'tenant_name' => $tenant->name,
            'tenant_phone' => $tenant->phone,
            'current_property' => $activeContract?->property?->name,
            'current_unit' => $activeContract?->unit?->name,
            'monthly_rent' => $activeContract?->monthly_rent,
            'contract_end' => $activeContract?->end_date,
            'total_contracts' => $totalContracts,
            'active_contracts' => $activeContractsCount,
            'total_paid' => $totalPaid,
            'pending_payments' => $pendingPayments,
            'overdue_payments' => $overduePayments,
            'last_payment_date' => $lastPayment?->collection_date,
            'last_payment_amount' => $lastPayment?->total_amount,
        ];
    }
}
