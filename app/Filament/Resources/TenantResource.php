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
use Filament\Actions\Action;
class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationLabel = 'المستأجرين';

    protected static ?string $modelLabel = 'مستأجر';

    protected static ?string $pluralModelLabel = 'المستأجرين';

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
                            ->label('رقم الهاتف الأول')
                            ->maxLength(20)
                            ->columnSpan('full'),

                        TextInput::make('secondary_phone')
                            ->numeric()
                            ->label('رقم الهاتف الثاني')
                            ->maxLength(20)
                            ->columnSpan('full'),

                        FileUpload::make('identity_file')
                            ->label('ملف الهوية')
                            ->directory('tenants/identities')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(5120)
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->columnSpan('full'),
                    ])
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query;
            })
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
            ->filters([
                Filter::make('name')
                    ->form([
                        FilterTextInput::make('name')
                            ->label('الاسم')
                            ->placeholder('البحث بالاسم')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['name'],
                            fn (Builder $query, $name): Builder => $query->where('name', 'like', "%{$name}%")
                        );
                    })
                    ->columnSpan(4),
                    
                SelectFilter::make('property')
                    ->label('العقار')
                    ->relationship('currentProperty', 'name')
                    ->searchable()
                    ->columnSpan(4),
                    
                Filter::make('unit')
                    ->form([
                        Select::make('unit_id')
                            ->label('الوحدة')
                            ->options(function (callable $get) {
                                $propertyId = $get('../../property');
                                if ($propertyId) {
                                    return \App\Models\Unit::where('property_id', $propertyId)
                                        ->with('property')
                                        ->get()
                                        ->mapWithKeys(function ($unit) {
                                            return [$unit->id => $unit->property->name . ' - وحدة ' . $unit->unit_number];
                                        });
                                }
                                return \App\Models\Unit::with('property')
                                    ->get()
                                    ->mapWithKeys(function ($unit) {
                                        return [$unit->id => $unit->property->name . ' - وحدة ' . $unit->unit_number];
                                    });
                            })
                            ->searchable()
                            ->live()
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['unit_id'],
                            function (Builder $query, $unitId): Builder {
                                return $query->whereHas('rentalContracts', function ($q) use ($unitId) {
                                    $q->whereHas('unit', function ($unitQuery) use ($unitId) {
                                        $unitQuery->where('id', $unitId);
                                    });
                                });
                            }
                        );
                    })
                    ->columnSpan(4),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(12)
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([])
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

    public static function getGlobalSearchResults(string $search): array
    {
        return static::getModel()::query()
            ->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('national_id', 'like', "%{$search}%");
            })
            ->limit(5)
            ->get()
            ->map(function ($record) {
                return GlobalSearchResult::make()
                    ->title($record->name)
                    ->details([
                        'البريد الإلكتروني: ' . ($record->email ?? 'غير محدد'),
                        'الهاتف: ' . ($record->phone ?? 'غير محدد'),
                        'الرقم المدني: ' . ($record->national_id ?? 'غير محدد'),
                    ])
                    ->actions([
                        Action::make('edit')
                            ->label('تحرير')
                            ->icon('heroicon-s-pencil')
                            ->url(static::getUrl('edit', ['record' => $record])),
                    ])
                    ->url(static::getUrl('view', ['record' => $record]));
            })
            ->toArray();
    }
}