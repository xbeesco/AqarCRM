<?php

namespace App\Filament\Forms\Components\Module\Screens;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Schemas\Components\Grid;

class FormFieldsBuilder
{
    public static function make(string $name = 'fields'): Repeater
    {
        return Repeater::make($name)
            ->label('Form Fields')
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('name')
                            ->label('Field Name')
                            ->required(),
                        
                        TextInput::make('label')
                            ->label('Field Label')
                            ->required(),
                        
                        Select::make('type')
                            ->label('Field Type')
                            ->options([
                                'text' => 'Text Input',
                                'textarea' => 'Textarea',
                                'number' => 'Number',
                                'email' => 'Email',
                                'password' => 'Password',
                                'select' => 'Select',
                                'multiselect' => 'Multi Select',
                                'radio' => 'Radio',
                                'checkbox' => 'Checkbox',
                                'toggle' => 'Toggle',
                                'date' => 'Date',
                                'datetime' => 'DateTime',
                                'time' => 'Time',
                                'file' => 'File Upload',
                                'image' => 'Image Upload',
                                'rich_editor' => 'Rich Editor',
                                'markdown' => 'Markdown',
                                'repeater' => 'Repeater',
                                'key_value' => 'Key-Value',
                                'color' => 'Color Picker',
                            ])
                            ->reactive(),
                    ]),
                
                Grid::make(4)
                    ->schema([
                        Toggle::make('required')
                            ->label('Required')
                            ->inline(),
                        
                        Toggle::make('disabled')
                            ->label('Disabled')
                            ->inline(),
                        
                        Toggle::make('readonly')
                            ->label('Read Only')
                            ->inline(),
                        
                        Toggle::make('multiple')
                            ->label('Multiple')
                            ->inline()
                            ->visible(fn ($get) => in_array($get('type'), ['select', 'file', 'image'])),
                    ]),
                
                TextInput::make('placeholder')
                    ->label('Placeholder')
                    ->columnSpanFull(),
                
                KeyValue::make('options')
                    ->label('Options')
                    ->keyLabel('Value')
                    ->valueLabel('Label')
                    ->visible(fn ($get) => in_array($get('type'), ['select', 'multiselect', 'radio']))
                    ->columnSpanFull(),
                
                TextInput::make('validation')
                    ->label('Validation Rules')
                    ->placeholder('required|min:3|max:255')
                    ->columnSpanFull(),
            ])
            ->itemLabel(fn ($state) => isset($state['label']) ? $state['label'] : (isset($state['name']) ? $state['name'] : 'Field'))
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->reorderable()
            ->addActionLabel('Add Form Field');
    }
}