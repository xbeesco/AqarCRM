<?php

namespace App\Filament\Resources\UnitContracts;

use App\Filament\Resources\UnitContracts\Schemas\UnitContractForm;
use App\Filament\Resources\UnitContracts\Tables\UnitContractsTable;
use App\Models\UnitContract;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UnitContractResource extends Resource
{
    protected static ?string $model = UnitContract::class;

    protected static ?string $navigationLabel = 'عقود الوحدات';

    protected static ?string $modelLabel = 'عقد وحدة';

    protected static ?string $pluralModelLabel = 'عقود الوحدات';

    public static function form(Schema $schema): Schema
    {
        return UnitContractForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnitContractsTable::configure($table);
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
            'index' => Pages\ListUnitContracts::route('/'),
            'create' => Pages\CreateUnitContract::route('/create'),
            'view' => Pages\ViewUnitContracts::route('/{record}'),
            'edit' => Pages\EditUnitContract::route('/{record}/edit'), // Only accessible by super_admin
            'reschedule' => Pages\ReschedulePayments::route('/{record}/reschedule'), // Only accessible by super_admin
            'renew' => Pages\RenewContract::route('/{record}/renew'),
        ];
    }

    /**
     * Only super_admin can edit contracts
     */
    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return $user && $user->type === 'super_admin';
    }

    /**
     * Only super_admin can delete contracts
     */
    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();

        return $user && $user->type === 'super_admin';
    }

    /**
     * Only admins and employees can create contracts
     */
    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->type, ['super_admin', 'admin', 'employee']);
    }

    /**
     * Filter records based on user type
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user) {
            switch ($user->type) {
                case 'owner':
                    // Owners see contracts for their properties only
                    return $query->whereHas('property', function ($q) use ($user) {
                        $q->where('owner_id', $user->id);
                    });

                case 'tenant':
                    // Tenants see only their own contracts
                    return $query->where('tenant_id', $user->id);
            }
        }

        return $query;
    }
}
