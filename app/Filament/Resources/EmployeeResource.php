<?php

namespace App\Filament\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\EmployeeResource\Pages\ListEmployees;
use App\Filament\Resources\EmployeeResource\Pages\CreateEmployee;
use App\Filament\Resources\EmployeeResource\Pages\EditEmployee;
use Illuminate\Support\Collection;
use Filament\GlobalSearch\GlobalSearchResult;
use App\Enums\UserType;
use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationLabel = 'الموظفين';

    protected static ?string $modelLabel = 'موظف';

    protected static ?string $pluralModelLabel = 'الموظفين';

    public static function canViewAny(): bool
    {
        $userType = auth()->user()?->type;

        return in_array($userType, ['super_admin', 'admin', 'manager']);
    }

    public static function canCreate(): bool
    {
        $userType = auth()->user()?->type;

        return in_array($userType, ['super_admin', 'admin']);
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
        return $schema
            ->components([
                Section::make('معلومات عامة')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->label('الاسم الكامل')
                            ->maxLength(255)
                            ->columnSpan('full'),

                        TextInput::make('phone')
                            ->tel()
                            ->regex('/^[0-9]+$/')
                            ->required()
                            ->unique('users', 'phone', ignoreRecord: true)
                            // ->unique('users', 'phone', ignoreRecord: true, modifyRuleUsing: function ($rule, $get) {
                            //     return $rule->where('type', $get('type'));
                            // })
                            ->label('الهاتف الأول')
                            ->maxLength(20)
                            ->columnSpan(6),

                        TextInput::make('secondary_phone')
                            ->tel()
                            ->regex('/^[0-9]+$/')
                            ->label('الهاتف الثاني')
                            ->maxLength(20)
                            ->columnSpan(6),

                        FileUpload::make('identity_file')
                            ->label('ملف الهوية')
                            ->directory('employee--identity-file')
                            ->columnSpan('full'),
                    ])
                    ->columns(12)
                    ->columnSpan('full'),

                Section::make('معلومات الدخول')
                    ->schema([
                        Select::make('type')
                            ->label('نوع المستخدم')
                            ->options([
                                UserType::SUPER_ADMIN->value => UserType::SUPER_ADMIN->label(),
                                UserType::ADMIN->value => UserType::ADMIN->label(),
                                UserType::EMPLOYEE->value => UserType::EMPLOYEE->label(),
                            ])
                            ->default(UserType::EMPLOYEE->value)
                            ->required()
                            ->visible(fn() => auth()->user()->type === 'super_admin')
                            ->disabled(
                                fn(string $operation, $record = null) => $operation === 'edit' &&
                                    $record &&
                                    auth()->user()->type === 'admin' &&
                                    in_array($record->type, ['super_admin', 'admin'])
                            )
                            ->columnSpan(12),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->label('البريد الإلكتروني')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(6),

                        TextInput::make('password')
                            ->password()
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->label('كلمة المرور')
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->dehydrated(fn($state) => filled($state))
                            ->maxLength(255)
                            ->columnSpan(6),
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

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('الهاتف الأول')
                    ->searchable(),

                TextColumn::make('secondary_phone')
                    ->label('الهاتف الثاني')
                    ->searchable(),

                TextColumn::make('type')
                    ->label('النوع')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'employee' => 'موظف',
                        'admin' => 'مدير',
                        'super_admin' => 'مدير النظام',
                        default => $state
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        'employee' => 'success',
                        default => 'secondary'
                    }),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn($record) => static::canEdit($record)),
            ])
            ->toolbarActions([])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();

                // Super admin can see all
                if ($user->type === 'super_admin') {
                    return $query;
                }

                // Admin can see all employees but no super admins
                if ($user->type === 'admin') {
                    return $query->where('type', '!=', 'super_admin');
                }

                // Employees can only see themselves
                if ($user->type === 'employee') {
                    return $query->where('id', $user->id);
                }

                // Others see nothing
                return $query->whereRaw('1 = 0');
            });
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
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'edit' => EditEmployee::route('/{record}/edit'),
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
