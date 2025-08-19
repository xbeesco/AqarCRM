<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;

class DatabaseSchemaBuilder
{
    public static function make(string $name = 'database_schema'): Repeater
    {
        return Repeater::make($name)
            ->label('Database Schema')
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('field_name')
                            ->label('Field Name')
                            ->required()
                            ->placeholder('column_name'),
                        
                        Select::make('type')
                            ->label('Data Type')
                            ->required()
                            ->options([
                                'bigIncrements' => 'Big Increments (ID)',
                                'increments' => 'Increments',
                                'integer' => 'Integer',
                                'bigInteger' => 'Big Integer',
                                'decimal' => 'Decimal',
                                'float' => 'Float',
                                'double' => 'Double',
                                'string' => 'String (VARCHAR)',
                                'text' => 'Text',
                                'longText' => 'Long Text',
                                'json' => 'JSON',
                                'boolean' => 'Boolean',
                                'date' => 'Date',
                                'datetime' => 'DateTime',
                                'timestamp' => 'Timestamp',
                                'time' => 'Time',
                                'year' => 'Year',
                                'enum' => 'Enum',
                                'uuid' => 'UUID',
                            ])
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => 
                                $state === 'string' ? $set('length', 255) : $set('length', null)
                            ),
                        
                        TextInput::make('length')
                            ->label('Length/Precision')
                            ->numeric()
                            ->placeholder('255')
                            ->visible(fn (callable $get) => in_array($get('type'), ['string', 'decimal', 'float', 'double'])),
                    ]),
                
                Grid::make(4)
                    ->schema([
                        Toggle::make('nullable')
                            ->label('Nullable')
                            ->inline(),
                        
                        Toggle::make('unique')
                            ->label('Unique')
                            ->inline(),
                        
                        Toggle::make('index')
                            ->label('Index')
                            ->inline(),
                        
                        Toggle::make('fillable')
                            ->label('Fillable')
                            ->inline()
                            ->default(true),
                    ]),
                
                Grid::make(2)
                    ->schema([
                        TextInput::make('default')
                            ->label('Default Value')
                            ->placeholder('null or value'),
                        
                        Select::make('foreign')
                            ->label('Foreign Key')
                            ->placeholder('table.column')
                            ->searchable()
                            ->options(function () {
                                // يمكن هنا جلب الجداول والأعمدة الموجودة
                                return [
                                    'users.id' => 'users.id',
                                    'properties.id' => 'properties.id',
                                    'units.id' => 'units.id',
                                    'owners.id' => 'owners.id',
                                    'tenants.id' => 'tenants.id',
                                ];
                            }),
                    ]),
                
                Textarea::make('validation')
                    ->label('Validation Rules')
                    ->rows(2)
                    ->placeholder('required|email|min:3|max:255|unique:table,column')
                    ->helperText('Laravel validation rules separated by |')
                    ->columnSpanFull(),
                
                TextInput::make('enum_values')
                    ->label('Enum Values')
                    ->placeholder('active,inactive,pending')
                    ->helperText('Comma separated values')
                    ->visible(fn (callable $get) => $get('type') === 'enum')
                    ->columnSpanFull(),
            ])
            ->itemLabel(fn (array $state): ?string => 
                isset($state['field_name']) ? $state['field_name'] . ' (' . ($state['type'] ?? 'unknown') . ')' : null
            )
            ->collapsible()
            ->cloneable()
            ->reorderable()
            ->defaultItems(0)
            ->addActionLabel('Add Database Field')
            ->columnSpanFull();
    }
}