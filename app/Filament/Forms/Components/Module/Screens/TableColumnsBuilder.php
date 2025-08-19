<?php

namespace App\Filament\Forms\Components\Module\Screens;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;

class TableColumnsBuilder
{
    public static function make(string $name = 'columns'): Repeater
    {
        return Repeater::make($name)
            ->label('Table Columns')
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('name')
                            ->label('Column Name')
                            ->required(),
                        
                        TextInput::make('label')
                            ->label('Display Label')
                            ->required(),
                        
                        Select::make('type')
                            ->label('Type')
                            ->options([
                                'text' => 'Text',
                                'number' => 'Number',
                                'date' => 'Date',
                                'badge' => 'Badge',
                                'link' => 'Link',
                                'image' => 'Image',
                                'boolean' => 'Boolean',
                            ])
                            ->default('text'),
                    ]),
                
                Grid::make(4)
                    ->schema([
                        Toggle::make('sortable')
                            ->label('Sortable')
                            ->inline()
                            ->default(true),
                        
                        Toggle::make('searchable')
                            ->label('Searchable')
                            ->inline(),
                        
                        TextInput::make('format')
                            ->label('Format')
                            ->placeholder('date:Y-m-d'),
                        
                        Select::make('align')
                            ->label('Align')
                            ->options([
                                'left' => 'Left',
                                'center' => 'Center',
                                'right' => 'Right',
                            ])
                            ->default('left'),
                    ]),
                
                TextInput::make('width')
                    ->label('Width')
                    ->placeholder('auto, 100px, 20%')
                    ->columnSpanFull(),
            ])
            ->itemLabel(fn ($state) => isset($state['label']) ? $state['label'] : (isset($state['name']) ? $state['name'] : 'Column'))
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->reorderable()
            ->addActionLabel('Add Column');
    }
}