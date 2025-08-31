<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Builder;
use Filament\GlobalSearch\GlobalSearchResult;
use Illuminate\Support\Collection;
use App\Enums\UserType;
use App\Services\UserPermissions;
use Filament\Forms\Components\Select;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationLabel = 'الموظفين';

    protected static ?string $modelLabel = 'موظف';

    protected static ?string $pluralModelLabel = 'الموظفين';

    // صلاحيات الوصول للـ Resource
    public static function canViewAny(): bool
    {
        $userType = auth()->user()?->type;
        // فقط super_admin و admin و manager يمكنهم رؤية الموظفين
        return in_array($userType, ['super_admin', 'admin', 'manager']);
    }

    public static function canCreate(): bool
    {
        $userType = auth()->user()?->type;
        // فقط super_admin و admin يمكنهم إضافة موظفين
        return in_array($userType, ['super_admin', 'admin']);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $userType = auth()->user()?->type;
        // فقط super_admin و admin يمكنهم تعديل الموظفين
        return in_array($userType, ['super_admin', 'admin']);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        // فقط super_admin يمكنه حذف الموظفين
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
                            ->visible(fn () => auth()->user()->type === 'super_admin')
                            ->disabled(fn (string $operation, $record = null) => 
                                $operation === 'edit' && 
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
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->label('كلمة المرور')
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
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
                    ->formatStateUsing(fn ($state) => match($state) {
                        'employee' => 'موظف',
                        'admin' => 'مدير',
                        'super_admin' => 'مدير النظام',
                        default => $state
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
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
            ->filters([
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn ($record) => auth()->user()->can('update', $record))
                    ->authorize(fn ($record) => auth()->user()->can('update', $record)),
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
        // Check if user can perform global search
        if (!auth()->user()->can('global-search')) {
            return collect();
        }
        
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
        
        // Apply same permission filters as table
        $user = auth()->user();
        if ($user->type === 'admin') {
            $query->where('type', '!=', 'super_admin');
        } elseif ($user->type === 'employee') {
            $query->where('id', $user->id);
        } elseif ($user->type !== 'super_admin') {
            return collect();
        }
        
        return $query->where(function (Builder $query) use ($normalizedSearch, $searchWithoutSpaces, $searchWithSpaces, $search) {
            // البحث العادي
            $query->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('secondary_phone', 'LIKE', "%{$search}%")
                  // البحث بدون همزات
                  ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ء', ''), 'ؤ', 'و'), 'ئ', 'ي') LIKE ?", ["%{$normalizedSearch}%"])
                  // البحث بدون مسافات
                  ->orWhereRaw("REPLACE(name, ' ', '') LIKE ?", ["%{$searchWithoutSpaces}%"])
                  // البحث مع تجاهل المسافات في الكلمة المبحوث عنها
                  ->orWhere('name', 'LIKE', "%{$searchWithSpaces}%")
                  // البحث في النوع
                  ->orWhere(function($q) use ($search) {
                      $searchLower = mb_strtolower($search, 'UTF-8');
                      if (str_contains('موظف', $searchLower)) {
                          $q->where('type', 'employee');
                      } elseif (str_contains('مدير النظام', $searchLower)) {
                          $q->where('type', 'super_admin');
                      } elseif (str_contains('مدير', $searchLower)) {
                          $q->where('type', 'admin');
                      }
                  })
                  // البحث بالتواريخ - تاريخ الإنشاء
                  ->orWhereRaw("DATE_FORMAT(created_at, '%Y-%m-%d') LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("DATE_FORMAT(created_at, '%d-%m-%Y') LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("DATE_FORMAT(created_at, '%Y/%m/%d') LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("DATE_FORMAT(created_at, '%d/%m/%Y') LIKE ?", ["%{$search}%"]);
        })
        ->limit(50)
        ->get()
        ->map(function ($record) {
            $typeLabel = match($record->type) {
                'employee' => 'موظف',
                'admin' => 'مدير',
                'super_admin' => 'مدير النظام',
                default => $record->type
            };
            
            return new \Filament\GlobalSearch\GlobalSearchResult(
                title: $record->name,
                url: static::getUrl('edit', ['record' => $record]),
                details: [
                    'النوع' => $typeLabel,
                    'البريد الإلكتروني' => $record->email ?? 'غير محدد',
                    'الهاتف' => $record->phone ?? 'غير محدد',
                    'الهاتف الثاني' => $record->secondary_phone ?? 'غير محدد',
                    'تاريخ الإنشاء' => $record->created_at?->format('Y-m-d') ?? 'غير محدد',
                ],
                actions: []
            );
        });
    }
}