<?php

namespace App\Filament\Resources\PropertyContracts;

use App\Filament\Resources\PropertyContracts\Schemas\PropertyContractForm;
use App\Filament\Resources\PropertyContracts\Tables\PropertyContractsTable;
use App\Models\PropertyContract;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PropertyContractResource extends Resource
{
    protected static ?string $model = PropertyContract::class;

    protected static ?string $navigationLabel = 'عقود العقارات';

    protected static ?string $modelLabel = 'عقد العقار';

    protected static ?string $pluralModelLabel = 'عقود العقارات';

    public static function form(Schema $schema): Schema
    {
        return PropertyContractForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PropertyContractsTable::configure($table);
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
            'index' => Pages\ListPropertyContracts::route('/'),
            'create' => Pages\CreatePropertyContract::route('/create'),
            'view' => Pages\ViewPropertyContract::route('/{record}'),
            'edit' => Pages\EditPropertyContract::route('/{record}/edit'),
            'reschedule' => Pages\ReschedulePayments::route('/{record}/reschedule'),
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
     * Only admins can create contracts
     */
    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->type, ['super_admin', 'admin']);
    }

    /**
     * Filter records based on user type
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->type === 'owner') {
            // Owners can only see their own contracts
            return $query->where('owner_id', $user->id);
        }

        return $query;
    }
}
