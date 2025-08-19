<?php

namespace App\Filament\Forms\Components\Module\Screens;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\KeyValue;

class TableFiltersBuilder
{
    public static function make(string $name = 'filters'): Repeater
    {
        return Repeater::make($name)
            ->label('Table Filters')
            ->schema([
                TextInput::make('name')
                    ->label('Filter Name')
                    ->required(),
                
                TextInput::make('label')
                    ->label('Display Label')
                    ->required(),
                
                Select::make('type')
                    ->label('Filter Type')
                    ->options([
                        'select' => 'Select',
                        'date_range' => 'Date Range',
                        'number_range' => 'Number Range',
                        'boolean' => 'Boolean',
                        'text' => 'Text Search',
                    ])
                    ->reactive(),
                
                KeyValue::make('options')
                    ->label('Filter Options')
                    ->keyLabel('Value')
                    ->valueLabel('Label')
                    ->visible(fn ($get) => $get('type') === 'select'),
                
                TextInput::make('placeholder')
                    ->label('Placeholder'),
            ])
            ->itemLabel(fn ($state) => isset($state['label']) ? $state['label'] : (isset($state['name']) ? $state['name'] : 'Filter'))
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->addActionLabel('Add Filter');
    }
}