<?php

namespace App\Filament\Forms\Components\Module;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\KeyValue;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class ProcessesBuilder
{
    public static function make(string $name = 'processes'): Tabs
    {
        return Tabs::make('ProcessesTabs')
            ->tabs([
                Tab::make('CRUD Operations')
                    ->schema([
                        Repeater::make($name . '.custom_crud_operations')
                            ->label('')
                    ->schema([
                        TextInput::make('operation_name')
                            ->label('Operation Name')
                            ->required(),
                        
                        Select::make('type')
                            ->label('Type')
                            ->options([
                                'create' => 'Create',
                                'read' => 'Read',
                                'update' => 'Update',
                                'delete' => 'Delete',
                                'bulk' => 'Bulk Operation',
                            ]),
                        
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(2),
                        
                        KeyValue::make('parameters')
                            ->label('Parameters')
                            ->keyLabel('Name')
                            ->valueLabel('Type'),
                        
                        Textarea::make('logic')
                            ->label('Business Logic')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                            ->itemLabel(fn ($state) => isset($state['operation_name']) ? $state['operation_name'] : 'CRUD Operation')
                            ->collapsible()
                            ->collapsed()
                            ->addActionLabel('Add CRUD Operation'),
                    ]),
                
                Tab::make('Business Operations')
                    ->schema([
                        Repeater::make($name . '.business_operations')
                            ->label('')
                    ->schema([
                        TextInput::make('operation_name')
                            ->label('Operation Name')
                            ->required(),
                        
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(2),
                        
                        KeyValue::make('inputs')
                            ->label('Inputs')
                            ->keyLabel('Field')
                            ->valueLabel('Type'),
                        
                        KeyValue::make('outputs')
                            ->label('Outputs')
                            ->keyLabel('Field')
                            ->valueLabel('Type'),
                        
                        Repeater::make('steps')
                            ->label('Process Steps')
                            ->simple(
                                TextInput::make('step')
                                    ->placeholder('Step description')
                            ),
                    ])
                            ->itemLabel(fn ($state) => isset($state['operation_name']) ? $state['operation_name'] : 'Business Operation')
                            ->collapsible()
                            ->collapsed()
                            ->addActionLabel('Add Business Operation'),
                    ]),
                
                Tab::make('Query Operations')
                    ->schema([
                        Repeater::make($name . '.query_operations')
                            ->label('')
                    ->schema([
                        TextInput::make('query_name')
                            ->label('Query Name')
                            ->required(),
                        
                        Select::make('type')
                            ->label('Query Type')
                            ->options([
                                'select' => 'Select',
                                'aggregate' => 'Aggregate',
                                'join' => 'Join',
                                'complex' => 'Complex Query',
                            ]),
                        
                        Textarea::make('sql')
                            ->label('SQL/Query Builder')
                            ->rows(3)
                            ->columnSpanFull(),
                        
                        KeyValue::make('bindings')
                            ->label('Query Bindings')
                            ->keyLabel('Parameter')
                            ->valueLabel('Type'),
                    ])
                            ->itemLabel(fn ($state) => isset($state['query_name']) ? $state['query_name'] : 'Query Operation')
                            ->collapsible()
                            ->collapsed()
                            ->addActionLabel('Add Query'),
                    ]),
                
                Tab::make('Integration Operations')
                    ->schema([
                        Repeater::make($name . '.integration_operations')
                            ->label('')
                    ->schema([
                        TextInput::make('integration_name')
                            ->label('Integration Name')
                            ->required(),
                        
                        Select::make('type')
                            ->label('Integration Type')
                            ->options([
                                'api' => 'API',
                                'webhook' => 'Webhook',
                                'queue' => 'Queue Job',
                                'event' => 'Event',
                                'notification' => 'Notification',
                            ]),
                        
                        TextInput::make('endpoint')
                            ->label('Endpoint/Target'),
                        
                        KeyValue::make('payload')
                            ->label('Payload Structure')
                            ->keyLabel('Field')
                            ->valueLabel('Type'),
                    ])
                            ->itemLabel(fn ($state) => isset($state['integration_name']) ? $state['integration_name'] : 'Integration')
                            ->collapsible()
                            ->collapsed()
                            ->addActionLabel('Add Integration'),
                    ]),
            ]);
    }
}