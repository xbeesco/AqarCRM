<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class ScreenComponentBuilder
{
    public static function make(string $name = 'screens'): Repeater
    {
        return Repeater::make($name)
            ->label('Screen Definitions')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('screen_slug')
                            ->label('Screen Identifier')
                            ->required()
                            ->placeholder('list_properties, edit_unit, etc.'),
                        
                        Select::make('template')
                            ->label('Filament Template')
                            ->required()
                            ->options([
                                'dashboard' => 'Dashboard',
                                'auth' => 'Authentication',
                                'resource_index' => 'Resource Index (List)',
                                'resource_create' => 'Resource Create',
                                'resource_edit' => 'Resource Edit',
                                'resource_view' => 'Resource View',
                                'custom_page' => 'Custom Page',
                                'widget' => 'Widget',
                                'modal' => 'Modal/Dialog',
                            ]),
                    ]),
                
                Select::make('permissions')
                    ->label('Required Permissions')
                    ->multiple()
                    ->searchable()
                    ->options([
                        'view' => 'View',
                        'create' => 'Create',
                        'edit' => 'Edit',
                        'delete' => 'Delete',
                        'export' => 'Export',
                        'import' => 'Import',
                    ])
                    ->columnSpanFull(),
                
                // Table Components
                Section::make('Table Configuration')
                    ->description('Configure table columns if this is a list screen')
                    ->schema([
                        Repeater::make('table_columns')
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
                                            ->label('Column Type')
                                            ->required()
                                            ->options([
                                                'text' => 'Text',
                                                'number' => 'Number',
                                                'date' => 'Date',
                                                'datetime' => 'DateTime',
                                                'badge' => 'Badge/Status',
                                                'boolean' => 'Boolean/Toggle',
                                                'image' => 'Image',
                                                'link' => 'Link',
                                                'money' => 'Money/Currency',
                                                'percentage' => 'Percentage',
                                                'color' => 'Color',
                                                'icon' => 'Icon',
                                            ]),
                                    ]),
                                
                                Grid::make(4)
                                    ->schema([
                                        Toggle::make('sortable')
                                            ->label('Sortable')
                                            ->inline(),
                                        
                                        Toggle::make('searchable')
                                            ->label('Searchable')
                                            ->inline(),
                                        
                                        Toggle::make('toggleable')
                                            ->label('Toggleable')
                                            ->inline(),
                                        
                                        Select::make('align')
                                            ->label('Align')
                                            ->options([
                                                'left' => 'Left',
                                                'center' => 'Center',
                                                'right' => 'Right',
                                            ])
                                            ->default('left'),
                                    ]),
                                
                                TextInput::make('format')
                                    ->label('Format Pattern')
                                    ->placeholder('Y-m-d, $0.00, etc.')
                                    ->helperText('Format pattern for dates, numbers, etc.')
                                    ->columnSpanFull(),
                            ])
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? $state['name'] ?? null)
                            ->addActionLabel('Add Column'),
                    ])
                    ->collapsed(),
                
                // Form Components
                Section::make('Form Configuration')
                    ->description('Configure form fields if this is a create/edit screen')
                    ->schema([
                        Repeater::make('form_fields')
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
                                            ->required()
                                            ->options([
                                                'text' => 'Text Input',
                                                'textarea' => 'Textarea',
                                                'number' => 'Number',
                                                'email' => 'Email',
                                                'password' => 'Password',
                                                'select' => 'Select/Dropdown',
                                                'multiselect' => 'Multi Select',
                                                'radio' => 'Radio Buttons',
                                                'checkbox' => 'Checkbox',
                                                'toggle' => 'Toggle Switch',
                                                'date' => 'Date Picker',
                                                'datetime' => 'DateTime Picker',
                                                'time' => 'Time Picker',
                                                'file' => 'File Upload',
                                                'image' => 'Image Upload',
                                                'rich_editor' => 'Rich Text Editor',
                                                'markdown' => 'Markdown Editor',
                                                'repeater' => 'Repeater',
                                                'key_value' => 'Key-Value',
                                                'color' => 'Color Picker',
                                                'hidden' => 'Hidden Field',
                                            ]),
                                    ]),
                                
                                Grid::make(3)
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
                                    ]),
                                
                                KeyValue::make('options')
                                    ->label('Options (for select, radio, etc.)')
                                    ->keyLabel('Value')
                                    ->valueLabel('Label')
                                    ->visible(fn (callable $get) => in_array($get('type'), ['select', 'multiselect', 'radio']))
                                    ->columnSpanFull(),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|min:3|max:255')
                                    ->columnSpanFull(),
                            ])
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? $state['name'] ?? null)
                            ->addActionLabel('Add Form Field'),
                    ])
                    ->collapsed(),
                
                // Actions
                KeyValue::make('actions')
                    ->label('Screen Actions')
                    ->keyLabel('Action Name')
                    ->valueLabel('Action Type')
                    ->helperText('Define available actions like create, edit, delete, export, etc.')
                    ->columnSpanFull(),
            ])
            ->itemLabel(fn (array $state): ?string => 
                isset($state['screen_slug']) ? $state['screen_slug'] . ' (' . ($state['template'] ?? 'custom') . ')' : null
            )
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->reorderable()
            ->defaultItems(0)
            ->addActionLabel('Add Screen Definition')
            ->columnSpanFull();
    }
}