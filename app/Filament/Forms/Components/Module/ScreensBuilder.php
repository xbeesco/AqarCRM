<?php

namespace App\Filament\Forms\Components\Module;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use App\Filament\Forms\Components\Module\Screens\TableColumnsBuilder;
use App\Filament\Forms\Components\Module\Screens\TableFiltersBuilder;
use App\Filament\Forms\Components\Module\Screens\FormFieldsBuilder;

class ScreensBuilder
{
    public static function make(string $name = 'screens'): Section
    {
        return Section::make('Screens')
            ->schema([
                Repeater::make($name)
                    ->label('')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('screen_slug')
                                    ->label('Screen Slug')
                                    ->required()
                                    ->placeholder('list_properties, edit_unit')
                                    ->reactive(),
                                
                                Select::make('filament_template')
                                    ->label('Template Type')
                                    ->options([
                                        'dashboard' => 'Dashboard',
                                        'auth' => 'Auth',
                                        'resource index' => 'Resource Index',
                                        'resource add/edit' => 'Resource Add/Edit',
                                        'resource view' => 'Resource View',
                                        'custom' => 'Custom',
                                    ])
                                    ->reactive(),
                                
                                Select::make('permissions')
                                    ->label('Permissions')
                                    ->multiple()
                                    ->options([
                                        'view' => 'View',
                                        'create' => 'Create',
                                        'edit' => 'Edit',
                                        'delete' => 'Delete',
                                        'export' => 'Export',
                                    ]),
                            ]),
                        
                        // Components and Interactions Wizard
                        Wizard::make([
                            Step::make('Components')
                                ->schema([
                                    // Table Component
                                    Section::make('Table Component')
                                        ->visible(fn ($get) => in_array($get('filament_template'), ['resource index', 'dashboard']))
                                        ->schema([
                                        TableColumnsBuilder::make('components.table.columns'),
                                        
                                        TableFiltersBuilder::make('components.table.filters'),
                                        
                                        KeyValue::make('components.table.record_actions')
                                            ->label('Record Actions')
                                            ->keyLabel('Action Name')
                                            ->valueLabel('Action Type'),
                                        
                                        KeyValue::make('components.table.bulk_actions')
                                            ->label('Bulk Actions')
                                            ->keyLabel('Action Name')
                                            ->valueLabel('Action Type'),
                                        
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('components.table.paginated')
                                                    ->label('Pagination')
                                                    ->options([
                                                        'false' => 'No Pagination',
                                                        '10' => '10 per page',
                                                        '25' => '25 per page',
                                                        '50' => '50 per page',
                                                        '100' => '100 per page',
                                                    ])
                                                    ->default('25'),
                                                
                                                TextInput::make('components.table.default_sort')
                                                    ->label('Default Sort')
                                                    ->placeholder('created_at:desc'),
                                            ]),
                                    ])
                                    ->collapsed(),
                                
                                // Form Component
                                Section::make('Form Component')
                                    ->visible(fn ($get) => in_array($get('filament_template'), ['resource add/edit', 'custom']))
                                    ->schema([
                                        FormFieldsBuilder::make('components.form.fields'),
                                        
                                        KeyValue::make('components.form.actions')
                                            ->label('Form Actions')
                                            ->keyLabel('Action Name')
                                            ->valueLabel('Action Type'),
                                    ])
                                    ->collapsed(),
                                
                                // Widgets
                                Repeater::make('components.widgets')
                                    ->label('Widgets')
                                    ->visible(fn ($get) => in_array($get('filament_template'), ['dashboard', 'resource view']))
                                    ->schema([
                                        TextInput::make('widget_name')
                                            ->label('Widget Name')
                                            ->required(),
                                        
                                        Select::make('type')
                                            ->label('Widget Type')
                                            ->options([
                                                'stats' => 'Stats Card',
                                                'chart' => 'Chart',
                                                'table' => 'Table Widget',
                                                'custom' => 'Custom Widget',
                                            ]),
                                        
                                        TextInput::make('position')
                                            ->label('Position')
                                            ->placeholder('1, 2, 3...'),
                                    ])
                                    ->columns(3)
                                    ->itemLabel(fn ($state) => isset($state['widget_name']) ? $state['widget_name'] : 'Widget')
                                    ->collapsible()
                                    ->collapsed()
                                    ->addActionLabel('Add Widget'),
                                
                                // Info List
                                Repeater::make('components.infolist')
                                    ->label('Info List')
                                    ->visible(fn ($get) => $get('filament_template') === 'resource view')
                                    ->schema([
                                        TextInput::make('field')
                                            ->label('Field')
                                            ->required(),
                                        
                                        TextInput::make('label')
                                            ->label('Label')
                                            ->required(),
                                        
                                        Select::make('type')
                                            ->label('Display Type')
                                            ->options([
                                                'text' => 'Text',
                                                'badge' => 'Badge',
                                                'boolean' => 'Boolean',
                                                'date' => 'Date',
                                                'image' => 'Image',
                                            ]),
                                    ])
                                    ->columns(3)
                                    ->itemLabel(fn ($state) => isset($state['label']) ? $state['label'] : (isset($state['field']) ? $state['field'] : 'Info'))
                                    ->collapsible()
                                    ->collapsed()
                                    ->addActionLabel('Add Info Field'),
                                ]),
                            
                            Step::make('Interactions')
                                ->schema([
                                    Repeater::make('interactions')
                                        ->label('')
                                    ->schema([
                                        TextInput::make('event_slug')
                                            ->label('Event Slug')
                                            ->required(),
                                        
                                        TextInput::make('user_action')
                                            ->label('User Action')
                                            ->placeholder('click, hover, submit'),
                                        
                                        TextInput::make('target')
                                            ->label('Target Element')
                                            ->placeholder('button.save, #form-id'),
                                        
                                        Textarea::make('response')
                                            ->label('Response/Effect')
                                            ->rows(2),
                                    ])
                                    ->columns(2)
                                        ->itemLabel(fn ($state) => isset($state['event_slug']) ? $state['event_slug'] : 'Interaction')
                                        ->collapsible()
                                        ->collapsed()
                                        ->addActionLabel('Add Interaction'),
                                ]),
                        ])
                        ->persistStepInQueryString()
                        ->columnSpanFull(),
                    ])
                    ->itemLabel(fn ($state) => isset($state['screen_slug']) ? ucfirst($state['screen_slug']) : 'Screen')
                    ->collapsible()
                    ->collapsed()
                    ->cloneable()
                    ->reorderable()
                    ->addActionLabel('Add Screen'),
            ])
            ->collapsible()
            ->collapsed();
    }
}