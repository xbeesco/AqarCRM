<?php

namespace App\Filament\Resources\PropertyStatuses;

use Str;
use Filament\GlobalSearch\GlobalSearchResult;
use App\Filament\Resources\PropertyStatuses\Pages\ManagePropertyStatuses;
use App\Models\PropertyStatus;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PropertyStatusResource extends Resource
{
    protected static ?string $model = PropertyStatus::class;

    protected static ?string $label = 'حالة عقار';

    protected static ?string $pluralLabel = 'حالات العقارات';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('الاسم')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
            ])
            ->searchable(false)
            ->filters([])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->toolbarActions([])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePropertyStatuses::route('/'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
            'slug',
            'created_at',
            'updated_at'
        ];
    }

    public static function getGlobalSearchResults(string $search): Collection
    {
        $normalizedSearch = str_replace(
            ['أ', 'إ', 'آ', 'ء', 'ؤ', 'ئ'],
            ['ا', 'ا', 'ا', '', 'و', 'ي'],
            $search
        );

        return static::getModel()::query()
            ->where(function ($query) use ($search, $normalizedSearch) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('slug', 'LIKE', "%{$search}%")
                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, 'أ', 'ا'), 'إ', 'ا'), 'آ', 'ا'), 'ء', ''), 'ؤ', 'و'), 'ئ', 'ي') LIKE ?", ["%{$normalizedSearch}%"]);
            })
            ->limit(50)
            ->get()
            ->map(function ($record) {
                return new GlobalSearchResult(
                    title: $record->name,
                    url: static::getUrl('index'),
                    details: [
                        //
                    ],
                    actions: []
                );
            });
    }
}
