<?php

namespace App\Filament\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Concerns\HasFormComponents;
use Filament\Tables\Columns\ImageColumn;
use App\Filament\Resources\OwnerResource\Pages\ListOwners;
use App\Filament\Resources\OwnerResource\Pages\CreateOwner;
use App\Filament\Resources\OwnerResource\Pages\EditOwner;
use App\Filament\Resources\OwnerResource\Pages\ViewOwner;
use Filament\GlobalSearch\GlobalSearchResult;
use App\Filament\Resources\OwnerResource\Pages;
use App\Models\CollectionPayment;
use App\Models\Owner;
use App\Models\SupplyPayment;
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
    use HasFormComponents;
    protected static ?string $model = Owner::class;

    protected static ?string $navigationLabel = 'الملاك';

    protected static ?string $modelLabel = 'مالك';

    protected static ?string $pluralModelLabel = 'الملاك';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        $userType = auth()->user()?->type;

        return ! in_array($userType, ['owner', 'tenant']);
    }

    public static function canCreate(): bool
    {
        $userType = auth()->user()?->type;

        return in_array($userType, ['super_admin', 'admin', 'manager']);
    }

    public static function canEdit(Model $record): bool
    {
        $userType = auth()->user()?->type;

        return in_array($userType, ['super_admin', 'admin', 'manager']);
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
        return $schema
            ->components([
                Section::make('معلومات عامة')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->label('الاسم الكامل')
                            ->maxLength(255)
                            ->columnSpan('full'),

                        static::phoneInput('phone', 'الهاتف الأول', true, 'owner')
                            ->required(),

                        static::secondaryPhoneInput('secondary_phone', 'الهاتف الثاني'),

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

                ImageColumn::make('identity_file')
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
            'index' => ListOwners::route('/'),
            'create' => CreateOwner::route('/create'),
            'edit' => EditOwner::route('/{record}/edit'),
            'view' => ViewOwner::route('/{record}'),
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
        // Normalize Arabic characters
        $normalizedSearch = str_replace(
            ['أ', 'إ', 'آ', 'ء', 'ؤ', 'ئ'],
            ['ا', 'ا', 'ا', '', 'و', 'ي'],
            $search
        );

        $searchWithoutSpaces = str_replace(' ', '', $normalizedSearch);
        $searchWithSpaces = str_replace(' ', '%', $normalizedSearch);

        // Arabic to English status mapping
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

        $searchLower = mb_strtolower($search, 'UTF-8');
        $englishStatus = null;
        foreach ($statusMap as $arabic => $english) {
            if (str_contains(mb_strtolower($arabic, 'UTF-8'), $searchLower)) {
                $englishStatus = $english;
                break;
            }
        }

        $query = static::getModel()::query();

        // Search by supply payment status
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
            // Standard search
            $query->where(function (Builder $query) use ($normalizedSearch, $searchWithoutSpaces, $searchWithSpaces, $search) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('secondary_phone', 'LIKE', "%{$search}%")
                    // Search without hamza
                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ء', ''), 'ؤ', 'و'), 'ئ', 'ي') LIKE ?", ["%{$normalizedSearch}%"])
                    // Search without spaces
                    ->orWhereRaw("REPLACE(name, ' ', '') LIKE ?", ["%{$searchWithoutSpaces}%"])
                    ->orWhere('name', 'LIKE', "%{$searchWithSpaces}%")
                    // Search by date formats
                    ->orWhereRaw("DATE_FORMAT(created_at, '%Y-%m-%d') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("DATE_FORMAT(created_at, '%d-%m-%Y') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("DATE_FORMAT(created_at, '%Y/%m/%d') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("DATE_FORMAT(created_at, '%d/%m/%Y') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("DATE_FORMAT(deleted_at, '%Y-%m-%d') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("DATE_FORMAT(deleted_at, '%d-%m-%Y') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("DATE_FORMAT(deleted_at, '%Y/%m/%d') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("DATE_FORMAT(deleted_at, '%d/%m/%Y') LIKE ?", ["%{$search}%"]);
            });
        }

        return $query->limit(50)
            ->get()
            ->map(function ($record) use ($englishStatus) {
                $details = [
                    'الهاتف' => $record->phone ?? 'غير محدد',
                    'الهاتف الثاني' => $record->secondary_phone ?? 'غير محدد',
                    'تاريخ الإنشاء' => $record->created_at?->format('Y-m-d') ?? 'غير محدد',
                    'تاريخ الحذف' => $record->deleted_at?->format('Y-m-d') ?? 'نشط',
                ];

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
                        "مدفوعات {$statusLabel}" => $count . ' دفعة',
                    ], $details);
                }

                return new GlobalSearchResult(
                    title: $record->name,
                    url: static::getUrl('edit', ['record' => $record]),
                    details: $details,
                    actions: static::getGlobalSearchResultActions($record)
                );
            });
    }

    public static function getGlobalSearchResultActions(Model $record): array
    {
        return [
            // Action::make('view_report')
            //     ->label('View Report')
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
        $owner->load(['properties', 'properties.units']);

        $propertiesCount = $owner->properties->count();
        $totalUnits = $owner->properties->sum(function ($property) {
            return $property->units->count();
        });
        $occupiedUnits = $owner->properties->sum(function ($property) {
            return $property->units->where('status', 'occupied')->count();
        });
        $vacantUnits = $totalUnits - $occupiedUnits;
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100) : 0;

        // Financial statistics - last 12 months
        $dateFrom = now()->subYear();
        $dateTo = now();

        // Use already-loaded property IDs instead of subquery to avoid cardinality issues
        $propertyIds = $owner->properties->pluck('id')->toArray();
        $totalCollection = CollectionPayment::whereIn('property_id', $propertyIds)
            ->collectedPayments()
            ->whereBetween('paid_date', [$dateFrom, $dateTo])
            ->sum('total_amount');

        // Management fees (10% default)
        $managementFees = $totalCollection * 0.10;

        $ownerDue = $totalCollection - $managementFees;

        $paidToOwner = SupplyPayment::where('owner_id', $owner->id)
            ->collected()
            ->whereBetween('paid_date', [$dateFrom, $dateTo])
            ->sum('net_amount');

        $pendingBalance = $ownerDue - $paidToOwner;

        $totalOperations = SupplyPayment::where('owner_id', $owner->id)->count();
        $completedOperations = SupplyPayment::where('owner_id', $owner->id)
            ->collected()
            ->count();

        $lastPayment = SupplyPayment::where('owner_id', $owner->id)
            ->collected()
            ->latest('paid_date')
            ->first();

        $nextPayment = SupplyPayment::where('owner_id', $owner->id)
            ->pending()
            ->oldest('created_at')
            ->first();

        $averageMonthlyIncome = $paidToOwner / 12;
        $transferRate = $ownerDue > 0 ? round(($paidToOwner / $ownerDue) * 100) : 0;

        return [
            'owner_name' => $owner->name,
            'owner_phone' => $owner->phone,
            'owner_secondary_phone' => $owner->secondary_phone,
            'owner_email' => $owner->email,
            'identity_file' => $owner->identity_file,
            'created_at' => $owner->created_at,

            'properties_count' => $propertiesCount,
            'total_units' => $totalUnits,
            'occupied_units' => $occupiedUnits,
            'vacant_units' => $vacantUnits,
            'occupancy_rate' => $occupancyRate,
            'properties_list' => $owner->properties->pluck('name')->toArray(),

            'total_collection' => $totalCollection,
            'management_fees' => $managementFees,
            'owner_due' => $ownerDue,
            'paid_to_owner' => $paidToOwner,
            'pending_balance' => $pendingBalance,
            'transfer_rate' => $transferRate,
            'average_monthly_income' => $averageMonthlyIncome,

            'total_operations' => $totalOperations,
            'completed_operations' => $completedOperations,
            'completion_rate' => $totalOperations > 0 ? round(($completedOperations / $totalOperations) * 100) : 0,

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

            'is_active' => $propertiesCount > 0 && $occupancyRate > 0,
            'performance_level' => $transferRate >= 80 ? 'excellent' : ($transferRate >= 50 ? 'good' : 'needs_attention'),
        ];
    }
}
