<?php

namespace App\Filament\Resources\Employees;

use App\Filament\Concerns\HasFormComponents;
use App\Filament\Resources\Employees\Schemas\EmployeeForm;
use App\Filament\Resources\Employees\Tables\EmployeesTable;
use App\Models\Employee;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class EmployeeResource extends Resource
{
    use HasFormComponents;

    protected static ?string $model = Employee::class;

    protected static ?string $navigationLabel = 'الموظفين';

    protected static ?string $modelLabel = 'موظف';

    protected static ?string $pluralModelLabel = 'الموظفين';

    public static function canViewAny(): bool
    {
        $userType = auth()->user()?->type;

        return in_array($userType, ['super_admin', 'admin', 'employee']);
    }

    public static function canCreate(): bool
    {
        $userType = auth()->user()?->type;

        return in_array($userType, ['super_admin', 'admin', 'employee']);
    }

    public static function canEdit(Model $record): bool
    {
        $userType = auth()->user()?->type;

        return in_array($userType, ['super_admin', 'admin']);
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
        return EmployeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeesTable::configure($table);
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone', 'secondary_phone'];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery();
    }

    public static function getGlobalSearchResults(string $search): Collection
    {
        if (! auth()->user()->can('global-search')) {
            return collect();
        }

        // Normalize Arabic characters for search
        $normalizedSearch = str_replace(
            ['أ', 'إ', 'آ', 'ء', 'ؤ', 'ئ'],
            ['ا', 'ا', 'ا', '', 'و', 'ي'],
            $search
        );

        $searchWithoutSpaces = str_replace(' ', '', $normalizedSearch);
        $searchWithSpaces = str_replace(' ', '%', $normalizedSearch);

        $query = static::getModel()::query();

        // Apply permission filters
        $user = auth()->user();
        if ($user->type === 'admin') {
            $query->where('type', '!=', 'super_admin');
        } elseif ($user->type === 'employee') {
            $query->where('id', $user->id);
        } elseif ($user->type !== 'super_admin') {
            return collect();
        }

        return $query->where(function (Builder $query) use ($normalizedSearch, $searchWithoutSpaces, $searchWithSpaces, $search) {
            $query->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%")
                ->orWhere('phone', 'LIKE', "%{$search}%")
                ->orWhere('secondary_phone', 'LIKE', "%{$search}%")
                ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ء', ''), 'ؤ', 'و'), 'ئ', 'ي') LIKE ?", ["%{$normalizedSearch}%"])
                ->orWhereRaw("REPLACE(name, ' ', '') LIKE ?", ["%{$searchWithoutSpaces}%"])
                ->orWhere('name', 'LIKE', "%{$searchWithSpaces}%")
                ->orWhere(function ($q) use ($search) {
                    $searchLower = mb_strtolower($search, 'UTF-8');
                    if (str_contains('موظف', $searchLower)) {
                        $q->where('type', 'employee');
                    } elseif (str_contains('مدير النظام', $searchLower)) {
                        $q->where('type', 'super_admin');
                    } elseif (str_contains('مدير', $searchLower)) {
                        $q->where('type', 'admin');
                    }
                })
                ->orWhereRaw("DATE_FORMAT(created_at, '%Y-%m-%d') LIKE ?", ["%{$search}%"])
                ->orWhereRaw("DATE_FORMAT(created_at, '%d-%m-%Y') LIKE ?", ["%{$search}%"])
                ->orWhereRaw("DATE_FORMAT(created_at, '%Y/%m/%d') LIKE ?", ["%{$search}%"])
                ->orWhereRaw("DATE_FORMAT(created_at, '%d/%m/%Y') LIKE ?", ["%{$search}%"]);
        })
            ->limit(50)
            ->get()
            ->map(function ($record) {
                $typeLabel = match ($record->type) {
                    'employee' => 'موظف',
                    'admin' => 'مدير',
                    'super_admin' => 'مدير النظام',
                    default => $record->type
                };

                return new GlobalSearchResult(
                    title: $record->name,
                    url: static::getUrl('edit', ['record' => $record]),
                    details: [
                        'النوع' => $typeLabel,
                        'البريد الإلكتروني' => $record->email ?? 'N/A',
                        'الهاتف' => $record->phone ?? 'N/A',
                        'الهاتف الثاني' => $record->secondary_phone ?? 'N/A',
                        'تاريخ الإنشاء' => $record->created_at?->format('Y-m-d') ?? 'N/A',
                    ],
                    actions: []
                );
            });
    }
}
