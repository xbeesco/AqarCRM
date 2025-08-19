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

class InteractionsBuilder
{
    public static function make(string $name = 'interactions'): Builder
    {
        return Builder::make($name)
            ->label('User Interactions')
            ->blocks([
                // Button Action
                Block::make('button_action')
                    ->label('Button Action')
                    ->icon('heroicon-o-cursor-arrow-rays')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->description('Configure button action and behavior')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Action Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/')
                                        ->placeholder('create_invoice, send_email'),
                                    
                                    TextInput::make('label')
                                        ->label('Button Label')
                                        ->required()
                                        ->placeholder('Create Invoice'),
                                    
                                    TextInput::make('icon')
                                        ->label('Button Icon')
                                        ->placeholder('heroicon-o-plus'),
                                ]),
                                
                                Grid::make(3)->schema([
                                    Select::make('type')
                                        ->label('Action Type')
                                        ->options([
                                            'form' => 'Open Form',
                                            'modal' => 'Open Modal',
                                            'url' => 'Navigate to URL',
                                            'download' => 'Download File',
                                            'api' => 'API Call',
                                            'livewire' => 'Livewire Action',
                                            'javascript' => 'JavaScript Function',
                                        ])
                                        ->required()
                                        ->reactive(),
                                    
                                    Select::make('color')
                                        ->label('Button Color')
                                        ->options([
                                            'primary' => 'Primary',
                                            'secondary' => 'Secondary',
                                            'success' => 'Success',
                                            'danger' => 'Danger',
                                            'warning' => 'Warning',
                                            'info' => 'Info',
                                        ])
                                        ->default('primary'),
                                    
                                    Select::make('size')
                                        ->label('Button Size')
                                        ->options([
                                            'xs' => 'Extra Small',
                                            'sm' => 'Small',
                                            'md' => 'Medium',
                                            'lg' => 'Large',
                                            'xl' => 'Extra Large',
                                        ])
                                        ->default('md'),
                                ]),
                                
                                Grid::make(4)->schema([
                                    Toggle::make('outlined')
                                        ->label('Outlined')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('disabled')
                                        ->label('Disabled')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('visible')
                                        ->label('Visible')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('requires_confirmation')
                                        ->label('Confirm')
                                        ->inline()
                                        ->default(false),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                // Modal Configuration
                                Grid::make(2)->schema([
                                    TextInput::make('modal_title')
                                        ->label('Modal Title')
                                        ->visible(fn ($get) => $get('type') === 'modal'),
                                    
                                    Select::make('modal_size')
                                        ->label('Modal Size')
                                        ->options([
                                            'sm' => 'Small',
                                            'md' => 'Medium',
                                            'lg' => 'Large',
                                            'xl' => 'Extra Large',
                                            '2xl' => '2XL',
                                            '3xl' => '3XL',
                                            '4xl' => '4XL',
                                            '5xl' => '5XL',
                                            'full' => 'Full Screen',
                                        ])
                                        ->default('lg')
                                        ->visible(fn ($get) => $get('type') === 'modal'),
                                ]),
                                
                                // URL Configuration
                                TextInput::make('url')
                                    ->label('Target URL')
                                    ->placeholder('/admin/create-invoice or https://example.com')
                                    ->visible(fn ($get) => in_array($get('type'), ['url', 'download'])),
                                
                                Toggle::make('open_in_new_tab')
                                    ->label('Open in New Tab')
                                    ->inline()
                                    ->default(false)
                                    ->visible(fn ($get) => $get('type') === 'url'),
                                
                                // API Configuration
                                Grid::make(2)->schema([
                                    Select::make('api_method')
                                        ->label('HTTP Method')
                                        ->options([
                                            'GET' => 'GET',
                                            'POST' => 'POST',
                                            'PUT' => 'PUT',
                                            'PATCH' => 'PATCH',
                                            'DELETE' => 'DELETE',
                                        ])
                                        ->default('POST')
                                        ->visible(fn ($get) => $get('type') === 'api'),
                                    
                                    TextInput::make('api_endpoint')
                                        ->label('API Endpoint')
                                        ->placeholder('/api/invoices')
                                        ->visible(fn ($get) => $get('type') === 'api'),
                                ]),
                                
                                // Confirmation Configuration
                                Grid::make(2)->schema([
                                    TextInput::make('confirmation_title')
                                        ->label('Confirmation Title')
                                        ->default('Are you sure?')
                                        ->visible(fn ($get) => $get('requires_confirmation')),
                                    
                                    Textarea::make('confirmation_message')
                                        ->label('Confirmation Message')
                                        ->rows(2)
                                        ->visible(fn ($get) => $get('requires_confirmation')),
                                ]),
                                
                                // Permissions and Conditions
                                TextInput::make('permission')
                                    ->label('Required Permission')
                                    ->placeholder('create_invoices'),
                                
                                Textarea::make('visible_condition')
                                    ->label('Visibility Condition')
                                    ->rows(2)
                                    ->placeholder("status === 'draft'")
                                    ->helperText('JavaScript expression'),
                                
                                Textarea::make('disabled_condition')
                                    ->label('Disabled Condition')
                                    ->rows(2)
                                    ->placeholder("amount <= 0")
                                    ->helperText('JavaScript expression'),
                                
                                KeyValue::make('data')
                                    ->label('Additional Data')
                                    ->keyLabel('Key')
                                    ->valueLabel('Value')
                                    ->default([]),
                            ]),
                    ]),
                
                // Bulk Action
                Block::make('bulk_action')
                    ->label('Bulk Action')
                    ->icon('heroicon-o-squares-2x2')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Action Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Action Label')
                                        ->required(),
                                    
                                    TextInput::make('icon')
                                        ->label('Action Icon')
                                        ->placeholder('heroicon-o-trash'),
                                ]),
                                
                                Grid::make(2)->schema([
                                    Select::make('action_type')
                                        ->label('Action Type')
                                        ->options([
                                            'delete' => 'Delete Records',
                                            'update' => 'Update Records',
                                            'export' => 'Export Records',
                                            'duplicate' => 'Duplicate Records',
                                            'api' => 'API Action',
                                            'custom' => 'Custom Action',
                                        ])
                                        ->required()
                                        ->reactive(),
                                    
                                    Select::make('color')
                                        ->label('Action Color')
                                        ->options([
                                            'primary' => 'Primary',
                                            'danger' => 'Danger',
                                            'warning' => 'Warning',
                                            'success' => 'Success',
                                        ])
                                        ->default('primary'),
                                ]),
                                
                                Toggle::make('requires_confirmation')
                                    ->label('Requires Confirmation')
                                    ->inline()
                                    ->default(true),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                // Update Configuration
                                Repeater::make('update_fields')
                                    ->label('Fields to Update')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('field')
                                                ->label('Field Name')
                                                ->required(),
                                            
                                            TextInput::make('value')
                                                ->label('New Value'),
                                            
                                            Select::make('type')
                                                ->label('Value Type')
                                                ->options([
                                                    'static' => 'Static Value',
                                                    'increment' => 'Increment',
                                                    'decrement' => 'Decrement',
                                                    'date' => 'Current Date',
                                                ])
                                                ->default('static'),
                                        ]),
                                    ])
                                    ->visible(fn ($get) => $get('action_type') === 'update')
                                    ->collapsed(),
                                
                                // Export Configuration
                                Grid::make(2)->schema([
                                    Select::make('export_format')
                                        ->label('Export Format')
                                        ->options([
                                            'csv' => 'CSV',
                                            'xlsx' => 'Excel',
                                            'pdf' => 'PDF',
                                            'json' => 'JSON',
                                        ])
                                        ->default('csv')
                                        ->visible(fn ($get) => $get('action_type') === 'export'),
                                    
                                    TextInput::make('export_filename')
                                        ->label('Filename Pattern')
                                        ->placeholder('export_{date}_{time}')
                                        ->visible(fn ($get) => $get('action_type') === 'export'),
                                ]),
                                
                                // Confirmation
                                TextInput::make('confirmation_title')
                                    ->label('Confirmation Title')
                                    ->default('Confirm Bulk Action')
                                    ->visible(fn ($get) => $get('requires_confirmation')),
                                
                                Textarea::make('confirmation_message')
                                    ->label('Confirmation Message')
                                    ->default('This action will affect {count} records.')
                                    ->rows(2)
                                    ->visible(fn ($get) => $get('requires_confirmation')),
                                
                                TextInput::make('success_message')
                                    ->label('Success Message')
                                    ->default('Successfully processed {count} records.'),
                                
                                TextInput::make('permission')
                                    ->label('Required Permission')
                                    ->placeholder('bulk_delete'),
                                
                                KeyValue::make('conditions')
                                    ->label('Action Conditions')
                                    ->keyLabel('Condition')
                                    ->valueLabel('Value')
                                    ->default([]),
                            ]),
                    ]),
                
                // Notification
                Block::make('notification')
                    ->label('Notification')
                    ->icon('heroicon-o-bell')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Notification Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('title')
                                        ->label('Notification Title')
                                        ->required(),
                                ]),
                                
                                Textarea::make('message')
                                    ->label('Notification Message')
                                    ->rows(2)
                                    ->required(),
                                
                                Grid::make(4)->schema([
                                    Select::make('type')
                                        ->label('Notification Type')
                                        ->options([
                                            'success' => 'Success',
                                            'info' => 'Info',
                                            'warning' => 'Warning',
                                            'danger' => 'Danger',
                                        ])
                                        ->default('info'),
                                    
                                    Select::make('position')
                                        ->label('Position')
                                        ->options([
                                            'top-right' => 'Top Right',
                                            'top-left' => 'Top Left',
                                            'bottom-right' => 'Bottom Right',
                                            'bottom-left' => 'Bottom Left',
                                            'top-center' => 'Top Center',
                                            'bottom-center' => 'Bottom Center',
                                        ])
                                        ->default('top-right'),
                                    
                                    TextInput::make('duration')
                                        ->label('Duration (ms)')
                                        ->numeric()
                                        ->default(3000),
                                    
                                    Toggle::make('persistent')
                                        ->label('Persistent')
                                        ->inline()
                                        ->default(false),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                TextInput::make('icon')
                                    ->label('Custom Icon')
                                    ->placeholder('heroicon-o-check-circle'),
                                
                                Toggle::make('closeable')
                                    ->label('Closeable')
                                    ->inline()
                                    ->default(true),
                                
                                Repeater::make('actions')
                                    ->label('Notification Actions')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('label')
                                                ->label('Action Label')
                                                ->required(),
                                            
                                            TextInput::make('url')
                                                ->label('Action URL'),
                                            
                                            Select::make('color')
                                                ->label('Color')
                                                ->options([
                                                    'primary' => 'Primary',
                                                    'secondary' => 'Secondary',
                                                ])
                                                ->default('primary'),
                                        ]),
                                    ])
                                    ->maxItems(2)
                                    ->collapsed(),
                                
                                TextInput::make('trigger_event')
                                    ->label('Trigger Event')
                                    ->placeholder('record.created, form.submitted'),
                                
                                KeyValue::make('data')
                                    ->label('Notification Data')
                                    ->default([]),
                            ]),
                    ]),
                
                // Filter
                Block::make('filter')
                    ->label('Filter')
                    ->icon('heroicon-o-funnel')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Filter Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Filter Label')
                                        ->required(),
                                    
                                    Select::make('type')
                                        ->label('Filter Type')
                                        ->options([
                                            'select' => 'Select Dropdown',
                                            'date' => 'Date Picker',
                                            'date_range' => 'Date Range',
                                            'number' => 'Number Input',
                                            'number_range' => 'Number Range',
                                            'text' => 'Text Search',
                                            'boolean' => 'Yes/No Toggle',
                                            'checkbox' => 'Multiple Checkboxes',
                                        ])
                                        ->required()
                                        ->reactive(),
                                ]),
                                
                                TextInput::make('field')
                                    ->label('Database Field')
                                    ->required()
                                    ->placeholder('status, created_at, price'),
                            ]),
                        
                        Section::make('Filter Options')
                            ->schema([
                                // Select Options
                                KeyValue::make('options')
                                    ->label('Filter Options')
                                    ->keyLabel('Value')
                                    ->valueLabel('Label')
                                    ->visible(fn ($get) => in_array($get('type'), ['select', 'checkbox']))
                                    ->default([]),
                                
                                // Date Configuration
                                Grid::make(2)->schema([
                                    TextInput::make('date_format')
                                        ->label('Date Format')
                                        ->default('Y-m-d')
                                        ->visible(fn ($get) => str_contains($get('type'), 'date')),
                                    
                                    Toggle::make('show_time')
                                        ->label('Include Time')
                                        ->inline()
                                        ->default(false)
                                        ->visible(fn ($get) => str_contains($get('type'), 'date')),
                                ]),
                                
                                // Number Configuration
                                Grid::make(3)->schema([
                                    TextInput::make('min')
                                        ->label('Minimum Value')
                                        ->numeric()
                                        ->visible(fn ($get) => str_contains($get('type'), 'number')),
                                    
                                    TextInput::make('max')
                                        ->label('Maximum Value')
                                        ->numeric()
                                        ->visible(fn ($get) => str_contains($get('type'), 'number')),
                                    
                                    TextInput::make('step')
                                        ->label('Step')
                                        ->numeric()
                                        ->default(1)
                                        ->visible(fn ($get) => str_contains($get('type'), 'number')),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Select::make('operator')
                                    ->label('Filter Operator')
                                    ->options([
                                        '=' => 'Equals',
                                        '!=' => 'Not Equals',
                                        '>' => 'Greater Than',
                                        '>=' => 'Greater Than or Equal',
                                        '<' => 'Less Than',
                                        '<=' => 'Less Than or Equal',
                                        'like' => 'Contains',
                                        'not_like' => 'Does Not Contain',
                                        'in' => 'In List',
                                        'not_in' => 'Not In List',
                                        'between' => 'Between',
                                        'is_null' => 'Is Null',
                                        'is_not_null' => 'Is Not Null',
                                    ])
                                    ->default('='),
                                
                                Toggle::make('multiple')
                                    ->label('Allow Multiple Selection')
                                    ->inline()
                                    ->default(false)
                                    ->visible(fn ($get) => $get('type') === 'select'),
                                
                                Toggle::make('searchable')
                                    ->label('Searchable')
                                    ->inline()
                                    ->default(false)
                                    ->visible(fn ($get) => $get('type') === 'select'),
                                
                                TextInput::make('placeholder')
                                    ->label('Placeholder Text')
                                    ->placeholder('Select an option...'),
                                
                                TextInput::make('default_value')
                                    ->label('Default Value'),
                                
                                Toggle::make('active_by_default')
                                    ->label('Active by Default')
                                    ->inline()
                                    ->default(false),
                            ]),
                    ]),
                
                // Search
                Block::make('search')
                    ->label('Search')
                    ->icon('heroicon-o-magnifying-glass')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Search Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('placeholder')
                                        ->label('Placeholder Text')
                                        ->default('Search...'),
                                ]),
                                
                                Repeater::make('searchable_fields')
                                    ->label('Searchable Fields')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('field')
                                                ->label('Field Name')
                                                ->required(),
                                            
                                            TextInput::make('label')
                                                ->label('Display Label'),
                                            
                                            Select::make('weight')
                                                ->label('Search Weight')
                                                ->options([
                                                    '1' => 'Low',
                                                    '5' => 'Medium',
                                                    '10' => 'High',
                                                ])
                                                ->default('5'),
                                        ]),
                                    ])
                                    ->minItems(1)
                                    ->defaultItems(2),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Select::make('search_type')
                                    ->label('Search Type')
                                    ->options([
                                        'contains' => 'Contains',
                                        'starts_with' => 'Starts With',
                                        'ends_with' => 'Ends With',
                                        'exact' => 'Exact Match',
                                        'fuzzy' => 'Fuzzy Search',
                                        'fulltext' => 'Full Text Search',
                                    ])
                                    ->default('contains'),
                                
                                Toggle::make('realtime')
                                    ->label('Realtime Search')
                                    ->inline()
                                    ->default(true),
                                
                                TextInput::make('debounce')
                                    ->label('Debounce (ms)')
                                    ->numeric()
                                    ->default(300)
                                    ->visible(fn ($get) => $get('realtime')),
                                
                                TextInput::make('min_characters')
                                    ->label('Minimum Characters')
                                    ->numeric()
                                    ->default(2),
                                
                                Toggle::make('highlight_results')
                                    ->label('Highlight Results')
                                    ->inline()
                                    ->default(true),
                                
                                Toggle::make('show_count')
                                    ->label('Show Result Count')
                                    ->inline()
                                    ->default(false),
                                
                                Toggle::make('case_sensitive')
                                    ->label('Case Sensitive')
                                    ->inline()
                                    ->default(false),
                            ]),
                    ]),
                
                // Export
                Block::make('export')
                    ->label('Export')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Export Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Export Label')
                                        ->required()
                                        ->default('Export'),
                                    
                                    Select::make('format')
                                        ->label('Export Format')
                                        ->options([
                                            'csv' => 'CSV',
                                            'xlsx' => 'Excel',
                                            'pdf' => 'PDF',
                                            'json' => 'JSON',
                                            'xml' => 'XML',
                                        ])
                                        ->required()
                                        ->reactive(),
                                ]),
                                
                                TextInput::make('filename')
                                    ->label('Filename Pattern')
                                    ->placeholder('export_{date}_{time}')
                                    ->default('export_{date}'),
                            ]),
                        
                        Section::make('Export Configuration')
                            ->schema([
                                Repeater::make('columns')
                                    ->label('Export Columns')
                                    ->schema([
                                        Grid::make(4)->schema([
                                            TextInput::make('field')
                                                ->label('Field')
                                                ->required(),
                                            
                                            TextInput::make('label')
                                                ->label('Column Header')
                                                ->required(),
                                            
                                            Select::make('format')
                                                ->label('Format')
                                                ->options([
                                                    'text' => 'Text',
                                                    'number' => 'Number',
                                                    'currency' => 'Currency',
                                                    'date' => 'Date',
                                                    'boolean' => 'Yes/No',
                                                ])
                                                ->default('text'),
                                            
                                            Toggle::make('included')
                                                ->label('Include')
                                                ->inline()
                                                ->default(true),
                                        ]),
                                    ])
                                    ->minItems(1)
                                    ->defaultItems(3),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Toggle::make('include_headers')
                                    ->label('Include Headers')
                                    ->inline()
                                    ->default(true),
                                
                                Toggle::make('include_timestamps')
                                    ->label('Include Timestamps')
                                    ->inline()
                                    ->default(false),
                                
                                Select::make('delimiter')
                                    ->label('CSV Delimiter')
                                    ->options([
                                        ',' => 'Comma (,)',
                                        ';' => 'Semicolon (;)',
                                        '\t' => 'Tab',
                                        '|' => 'Pipe (|)',
                                    ])
                                    ->default(',')
                                    ->visible(fn ($get) => $get('format') === 'csv'),
                                
                                Select::make('encoding')
                                    ->label('File Encoding')
                                    ->options([
                                        'UTF-8' => 'UTF-8',
                                        'UTF-16' => 'UTF-16',
                                        'ISO-8859-1' => 'ISO-8859-1',
                                    ])
                                    ->default('UTF-8'),
                                
                                TextInput::make('max_rows')
                                    ->label('Maximum Rows')
                                    ->numeric()
                                    ->placeholder('No limit'),
                                
                                Toggle::make('compress')
                                    ->label('Compress File (ZIP)')
                                    ->inline()
                                    ->default(false),
                                
                                TextInput::make('permission')
                                    ->label('Required Permission')
                                    ->placeholder('export_data'),
                            ]),
                    ]),
                
                // Print
                Block::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Print Action Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Print Button Label')
                                        ->required()
                                        ->default('Print'),
                                ]),
                                
                                Grid::make(3)->schema([
                                    Select::make('orientation')
                                        ->label('Page Orientation')
                                        ->options([
                                            'portrait' => 'Portrait',
                                            'landscape' => 'Landscape',
                                        ])
                                        ->default('portrait'),
                                    
                                    Select::make('size')
                                        ->label('Paper Size')
                                        ->options([
                                            'A4' => 'A4',
                                            'A3' => 'A3',
                                            'Letter' => 'Letter',
                                            'Legal' => 'Legal',
                                        ])
                                        ->default('A4'),
                                    
                                    Select::make('margins')
                                        ->label('Margins')
                                        ->options([
                                            'normal' => 'Normal',
                                            'narrow' => 'Narrow',
                                            'wide' => 'Wide',
                                            'none' => 'None',
                                        ])
                                        ->default('normal'),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Textarea::make('header_html')
                                    ->label('Header HTML')
                                    ->rows(3)
                                    ->placeholder('<h1>{title}</h1>'),
                                
                                Textarea::make('footer_html')
                                    ->label('Footer HTML')
                                    ->rows(2)
                                    ->placeholder('<p>Page {page} of {total}</p>'),
                                
                                Toggle::make('include_backgrounds')
                                    ->label('Include Backgrounds')
                                    ->inline()
                                    ->default(false),
                                
                                Toggle::make('include_images')
                                    ->label('Include Images')
                                    ->inline()
                                    ->default(true),
                                
                                Toggle::make('page_numbers')
                                    ->label('Show Page Numbers')
                                    ->inline()
                                    ->default(true),
                                
                                TextInput::make('css_file')
                                    ->label('Custom CSS File')
                                    ->placeholder('print.css'),
                            ]),
                    ]),
            ])
            ->addActionLabel('Add Interaction')
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->blockNumbers(false)
            ->columnSpanFull();
    }
}