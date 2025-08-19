<?php

namespace App\Filament\Forms\Components\Module\Screens;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use App\Filament\Forms\Components\SafeKeyValue as KeyValue;
use Filament\Forms\Components\ColorPicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Repeater;

class WidgetsBuilder
{
    public static function make(string $name = 'widgets'): Builder
    {
        return Builder::make($name)
            ->label('Dashboard Widgets')
            ->blocks([
                // Stats Widget
                Block::make('stats_widget')
                    ->label('Stats Overview Widget')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->description('Configure statistics overview widget')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Widget Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/')
                                        ->placeholder('revenue_stats'),
                                    
                                    TextInput::make('title')
                                        ->label('Widget Title')
                                        ->placeholder('Revenue Overview'),
                                ]),
                                
                                Grid::make(3)->schema([
                                    TextInput::make('columns')
                                        ->label('Columns')
                                        ->numeric()
                                        ->default(3)
                                        ->minValue(1)
                                        ->maxValue(6),
                                    
                                    Select::make('position')
                                        ->label('Position')
                                        ->options([
                                            'before_table' => 'Before Table',
                                            'after_table' => 'After Table',
                                            'dashboard' => 'Dashboard Only',
                                        ])
                                        ->default('before_table'),
                                    
                                    TextInput::make('sort_order')
                                        ->label('Sort Order')
                                        ->numeric()
                                        ->default(0),
                                ]),
                                
                                Repeater::make('stats')
                                    ->label('Statistics')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('label')
                                                ->label('Stat Label')
                                                ->required()
                                                ->placeholder('Total Revenue'),
                                            
                                            TextInput::make('value_field')
                                                ->label('Value Field')
                                                ->required()
                                                ->placeholder('total_revenue'),
                                            
                                            Select::make('format')
                                                ->label('Format')
                                                ->options([
                                                    'number' => 'Number',
                                                    'currency' => 'Currency',
                                                    'percentage' => 'Percentage',
                                                    'abbreviate' => 'Abbreviate (1K, 1M)',
                                                ])
                                                ->default('number'),
                                        ]),
                                        
                                        Grid::make(3)->schema([
                                            TextInput::make('description')
                                                ->label('Description')
                                                ->placeholder('32k increase'),
                                            
                                            TextInput::make('description_icon')
                                                ->label('Description Icon')
                                                ->placeholder('heroicon-m-arrow-trending-up'),
                                            
                                            Select::make('color')
                                                ->label('Color')
                                                ->options([
                                                    'primary' => 'Primary',
                                                    'success' => 'Success',
                                                    'danger' => 'Danger',
                                                    'warning' => 'Warning',
                                                    'info' => 'Info',
                                                ])
                                                ->default('primary'),
                                        ]),
                                    ])
                                    ->minItems(1)
                                    ->defaultItems(3)
                                    ->collapsible()
                                    ->collapsed(),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Toggle::make('show_chart')
                                    ->label('Show Trend Chart')
                                    ->inline()
                                    ->default(false),
                                
                                TextInput::make('chart_data_field')
                                    ->label('Chart Data Field')
                                    ->placeholder('daily_values')
                                    ->visible(fn ($get) => $get('show_chart')),
                                
                                Select::make('chart_color')
                                    ->label('Chart Color')
                                    ->options([
                                        '#3b82f6' => 'Blue',
                                        '#10b981' => 'Green',
                                        '#ef4444' => 'Red',
                                        '#f59e0b' => 'Yellow',
                                    ])
                                    ->default('#3b82f6')
                                    ->visible(fn ($get) => $get('show_chart')),
                                
                                Toggle::make('lazy_load')
                                    ->label('Lazy Load')
                                    ->inline()
                                    ->default(false),
                                
                                TextInput::make('poll_interval')
                                    ->label('Poll Interval (seconds)')
                                    ->numeric()
                                    ->placeholder('60')
                                    ->helperText('Leave empty to disable polling'),
                                
                                TextInput::make('permission')
                                    ->label('Required Permission')
                                    ->placeholder('view_stats'),
                                
                                KeyValue::make('extra_attributes')
                                    ->label('Extra Attributes'),
                            ]),
                    ]),
                
                // Chart Widget
                Block::make('chart_widget')
                    ->label('Chart Widget')
                    ->icon('heroicon-o-chart-pie')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Widget Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('title')
                                        ->label('Widget Title')
                                        ->required(),
                                    
                                    Select::make('chart_type')
                                        ->label('Chart Type')
                                        ->options([
                                            'line' => 'Line Chart',
                                            'bar' => 'Bar Chart',
                                            'pie' => 'Pie Chart',
                                            'doughnut' => 'Doughnut Chart',
                                            'radar' => 'Radar Chart',
                                            'polarArea' => 'Polar Area',
                                            'scatter' => 'Scatter Plot',
                                            'bubble' => 'Bubble Chart',
                                        ])
                                        ->default('line')
                                        ->reactive(),
                                ]),
                                
                                Grid::make(3)->schema([
                                    TextInput::make('height')
                                        ->label('Height (px)')
                                        ->numeric()
                                        ->default(300),
                                    
                                    Select::make('position')
                                        ->label('Position')
                                        ->options([
                                            'before_table' => 'Before Table',
                                            'after_table' => 'After Table',
                                            'dashboard' => 'Dashboard Only',
                                        ])
                                        ->default('dashboard'),
                                    
                                    TextInput::make('sort_order')
                                        ->label('Sort Order')
                                        ->numeric()
                                        ->default(0),
                                ]),
                            ]),
                        
                        Section::make('Data Configuration')
                            ->schema([
                                Select::make('data_source')
                                    ->label('Data Source')
                                    ->options([
                                        'static' => 'Static Data',
                                        'model' => 'From Model',
                                        'api' => 'From API',
                                        'query' => 'Custom Query',
                                    ])
                                    ->default('model')
                                    ->reactive(),
                                
                                // Model Data Source
                                Grid::make(2)->schema([
                                    TextInput::make('model')
                                        ->label('Model Class')
                                        ->placeholder('App\\Models\\Order')
                                        ->visible(fn ($get) => $get('data_source') === 'model'),
                                    
                                    TextInput::make('group_by')
                                        ->label('Group By')
                                        ->placeholder('created_at')
                                        ->visible(fn ($get) => $get('data_source') === 'model'),
                                ]),
                                
                                TextInput::make('aggregate_function')
                                    ->label('Aggregate Function')
                                    ->placeholder('count, sum:amount, avg:price')
                                    ->visible(fn ($get) => $get('data_source') === 'model'),
                                
                                // API Data Source
                                TextInput::make('api_endpoint')
                                    ->label('API Endpoint')
                                    ->placeholder('/api/chart-data')
                                    ->visible(fn ($get) => $get('data_source') === 'api'),
                                
                                // Static Data
                                Textarea::make('static_data')
                                    ->label('Static Data (JSON)')
                                    ->rows(5)
                                    ->visible(fn ($get) => $get('data_source') === 'static'),
                                
                                // Labels Configuration
                                TextInput::make('labels_field')
                                    ->label('Labels Field')
                                    ->placeholder('labels, months, categories')
                                    ->helperText('Field containing x-axis labels'),
                                
                                // Datasets
                                Repeater::make('datasets')
                                    ->label('Datasets')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('label')
                                                ->label('Dataset Label')
                                                ->required(),
                                            
                                            TextInput::make('data_field')
                                                ->label('Data Field')
                                                ->required(),
                                            
                                            ColorPicker::make('backgroundColor')
                                                ->label('Background Color')
                                                ->default('#3b82f6'),
                                        ]),
                                        
                                        Grid::make(3)->schema([
                                            ColorPicker::make('borderColor')
                                                ->label('Border Color')
                                                ->default('#2563eb'),
                                            
                                            TextInput::make('borderWidth')
                                                ->label('Border Width')
                                                ->numeric()
                                                ->default(2),
                                            
                                            Toggle::make('fill')
                                                ->label('Fill Area')
                                                ->inline()
                                                ->default(false),
                                        ]),
                                    ])
                                    ->minItems(1)
                                    ->defaultItems(1)
                                    ->collapsible()
                                    ->collapsed(),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Toggle::make('show_legend')
                                    ->label('Show Legend')
                                    ->inline()
                                    ->default(true),
                                
                                Select::make('legend_position')
                                    ->label('Legend Position')
                                    ->options([
                                        'top' => 'Top',
                                        'bottom' => 'Bottom',
                                        'left' => 'Left',
                                        'right' => 'Right',
                                    ])
                                    ->default('top')
                                    ->visible(fn ($get) => $get('show_legend')),
                                
                                Toggle::make('show_grid')
                                    ->label('Show Grid')
                                    ->inline()
                                    ->default(true),
                                
                                Toggle::make('stacked')
                                    ->label('Stacked')
                                    ->inline()
                                    ->default(false)
                                    ->visible(fn ($get) => in_array($get('chart_type'), ['bar', 'line'])),
                                
                                Toggle::make('show_tooltip')
                                    ->label('Show Tooltip')
                                    ->inline()
                                    ->default(true),
                                
                                Toggle::make('animate')
                                    ->label('Animate')
                                    ->inline()
                                    ->default(true),
                                
                                TextInput::make('poll_interval')
                                    ->label('Poll Interval (seconds)')
                                    ->numeric()
                                    ->placeholder('60'),
                                
                                TextInput::make('permission')
                                    ->label('Required Permission')
                                    ->placeholder('view_charts'),
                            ]),
                    ]),
                
                // Table Widget
                Block::make('table_widget')
                    ->label('Table Widget')
                    ->icon('heroicon-o-table-cells')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Widget Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('title')
                                        ->label('Widget Title')
                                        ->required(),
                                ]),
                                
                                Grid::make(3)->schema([
                                    Select::make('model')
                                        ->label('Data Model')
                                        ->options(fn () => [
                                            'App\\Models\\User' => 'Users',
                                            'App\\Models\\Order' => 'Orders',
                                            'Custom' => 'Custom Query',
                                        ])
                                        ->required(),
                                    
                                    TextInput::make('limit')
                                        ->label('Row Limit')
                                        ->numeric()
                                        ->default(5),
                                    
                                    Select::make('position')
                                        ->label('Position')
                                        ->options([
                                            'before_table' => 'Before Table',
                                            'after_table' => 'After Table',
                                            'dashboard' => 'Dashboard Only',
                                        ])
                                        ->default('dashboard'),
                                ]),
                                
                                Textarea::make('query')
                                    ->label('Custom Query')
                                    ->rows(3)
                                    ->placeholder("Model::query()->where('status', 'active')")
                                    ->helperText('Laravel Eloquent query'),
                            ]),
                        
                        Section::make('Columns Configuration')
                            ->schema([
                                Repeater::make('columns')
                                    ->label('Table Columns')
                                    ->schema([
                                        Grid::make(4)->schema([
                                            TextInput::make('field')
                                                ->label('Field')
                                                ->required(),
                                            
                                            TextInput::make('label')
                                                ->label('Label')
                                                ->required(),
                                            
                                            Select::make('type')
                                                ->label('Type')
                                                ->options([
                                                    'text' => 'Text',
                                                    'number' => 'Number',
                                                    'date' => 'Date',
                                                    'badge' => 'Badge',
                                                    'boolean' => 'Boolean',
                                                ])
                                                ->default('text'),
                                            
                                            TextInput::make('format')
                                                ->label('Format')
                                                ->placeholder('Y-m-d, currency'),
                                        ]),
                                    ])
                                    ->minItems(1)
                                    ->defaultItems(3)
                                    ->collapsible()
                                    ->collapsed(),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Toggle::make('searchable')
                                    ->label('Searchable')
                                    ->inline()
                                    ->default(false),
                                
                                Toggle::make('sortable')
                                    ->label('Sortable')
                                    ->inline()
                                    ->default(true),
                                
                                Toggle::make('paginated')
                                    ->label('Paginated')
                                    ->inline()
                                    ->default(false),
                                
                                Toggle::make('striped')
                                    ->label('Striped Rows')
                                    ->inline()
                                    ->default(true),
                                
                                TextInput::make('empty_state_message')
                                    ->label('Empty State Message')
                                    ->placeholder('No records found'),
                                
                                TextInput::make('empty_state_icon')
                                    ->label('Empty State Icon')
                                    ->placeholder('heroicon-o-x-circle'),
                                
                                TextInput::make('poll_interval')
                                    ->label('Poll Interval (seconds)')
                                    ->numeric(),
                                
                                TextInput::make('permission')
                                    ->label('Required Permission')
                                    ->placeholder('view_table_widget'),
                            ]),
                    ]),
                
                // Custom HTML Widget
                Block::make('html_widget')
                    ->label('Custom HTML Widget')
                    ->icon('heroicon-o-code-bracket')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Widget Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('title')
                                        ->label('Widget Title'),
                                ]),
                                
                                Grid::make(2)->schema([
                                    Select::make('position')
                                        ->label('Position')
                                        ->options([
                                            'before_table' => 'Before Table',
                                            'after_table' => 'After Table',
                                            'dashboard' => 'Dashboard Only',
                                        ])
                                        ->default('dashboard'),
                                    
                                    TextInput::make('sort_order')
                                        ->label('Sort Order')
                                        ->numeric()
                                        ->default(0),
                                ]),
                            ]),
                        
                        Section::make('Content')
                            ->schema([
                                Textarea::make('html_content')
                                    ->label('HTML Content')
                                    ->rows(10)
                                    ->required()
                                    ->placeholder('<div class="p-4">Your custom HTML here</div>'),
                                
                                Textarea::make('css_styles')
                                    ->label('Custom CSS')
                                    ->rows(5)
                                    ->placeholder('.custom-class { color: #333; }'),
                                
                                Textarea::make('javascript')
                                    ->label('JavaScript')
                                    ->rows(5)
                                    ->placeholder('// Custom JavaScript code'),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Toggle::make('use_blade')
                                    ->label('Use Blade Template')
                                    ->inline()
                                    ->default(false)
                                    ->helperText('Parse as Blade template with variables'),
                                
                                KeyValue::make('variables')
                                    ->label('Template Variables')
                                    ->keyLabel('Variable')
                                    ->valueLabel('Value')
                                    ->visible(fn ($get) => $get('use_blade')),
                                
                                TextInput::make('cache_duration')
                                    ->label('Cache Duration (minutes)')
                                    ->numeric()
                                    ->placeholder('60'),
                                
                                TextInput::make('permission')
                                    ->label('Required Permission')
                                    ->placeholder('view_custom_widget'),
                            ]),
                    ]),
                
                // Calendar Widget
                Block::make('calendar_widget')
                    ->label('Calendar Widget')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Widget Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('title')
                                        ->label('Widget Title')
                                        ->required(),
                                ]),
                                
                                Grid::make(3)->schema([
                                    Select::make('view')
                                        ->label('Default View')
                                        ->options([
                                            'month' => 'Month',
                                            'week' => 'Week',
                                            'day' => 'Day',
                                            'list' => 'List',
                                        ])
                                        ->default('month'),
                                    
                                    TextInput::make('height')
                                        ->label('Height (px)')
                                        ->numeric()
                                        ->default(400),
                                    
                                    Select::make('position')
                                        ->label('Position')
                                        ->options([
                                            'dashboard' => 'Dashboard Only',
                                            'resource_page' => 'Resource Page',
                                        ])
                                        ->default('dashboard'),
                                ]),
                            ]),
                        
                        Section::make('Events Configuration')
                            ->schema([
                                Select::make('event_source')
                                    ->label('Event Source')
                                    ->options([
                                        'model' => 'From Model',
                                        'api' => 'From API',
                                        'static' => 'Static Events',
                                    ])
                                    ->default('model')
                                    ->reactive(),
                                
                                Grid::make(2)->schema([
                                    TextInput::make('event_model')
                                        ->label('Event Model')
                                        ->placeholder('App\\Models\\Event')
                                        ->visible(fn ($get) => $get('event_source') === 'model'),
                                    
                                    TextInput::make('title_field')
                                        ->label('Title Field')
                                        ->placeholder('title')
                                        ->visible(fn ($get) => $get('event_source') === 'model'),
                                ]),
                                
                                Grid::make(2)->schema([
                                    TextInput::make('start_field')
                                        ->label('Start Date Field')
                                        ->placeholder('start_date')
                                        ->visible(fn ($get) => $get('event_source') === 'model'),
                                    
                                    TextInput::make('end_field')
                                        ->label('End Date Field')
                                        ->placeholder('end_date')
                                        ->visible(fn ($get) => $get('event_source') === 'model'),
                                ]),
                                
                                TextInput::make('color_field')
                                    ->label('Color Field')
                                    ->placeholder('category_color')
                                    ->visible(fn ($get) => $get('event_source') === 'model'),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Toggle::make('editable')
                                    ->label('Editable Events')
                                    ->inline()
                                    ->default(false),
                                
                                Toggle::make('draggable')
                                    ->label('Draggable Events')
                                    ->inline()
                                    ->default(false),
                                
                                Toggle::make('show_weekends')
                                    ->label('Show Weekends')
                                    ->inline()
                                    ->default(true),
                                
                                Select::make('first_day')
                                    ->label('First Day of Week')
                                    ->options([
                                        0 => 'Sunday',
                                        1 => 'Monday',
                                        6 => 'Saturday',
                                    ])
                                    ->default(0),
                                
                                TextInput::make('time_zone')
                                    ->label('Time Zone')
                                    ->placeholder('UTC')
                                    ->default('UTC'),
                                
                                TextInput::make('permission')
                                    ->label('Required Permission')
                                    ->placeholder('view_calendar'),
                            ]),
                    ]),
                
                // Progress Widget
                Block::make('progress_widget')
                    ->label('Progress Widget')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Widget Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('title')
                                        ->label('Widget Title')
                                        ->required(),
                                ]),
                                
                                Grid::make(3)->schema([
                                    Select::make('type')
                                        ->label('Progress Type')
                                        ->options([
                                            'bar' => 'Progress Bar',
                                            'circle' => 'Circular Progress',
                                            'steps' => 'Step Progress',
                                        ])
                                        ->default('bar'),
                                    
                                    TextInput::make('value_field')
                                        ->label('Value Field')
                                        ->required()
                                        ->placeholder('completion_percentage'),
                                    
                                    TextInput::make('max_value')
                                        ->label('Max Value')
                                        ->numeric()
                                        ->default(100),
                                ]),
                            ]),
                        
                        Section::make('Display Configuration')
                            ->schema([
                                Toggle::make('show_percentage')
                                    ->label('Show Percentage')
                                    ->inline()
                                    ->default(true),
                                
                                Toggle::make('show_label')
                                    ->label('Show Label')
                                    ->inline()
                                    ->default(true),
                                
                                TextInput::make('label_format')
                                    ->label('Label Format')
                                    ->placeholder('{value}% Complete')
                                    ->visible(fn ($get) => $get('show_label')),
                                
                                ColorPicker::make('color')
                                    ->label('Progress Color')
                                    ->default('#3b82f6'),
                                
                                ColorPicker::make('background_color')
                                    ->label('Background Color')
                                    ->default('#e5e7eb'),
                                
                                Toggle::make('animated')
                                    ->label('Animated')
                                    ->inline()
                                    ->default(true),
                                
                                Toggle::make('striped')
                                    ->label('Striped')
                                    ->inline()
                                    ->default(false)
                                    ->visible(fn ($get) => $get('type') === 'bar'),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                KeyValue::make('color_ranges')
                                    ->label('Color Ranges')
                                    ->keyLabel('Range (0-100)')
                                    ->valueLabel('Color')
                                    ->default([
                                        '0-30' => '#ef4444',
                                        '31-70' => '#f59e0b',
                                        '71-100' => '#10b981',
                                    ]),
                                
                                TextInput::make('height')
                                    ->label('Height (px)')
                                    ->numeric()
                                    ->default(20)
                                    ->visible(fn ($get) => $get('type') === 'bar'),
                                
                                TextInput::make('size')
                                    ->label('Size (px)')
                                    ->numeric()
                                    ->default(100)
                                    ->visible(fn ($get) => $get('type') === 'circle'),
                                
                                TextInput::make('stroke_width')
                                    ->label('Stroke Width')
                                    ->numeric()
                                    ->default(4)
                                    ->visible(fn ($get) => $get('type') === 'circle'),
                                
                                TextInput::make('poll_interval')
                                    ->label('Poll Interval (seconds)')
                                    ->numeric(),
                                
                                TextInput::make('permission')
                                    ->label('Required Permission')
                                    ->placeholder('view_progress'),
                            ]),
                    ]),
            ])
            ->addActionLabel('Add Widget')
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->blockNumbers(false)
            ->columnSpanFull();
    }
}