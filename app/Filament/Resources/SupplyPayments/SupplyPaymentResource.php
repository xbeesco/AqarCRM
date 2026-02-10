<?php

namespace App\Filament\Resources\SupplyPayments;

use App\Filament\Resources\SupplyPayments\Schemas\SupplyPaymentForm;
use App\Filament\Resources\SupplyPayments\Tables\SupplyPaymentsTable;
use App\Models\SupplyPayment;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SupplyPaymentResource extends Resource
{
    protected static ?string $model = SupplyPayment::class;

    protected static ?string $navigationLabel = 'دفعات الملاك';

    protected static ?string $modelLabel = 'مالك توريد';

    protected static ?string $pluralModelLabel = 'دفعات الملاك';

    protected static ?string $recordTitleAttribute = 'payment_number';

    public static function form(Schema $schema): Schema
    {
        return SupplyPaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplyPaymentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        // TODO: Re-implement these as custom infolist sections instead of RelationManagers
        // The previous implementation used fake/dummy relationships which is incorrect
        // Data should be fetched via PaymentAssignmentService and SupplyPaymentService
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplyPayments::route('/'),
            'create' => Pages\CreateSupplyPayment::route('/create'),
            'view' => Pages\ViewSupplyPayment::route('/{record}'),
            'edit' => Pages\EditSupplyPayment::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'payment_number',
            'gross_amount',
            'commission_amount',
            'commission_rate',
            'maintenance_deduction',
            'other_deductions',
            'net_amount',
            'due_date',
            'paid_date',
            'bank_transfer_reference',
            'month_year',
            'notes',
            'propertyContract.contract_number',
            'propertyContract.property.name',
            'propertyContract.property.address',
            'owner.name',
            'owner.phone',
            'owner.email',
            'approver.name',
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['propertyContract.property', 'owner', 'approver']);
    }

    public static function getGlobalSearchResults(string $search): Collection
    {
        $search = trim($search);

        $normalizedSearch = str_replace(['أ', 'إ', 'آ'], 'ا', $search);
        $normalizedSearch = str_replace(['ة'], 'ه', $normalizedSearch);
        $normalizedSearch = str_replace(['ى'], 'ي', $normalizedSearch);

        $searchWithoutSpaces = str_replace(' ', '', $normalizedSearch);

        return static::getGlobalSearchEloquentQuery()
            ->where(function (Builder $query) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                $query->where('payment_number', 'LIKE', "%{$search}%")
                    ->orWhere('payment_number', 'LIKE', "%{$searchWithoutSpaces}%")
                    ->orWhere('bank_transfer_reference', 'LIKE', "%{$search}%")
                    ->orWhere('month_year', 'LIKE', "%{$search}%");

                $approvalOptions = [
                    'approved' => 'موافق',
                    'rejected' => 'غير موافق',
                ];

                foreach ($approvalOptions as $key => $label) {
                    if (stripos($label, $normalizedSearch) !== false || stripos($label, $search) !== false) {
                        $query->orWhere('approval_status', $key);
                    }
                }

                if (is_numeric($search)) {
                    $query->orWhere('gross_amount', 'LIKE', "%{$search}%")
                        ->orWhere('commission_amount', 'LIKE', "%{$search}%")
                        ->orWhere('commission_rate', 'LIKE', "%{$search}%")
                        ->orWhere('maintenance_deduction', 'LIKE', "%{$search}%")
                        ->orWhere('other_deductions', 'LIKE', "%{$search}%")
                        ->orWhere('net_amount', 'LIKE', "%{$search}%");
                }

                $query->orWhere('notes', 'LIKE', "%{$normalizedSearch}%");

                $query->orWhere('due_date', 'LIKE', "%{$search}%")
                    ->orWhere('paid_date', 'LIKE', "%{$search}%")
                    ->orWhere('approved_at', 'LIKE', "%{$search}%")
                    ->orWhere('created_at', 'LIKE', "%{$search}%");

                if (preg_match('/^\d{4}$/', $search)) {
                    $query->orWhereYear('due_date', $search)
                        ->orWhereYear('paid_date', $search)
                        ->orWhereYear('approved_at', $search)
                        ->orWhereYear('created_at', $search);
                }

                if (preg_match('/^\d{1,2}[-\/]\d{4}$/', $search)) {
                    $parts = preg_split('/[-\/]/', $search);
                    $month = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                    $year = $parts[1];
                    $query->orWhere(function ($q) use ($month, $year) {
                        $q->whereMonth('due_date', $month)->whereYear('due_date', $year);
                    })->orWhere(function ($q) use ($month, $year) {
                        $q->whereMonth('paid_date', $month)->whereYear('paid_date', $year);
                    })->orWhere(function ($q) use ($month, $year) {
                        $q->whereMonth('approved_at', $month)->whereYear('approved_at', $year);
                    })->orWhere(function ($q) use ($month, $year) {
                        $q->whereMonth('created_at', $month)->whereYear('created_at', $year);
                    });
                }

                if (preg_match('/^\d{4}[-\/]\d{1,2}$/', $search)) {
                    $parts = preg_split('/[-\/]/', $search);
                    $year = $parts[0];
                    $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                    $query->orWhere(function ($q) use ($month, $year) {
                        $q->whereMonth('due_date', $month)->whereYear('due_date', $year);
                    })->orWhere(function ($q) use ($month, $year) {
                        $q->whereMonth('paid_date', $month)->whereYear('paid_date', $year);
                    })->orWhere(function ($q) use ($month, $year) {
                        $q->whereMonth('approved_at', $month)->whereYear('approved_at', $year);
                    })->orWhere(function ($q) use ($month, $year) {
                        $q->whereMonth('created_at', $month)->whereYear('created_at', $year);
                    });
                }

                if (preg_match('/^\d{1,2}[-\/]\d{1,2}[-\/]\d{4}$/', $search)) {
                    $parts = preg_split('/[-\/]/', $search);
                    $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                    $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                    $year = $parts[2];
                    $dateStr = "$year-$month-$day";

                    $query->orWhereDate('due_date', $dateStr)
                        ->orWhereDate('paid_date', $dateStr)
                        ->orWhereDate('approved_at', $dateStr)
                        ->orWhereDate('created_at', $dateStr);
                }

                if (preg_match('/^\d{1,2}[-\/]\d{1,2}$/', $search)) {
                    $parts = preg_split('/[-\/]/', $search);
                    $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                    $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                    $currentYear = date('Y');

                    $query->orWhere(function ($q) use ($day, $month, $currentYear) {
                        $dateStr = "$currentYear-$month-$day";
                        $q->whereDate('due_date', $dateStr)
                            ->orWhereDate('paid_date', $dateStr)
                            ->orWhereDate('approved_at', $dateStr)
                            ->orWhereDate('created_at', $dateStr);
                    });

                    $query->orWhere(function ($q) use ($month, $currentYear) {
                        $q->whereMonth('due_date', $month)->whereYear('due_date', $currentYear);
                    })->orWhere(function ($q) use ($month, $currentYear) {
                        $q->whereMonth('paid_date', $month)->whereYear('paid_date', $currentYear);
                    });
                }

                $query->orWhereHas('propertyContract', function ($q) use ($search, $searchWithoutSpaces) {
                    $q->where('contract_number', 'LIKE', "%{$search}%")
                        ->orWhere('contract_number', 'LIKE', "%{$searchWithoutSpaces}%");

                    if (is_numeric($search)) {
                        $q->orWhere('commission_rate', 'LIKE', "%{$search}%")
                            ->orWhere('duration_months', $search);
                    }
                });

                $query->orWhereHas('propertyContract.property', function ($q) use ($normalizedSearch, $searchWithoutSpaces) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%")
                        ->orWhere('address', 'LIKE', "%{$normalizedSearch}%");
                });

                $query->orWhereHas('owner', function ($q) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$searchWithoutSpaces}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });

                $query->orWhereHas('approver', function ($q) use ($normalizedSearch, $searchWithoutSpaces) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%");
                });
            })
            ->limit(50)
            ->get()
            ->map(function ($record) {
                $contract = $record->propertyContract;
                $property = $contract?->property?->name ?? 'غير محدد';
                $owner = $record->owner?->name ?? 'غير محدد';

                $statusLabel = $record->supply_status_label;

                return new GlobalSearchResult(
                    title: $record->payment_number,
                    url: static::getUrl('edit', ['record' => $record]),
                    details: [
                        'العقار' => $property,
                        'المالك' => $owner,
                        'المبلغ الصافي' => number_format($record->net_amount, 2) . ' SAR',
                        'الحالة' => $statusLabel,
                        'الشهر' => $record->month_year,
                    ]
                );
            });
    }
}
