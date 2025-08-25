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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\TextInput as FilterTextInput;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Filament\GlobalSearch\GlobalSearchResult;
use Illuminate\Support\Collection;

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
                EditAction::make(),
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

}