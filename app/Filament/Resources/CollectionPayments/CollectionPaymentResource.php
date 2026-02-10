<?php

namespace App\Filament\Resources\CollectionPayments;

use App\Enums\PaymentStatus;
use App\Filament\Resources\CollectionPayments\Schemas\CollectionPaymentForm;
use App\Filament\Resources\CollectionPayments\Tables\CollectionPaymentsTable;
use App\Models\CollectionPayment;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use ValueError;

class CollectionPaymentResource extends Resource
{
    protected static ?string $model = CollectionPayment::class;

    protected static ?string $navigationLabel = 'دفعات المستأجرين';

    protected static ?string $modelLabel = 'دفعة مستأجر';

    protected static ?string $pluralModelLabel = 'دفعات المستأجرين';

    protected static ?string $recordTitleAttribute = 'payment_number';

    public static function form(Schema $schema): Schema
    {
        return CollectionPaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CollectionPaymentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCollectionPayments::route('/'),
            'create' => Pages\CreateCollectionPayment::route('/create'),
            'view' => Pages\ViewCollectionPayment::route('/{record}'),
            'edit' => Pages\EditCollectionPayment::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'payment_number',
            'amount',
            'collection_date',
            'due_date_start',
            'due_date_end',
            'paid_date',
            'delay_duration',
            'delay_reason',
            'late_payment_notes',
            'payment_reference',
            'receipt_number',
            'month_year',
            'tenant.name',
            'tenant.phone',
            'tenant.email',
            'unit.name',
            'property.name',
            'property.address',
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['tenant', 'unit', 'property']);
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
                    ->orWhere('payment_reference', 'LIKE', "%{$search}%")
                    ->orWhere('receipt_number', 'LIKE', "%{$search}%")
                    ->orWhere('month_year', 'LIKE', "%{$search}%");

                $statusSearch = PaymentStatus::options();
                foreach ($statusSearch as $key => $label) {
                    if (stripos($label, $normalizedSearch) !== false || stripos($label, $search) !== false) {
                        try {
                            $status = PaymentStatus::from($key);
                            $query->orWhere(function ($statusQuery) use ($status) {
                                (new CollectionPayment)->scopeByStatus($statusQuery, $status);
                            });
                        } catch (ValueError $e) {
                            // Skip invalid values
                        }
                    }
                }

                if (is_numeric($search)) {
                    $query->orWhere('amount', 'LIKE', "%{$search}%")
                        ->orWhere('amount', $search);
                }

                $query->orWhere('delay_reason', 'LIKE', "%{$normalizedSearch}%")
                    ->orWhere('late_payment_notes', 'LIKE', "%{$normalizedSearch}%");

                if (is_numeric($search)) {
                    $query->orWhere('delay_duration', $search)
                        ->orWhere('delay_duration', 'LIKE', "%{$search}%");
                }

                $query->orWhere('collection_date', 'LIKE', "%{$search}%")
                    ->orWhere('due_date_start', 'LIKE', "%{$search}%")
                    ->orWhere('due_date_end', 'LIKE', "%{$search}%")
                    ->orWhere('paid_date', 'LIKE', "%{$search}%")
                    ->orWhere('created_at', 'LIKE', "%{$search}%");

                if (preg_match('/^\d{4}$/', $search)) {
                    $query->orWhereYear('collection_date', $search)
                        ->orWhereYear('due_date_start', $search)
                        ->orWhereYear('due_date_end', $search)
                        ->orWhereYear('paid_date', $search)
                        ->orWhereYear('created_at', $search);
                }

                if (preg_match('/^\d{1,2}[-\/]\d{4}$/', $search)) {
                    $parts = preg_split('/[-\/]/', $search);
                    $month = $parts[0];
                    $year = $parts[1];
                    $query->orWhereMonth('collection_date', $month)->whereYear('collection_date', $year)
                        ->orWhereMonth('due_date_start', $month)->whereYear('due_date_start', $year)
                        ->orWhereMonth('due_date_end', $month)->whereYear('due_date_end', $year);
                }

                $query->orWhereHas('tenant', function ($q) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                    $q->where(function ($subQuery) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                        $subQuery->where('name', 'LIKE', "%{$normalizedSearch}%")
                            ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%")
                            ->orWhere('phone', 'LIKE', "%{$search}%")
                            ->orWhere('phone', 'LIKE', "%{$searchWithoutSpaces}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    });
                });

                $query->orWhereHas('unit', function ($q) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%");

                    if (is_numeric($search)) {
                        $q->orWhere('floor_number', $search)
                            ->orWhere('rooms_count', $search);
                    }
                });

                $query->orWhereHas('property', function ($q) use ($normalizedSearch, $searchWithoutSpaces) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%")
                        ->orWhere('address', 'LIKE', "%{$normalizedSearch}%");
                });
            })
            ->limit(50)
            ->get()
            ->map(function ($record) {
                $tenant = $record->tenant?->name ?? 'غير محدد';
                $unit = $record->unit?->name ?? 'غير محدد';
                $property = $record->property?->name ?? 'غير محدد';

                return new GlobalSearchResult(
                    title: $record->payment_number,
                    url: static::getUrl('edit', ['record' => $record]),
                    details: [
                        'المستأجر' => $tenant,
                        'الوحدة' => $unit,
                        'العقار' => $property,
                        'المبلغ' => number_format($record->amount, 2).' SAR',
                        'الحالة' => $record->payment_status_label,
                    ]
                );
            });
    }
}
