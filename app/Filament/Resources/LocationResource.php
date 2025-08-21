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
                            ->options(function (callable $get) {
                                $level = $get('level');
                                if (!$level || $level <= 1) {
                                    return [];
                                }
                                return Location::getParentOptions($level);
                            })
                            ->visible(fn (callable $get) => $get('level') > 1)
                            ->reactive(),
                            
                        TextInput::make('name_ar')
                            ->label('الاسم بالعربية')
                            ->required()
                            ->maxLength(255),
                            
                        TextInput::make('name_en')
                            ->label('الاسم بالإنجليزية')
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
                    ->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_ar')
                    ->label('الموقع')
                    ->searchable()
                    ->formatStateUsing(function (string $state, Location $record): string {
                        // Create hierarchical indentation with visual tree structure
                        $treeStructure = '';
                        
                        // Build tree indentation based on level
                        if ($record->level > 1) {
                            $treeStructure = str_repeat('│&nbsp;&nbsp;&nbsp;', $record->level - 2);
                            $treeStructure .= '├── ';
                        }
                        
                        // Icons for different levels
                        $icon = match ($record->level) {
                            1 => '🌍',  // منطقة
                            2 => '🏙️',  // مدينة  
                            3 => '🏢',  // مركز
                            4 => '🏘️',  // حي
                            default => '📍'
                        };
                        
                        // Combine Arabic and English names
                        $displayName = $state;
                        if ($record->name_en && $record->name_en !== $state) {
                            $displayName .= ' <span class="text-gray-500">(' . $record->name_en . ')</span>';
                        }
                        
                        return $treeStructure . $icon . ' ' . $displayName;
                    })
                    ->html()
                    ->weight('medium')
                    ->wrap(),
                    
                BadgeColumn::make('level_label')
                    ->label('المستوى')
                    ->color(fn (string $state): string => match ($state) {
                        'منطقة' => 'success',
                        'مدينة' => 'warning', 
                        'مركز' => 'info',
                        'حي' => 'gray',
                        default => 'gray',
                    }),
                    
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
            ->filters([
                SelectFilter::make('level')
                    ->label('المستوى')
                    ->options([
                        1 => 'منطقة',
                        2 => 'مدينة', 
                        3 => 'مركز',
                        4 => 'حي'
                    ]),
                    
                SelectFilter::make('is_active')
                    ->label('الحالة')
                    ->options([
                        1 => 'نشط',
                        0 => 'غير نشط',
                    ]),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
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
            ->with(['parent'])
            ->orderBy('path')
            ->orderBy('name');
    }
}