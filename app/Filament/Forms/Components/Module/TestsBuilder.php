<?php

namespace App\Filament\Forms\Components\Module;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class TestsBuilder
{
    public static function make(string $name = 'tests'): Tabs
    {
        return Tabs::make('TestsTabs')
            ->tabs([
                Tab::make('Unit Tests')
                    ->schema([
                        Repeater::make($name . '.unit')
                            ->label('')
                            ->schema([
                                TextInput::make('test_name')
                                    ->label('Test Name')
                                    ->required()
                                    ->columnSpan(2),
                                
                                Textarea::make('description')
                                    ->label('Description')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                
                                KeyValue::make('input')
                                    ->label('Input Data')
                                    ->keyLabel('Parameter')
                                    ->valueLabel('Value'),
                                
                                KeyValue::make('expected')
                                    ->label('Expected Output')
                                    ->keyLabel('Field')
                                    ->valueLabel('Value'),
                                
                                Repeater::make('assertions')
                                    ->label('Assertions')
                                    ->simple(
                                        TextInput::make('assertion')
                                            ->placeholder('assertEquals, assertTrue, etc.')
                                    )
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->itemLabel(fn ($state) => isset($state['test_name']) ? $state['test_name'] : 'Unit Test')
                            ->collapsible()
                            ->collapsed()
                            ->addActionLabel('Add Unit Test'),
                    ]),
                
                Tab::make('Feature Tests')
                    ->schema([
                        Repeater::make($name . '.feature')
                            ->label('')
                            ->schema([
                                TextInput::make('test_name')
                                    ->label('Test Name')
                                    ->required(),
                                
                                Textarea::make('scenario')
                                    ->label('Scenario')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                
                                Repeater::make('steps')
                                    ->label('Steps')
                                    ->simple(
                                        TextInput::make('step')
                                    )
                                    ->columnSpanFull(),
                                
                                Textarea::make('expected_result')
                                    ->label('Expected Result')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->itemLabel(fn ($state) => isset($state['test_name']) ? $state['test_name'] : 'Feature Test')
                            ->collapsible()
                            ->collapsed()
                            ->addActionLabel('Add Feature Test'),
                    ]),
                
                Tab::make('Playwright MCP Tests')
                    ->schema([
                        Repeater::make($name . '.playwright_mcp')
                            ->label('')
                            ->schema([
                                TextInput::make('test_name')
                                    ->label('Test Name')
                                    ->required(),
                                
                                Select::make('tools')
                                    ->label('MCP Tools')
                                    ->multiple()
                                    ->options([
                                        'browser_navigate' => 'Navigate',
                                        'browser_click' => 'Click',
                                        'browser_type' => 'Type',
                                        'browser_select' => 'Select',
                                        'browser_screenshot' => 'Screenshot',
                                    ]),
                                
                                Repeater::make('flow')
                                    ->label('Test Flow')
                                    ->schema([
                                        Select::make('action')
                                            ->label('Action')
                                            ->options([
                                                'navigate' => 'Navigate',
                                                'click' => 'Click',
                                                'type' => 'Type',
                                                'wait' => 'Wait',
                                                'assert' => 'Assert',
                                            ]),
                                        TextInput::make('target')
                                            ->label('Target'),
                                        TextInput::make('value')
                                            ->label('Value'),
                                    ])
                                    ->columns(3)
                                    ->itemLabel(fn ($state) => isset($state['action']) ? ucfirst($state['action']) : 'Step')
                                    ->collapsible()
                                    ->collapsed(),
                                
                                Repeater::make('validations')
                                    ->label('Validations')
                                    ->simple(
                                        TextInput::make('validation')
                                    ),
                            ])
                            ->itemLabel(fn ($state) => isset($state['test_name']) ? $state['test_name'] : 'Playwright Test')
                            ->collapsible()
                            ->collapsed()
                            ->addActionLabel('Add Playwright Test'),
                    ]),
            ]);
    }
}