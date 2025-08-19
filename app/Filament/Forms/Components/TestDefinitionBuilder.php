<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\KeyValue;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

class TestDefinitionBuilder
{
    public static function make(string $name = 'tests'): Section
    {
        return Section::make('Test Definitions')
            ->description('Define unit, feature and browser tests')
            ->schema([
                // Unit Tests
                Repeater::make($name . '.unit')
                    ->label('Unit Tests')
                    ->schema([
                        TextInput::make('test_name')
                            ->label('Test Name')
                            ->required()
                            ->placeholder('test_user_can_create_property'),
                        
                        Textarea::make('description')
                            ->label('Test Description')
                            ->rows(2)
                            ->columnSpanFull(),
                        
                        KeyValue::make('input')
                            ->label('Test Input Data')
                            ->keyLabel('Parameter')
                            ->valueLabel('Value')
                            ->columnSpanFull(),
                        
                        KeyValue::make('expected')
                            ->label('Expected Output')
                            ->keyLabel('Field')
                            ->valueLabel('Expected Value')
                            ->columnSpanFull(),
                        
                        Repeater::make('assertions')
                            ->label('Assertions')
                            ->simple(
                                TextInput::make('assertion')
                                    ->placeholder('assertEquals, assertTrue, assertDatabaseHas, etc.')
                            )
                            ->columnSpanFull(),
                    ])
                    ->itemLabel(fn (array $state): ?string => $state['test_name'] ?? null)
                    ->collapsed()
                    ->collapsible()
                    ->addActionLabel('Add Unit Test'),
                
                // Feature Tests
                Repeater::make($name . '.feature')
                    ->label('Feature Tests')
                    ->schema([
                        TextInput::make('test_name')
                            ->label('Test Name')
                            ->required()
                            ->placeholder('test_property_workflow'),
                        
                        Textarea::make('scenario')
                            ->label('Test Scenario')
                            ->rows(3)
                            ->columnSpanFull(),
                        
                        Repeater::make('steps')
                            ->label('Test Steps')
                            ->simple(
                                TextInput::make('step')
                                    ->placeholder('Login as admin, Navigate to properties, Click create...')
                            )
                            ->columnSpanFull(),
                        
                        Textarea::make('expected_result')
                            ->label('Expected Result')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->itemLabel(fn (array $state): ?string => $state['test_name'] ?? null)
                    ->collapsed()
                    ->collapsible()
                    ->addActionLabel('Add Feature Test'),
                
                // Playwright Tests
                Repeater::make($name . '.playwright')
                    ->label('Browser Tests (Playwright)')
                    ->schema([
                        TextInput::make('test_name')
                            ->label('Test Name')
                            ->required()
                            ->placeholder('test_create_property_flow'),
                        
                        Select::make('tools')
                            ->label('Required MCP Tools')
                            ->multiple()
                            ->options([
                                'browser_navigate' => 'Navigate',
                                'browser_click' => 'Click',
                                'browser_type' => 'Type Text',
                                'browser_select_option' => 'Select Option',
                                'browser_take_screenshot' => 'Screenshot',
                                'browser_wait_for' => 'Wait For',
                                'browser_evaluate' => 'Evaluate JS',
                            ])
                            ->columnSpanFull(),
                        
                        Repeater::make('flow')
                            ->label('Test Flow')
                            ->schema([
                                Select::make('action')
                                    ->label('Action')
                                    ->options([
                                        'navigate' => 'Navigate to URL',
                                        'click' => 'Click Element',
                                        'type' => 'Type Text',
                                        'select' => 'Select Option',
                                        'wait' => 'Wait',
                                        'screenshot' => 'Take Screenshot',
                                        'assert' => 'Assert/Validate',
                                    ])
                                    ->required(),
                                
                                TextInput::make('target')
                                    ->label('Target Element/URL')
                                    ->placeholder('button.submit, #email-input, etc.'),
                                
                                TextInput::make('value')
                                    ->label('Value/Text')
                                    ->placeholder('Text to type, option to select, etc.'),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                        
                        Repeater::make('validations')
                            ->label('Validations')
                            ->simple(
                                TextInput::make('validation')
                                    ->placeholder('Element exists, Text contains, URL matches, etc.')
                            )
                            ->columnSpanFull(),
                    ])
                    ->itemLabel(fn (array $state): ?string => $state['test_name'] ?? null)
                    ->collapsed()
                    ->collapsible()
                    ->addActionLabel('Add Browser Test'),
            ])
            ->collapsible()
            ->collapsed();
    }
}