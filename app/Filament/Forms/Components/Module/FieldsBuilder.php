<?php

namespace App\Filament\Forms\Components\Module;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;

class FieldsBuilder
{
    public static function make(string $name = 'fields'): Repeater
    {
        return Repeater::make($name)
            ->label('Database Fields')
            ->schema([
                Grid::make(4)
                    ->schema([
                        TextInput::make('field_name')
                            ->label('Field Name')
                            ->required()
                            ->placeholder('column_name')
                            ->columnSpan(2),
                        
                        Select::make('type')
                            ->label('Type')
                            ->required()
                            ->options([
                                'int' => 'Integer',
                                'bigint' => 'Big Integer',
                                'string' => 'String',
                                'text' => 'Text',
                                'bool' => 'Boolean',
                                'decimal' => 'Decimal',
                                'float' => 'Float',
                                'date' => 'Date',
                                'datetime' => 'DateTime',
                                'timestamp' => 'Timestamp',
                                'json' => 'JSON',
                                'enum' => 'Enum',
                            ])
                            ->reactive(),
                        
                        TextInput::make('length')
                            ->label('Length')
                            ->numeric()
                            ->placeholder('255')
                            ->visible(fn ($get) => in_array($get('type'), ['string', 'decimal'])),
                    ]),
                
                Grid::make(6)
                    ->schema([
                        Toggle::make('nullable')
                            ->label('Nullable')
                            ->inline(),
                        
                        Toggle::make('unique')
                            ->label('Unique')
                            ->inline(),
                        
                        Select::make('index')
                            ->label('Index')
                            ->options([
                                'none' => 'None',
                                'index' => 'Index',
                                'unique' => 'Unique',
                                'fulltext' => 'Fulltext',
                            ])
                            ->default('none'),
                        
                        Toggle::make('fillable')
                            ->label('Fillable')
                            ->inline()
                            ->default(true),
                        
                        TextInput::make('default')
                            ->label('Default')
                            ->placeholder('null'),
                        
                        TextInput::make('foreign')
                            ->label('Foreign Key')
                            ->placeholder('table.field'),
                    ]),
                
                TextInput::make('validation')
                    ->label('Validation Rules')
                    ->placeholder('required|email|min:3|max:255')
                    ->columnSpanFull(),
            ])
            ->itemLabel(fn (array $state): ?string => 
                isset($state['field_name']) ? $state['field_name'] . ' (' . ($state['type'] ?? '') . ')' : null
            )
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->reorderable()
            ->addActionLabel('Add Field')
            ->columnSpanFull();
    }
}