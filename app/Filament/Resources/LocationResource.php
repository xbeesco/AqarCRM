<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Models\Location;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
class LocationResource extends Resource
{
    protected static ?string $model = Location::class;
    
    protected static ?string $navigationLabel = 'المواقع';
    
    protected static ?string $pluralModelLabel = 'المواقع';
    
    protected static ?string $modelLabel = 'موقع';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('معلومات الموقع')
                    ->schema([
                        Select::make('level')
                            ->label('المستوى')
                            ->options(Location::getLevelOptions())
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('parent_id', null)),
                            
                        Select::make('parent_id')
                            ->label('الموقع الأب')
                            ->options(function (callable $get, $record) {
                                $level = $get('level') ?: $record?->level;
                                if (!$level || $level <= 1) {
                                    return [];
                                }
                                return Location::getParentOptions($level);
                            })
                            ->visible(fn (callable $get, $record) => ($get('level') ?: $record?->level) > 1)
                            ->searchable()
                            ->preload()
                            ->reactive(),
                            
                        TextInput::make('name')
                            ->label('الاسم')
                            ->required()
                            ->maxLength(255),
                            
                        TextInput::make('code')
                            ->label('الكود')
                            ->maxLength(50),
                            
                        TextInput::make('postal_code')
                            ->label('الرمز البريدي')
                            ->maxLength(20),
                            
                        TextInput::make('coordinates')
                            ->label('الإحداثيات')
                            ->placeholder('lat,lng')
                            ->maxLength(100),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true),
                    ])
                    ->columnSpanFull()
                    ->columns(4)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الموقع')
                    ->formatStateUsing(function (string $state, Location $record): string {
                        // Create hierarchical indentation with enhanced visual tree structure
                        $treeStructure = '';
                        $badges = [
                            1 => '<span class="fi-color fi-color-success fi-text-color-700 dark:fi-text-color-300 fi-badge fi-size-sm"> منطقة </span>&nbsp;',
                            2 => '<span class="fi-color fi-color-warning fi-text-color-700 dark:fi-text-color-300 fi-badge fi-size-sm"> مدينة </span>&nbsp;',
                            3 => '<span class="fi-color fi-color-info fi-text-color-700 dark:fi-text-color-300 fi-badge fi-size-sm"> مركز </span>&nbsp;',
                            4 => '<span class="fi-color fi-color-gray fi-text-color-700 dark:fi-text-color-300 fi-badge fi-size-sm"> حي </span>&nbsp;',
                        ];
                        // Build tree indentation based on level with better styling
                        if ($record->level > 1) {
                            $treeStructure = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $record->level - 1);
                        }
                                                    $treeStructure .=  $badges[$record->level] .  '&nbsp;';

                        // Enhanced icons with colors for different levels
                        // $icon = match ($record->level) {
                        //     1 => '<span class="text-green-600">🌍</span>',  // منطقة
                        //     2 => '<span class="text-blue-600">🏙️</span>',   // مدينة  
                        //     3 => '<span class="text-orange-600">🏢</span>',  // مركز
                        //     4 => '<span class="text-purple-600">🏘️</span>', // حي
                        //     default => '<span class="text-gray-600">📍</span>'
                        // };
                        
                        // Display name with better styling
                        $displayName = '<span class="font-medium text-gray-900">' . $state . '</span>';
                        
                        // Add breadcrumb path for deeper levels
                        $breadcrumb = '';
                        if ($record->level > 1 && $record->parent) {
                            $path = collect();
                            $current = $record->parent;
                            while ($current) {
                                $path->prepend($current->name);
                                $current = $current->parent;
                            }
                            if ($path->isNotEmpty()) {
                                $breadcrumb = '<div class="text-xs text-gray-400 mt-1">' . 
                                             $path->join(' › ') . 
                                             '</div>';
                            }
                        }
                        return '<div class="py-1">' . $treeStructure . $displayName . '</div>';
                    })
                    ->html()
                    ->wrap(),
                    
                TextColumn::make('code')
                    ->label('الكود')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),
                    
                BadgeColumn::make('is_active')
                    ->label('الحالة')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'نشط' : 'غير نشط')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                    
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            //     SelectFilter::make('level')
            //         ->label('المستوى')
            //         ->options([
            //             1 => '🌍 منطقة',
            //             2 => '🏙️ مدينة', 
            //             3 => '🏢 مركز',
            //             4 => '🏘️ حي'
            //         ]),
                    
            //     SelectFilter::make('parent_id')
            //         ->label('الموقع الأب')
            //         ->options(function (): array {
            //             return Location::whereIn('level', [1, 2, 3])
            //                 ->orderBy('path')
            //                 ->get()
            //                 ->mapWithKeys(function (Location $location) {
            //                     $prefix = str_repeat('──', $location->level - 1);
            //                     $icon = match ($location->level) {
            //                         1 => '🌍',
            //                         2 => '🏙️',
            //                         3 => '🏢',
            //                         default => '📍'
            //                     };
            //                     return [$location->id => $prefix . $icon . ' ' . $location->name];
            //                 })
            //                 ->toArray();
            //         })
            //         ->searchable(),
                    
            //     SelectFilter::make('is_active')
            //         ->label('الحالة')
            //         ->options([
            //             1 => '✅ نشط',
            //             0 => '❌ غير نشط',
            //         ]),
            // ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('path', 'asc')
            ->paginated(false);
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
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'view' => Pages\ViewLocation::route('/{record}'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['parent', 'children'])
            ->orderBy('path');
    }
}