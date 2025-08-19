<?php

namespace App\Filament\Forms\Components\Module\Screens;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\ColorPicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class TableColumnsBuilderAdvanced
{
    public static function make(string $name = 'columns'): Builder
    {
        return Builder::make($name)
            ->label('Table Columns')
            ->blocks([
                // Text Column
                Block::make('text_column')
                    ->label('Text Column')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->description('Essential column settings')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Column Name')
                                        ->required()
                                        ->placeholder('field_name')
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required()
                                        ->placeholder('Column Title'),
                                    
                                    Select::make('alignment')
                                        ->label('Alignment')
                                        ->options([
                                            'left' => 'Left',
                                            'center' => 'Center',
                                            'right' => 'Right',
                                            'justify' => 'Justify',
                                        ])
                                        ->default('left'),
                                ]),
                                
                                Grid::make(4)->schema([
                                    Toggle::make('sortable')
                                        ->label('Sortable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('searchable')
                                        ->label('Searchable')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('toggleable')
                                        ->label('Toggleable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('copyable')
                                        ->label('Copyable')
                                        ->inline()
                                        ->default(false),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('placeholder')
                                        ->label('Empty State Text')
                                        ->placeholder('N/A or -'),
                                    
                                    TextInput::make('width')
                                        ->label('Column Width')
                                        ->placeholder('auto, 100px, 20%'),
                                ]),
                                
                                Grid::make(3)->schema([
                                    TextInput::make('limit')
                                        ->label('Character Limit')
                                        ->numeric()
                                        ->placeholder('50'),
                                    
                                    TextInput::make('words')
                                        ->label('Word Limit')
                                        ->numeric()
                                        ->placeholder('10'),
                                    
                                    TextInput::make('line_clamp')
                                        ->label('Line Clamp')
                                        ->numeric()
                                        ->placeholder('2'),
                                ]),
                                
                                Grid::make(4)->schema([
                                    Toggle::make('wrap')
                                        ->label('Wrap Text')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('html')
                                        ->label('HTML Content')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('markdown')
                                        ->label('Markdown')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('tooltip')
                                        ->label('Show Tooltip')
                                        ->inline()
                                        ->default(false),
                                ]),
                                
                                Select::make('weight')
                                    ->label('Font Weight')
                                    ->options([
                                        'thin' => 'Thin',
                                        'light' => 'Light',
                                        'normal' => 'Normal',
                                        'medium' => 'Medium',
                                        'semibold' => 'Semibold',
                                        'bold' => 'Bold',
                                        'extrabold' => 'Extra Bold',
                                    ])
                                    ->default('normal'),
                                
                                Select::make('size')
                                    ->label('Text Size')
                                    ->options([
                                        'xs' => 'Extra Small',
                                        'sm' => 'Small',
                                        'base' => 'Base',
                                        'lg' => 'Large',
                                        'xl' => 'Extra Large',
                                    ])
                                    ->default('base'),
                                
                                TextInput::make('prefix')
                                    ->label('Prefix Text')
                                    ->placeholder('$, #, @'),
                                
                                TextInput::make('suffix')
                                    ->label('Suffix Text')
                                    ->placeholder('USD, %'),
                                
                                KeyValue::make('extra_attributes')
                                    ->label('Extra Attributes')
                                    ->keyLabel('Attribute')
                                    ->valueLabel('Value'),
                            ]),
                    ]),
                
                // Number Column
                Block::make('number_column')
                    ->label('Number Column')
                    ->icon('heroicon-o-hashtag')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Column Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                    
                                    Select::make('format')
                                        ->label('Number Format')
                                        ->options([
                                            'number' => 'Number',
                                            'decimal' => 'Decimal',
                                            'percentage' => 'Percentage',
                                            'currency' => 'Currency',
                                            'scientific' => 'Scientific',
                                        ])
                                        ->default('number')
                                        ->reactive(),
                                ]),
                                
                                Grid::make(4)->schema([
                                    Toggle::make('sortable')
                                        ->label('Sortable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('searchable')
                                        ->label('Searchable')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('toggleable')
                                        ->label('Toggleable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('summarizable')
                                        ->label('Summarizable')
                                        ->inline()
                                        ->default(false),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('decimal_places')
                                        ->label('Decimal Places')
                                        ->numeric()
                                        ->default(2)
                                        ->minValue(0)
                                        ->maxValue(10),
                                    
                                    TextInput::make('thousands_separator')
                                        ->label('Thousands Separator')
                                        ->default(',')
                                        ->maxLength(1),
                                    
                                    TextInput::make('decimal_separator')
                                        ->label('Decimal Separator')
                                        ->default('.')
                                        ->maxLength(1),
                                ]),
                                
                                Grid::make(2)->schema([
                                    Select::make('currency')
                                        ->label('Currency')
                                        ->options([
                                            'USD' => 'USD',
                                            'EUR' => 'EUR',
                                            'GBP' => 'GBP',
                                            'SAR' => 'SAR',
                                            'AED' => 'AED',
                                        ])
                                        ->visible(fn ($get) => $get('format') === 'currency'),
                                    
                                    Select::make('currency_position')
                                        ->label('Currency Position')
                                        ->options([
                                            'before' => 'Before',
                                            'after' => 'After',
                                        ])
                                        ->default('before')
                                        ->visible(fn ($get) => $get('format') === 'currency'),
                                ]),
                                
                                Select::make('summary_type')
                                    ->label('Summary Type')
                                    ->options([
                                        'sum' => 'Sum',
                                        'average' => 'Average',
                                        'count' => 'Count',
                                        'min' => 'Minimum',
                                        'max' => 'Maximum',
                                    ])
                                    ->multiple()
                                    ->visible(fn ($get) => $get('summarizable')),
                                
                                TextInput::make('width')
                                    ->label('Column Width')
                                    ->placeholder('100px'),
                                
                                Select::make('alignment')
                                    ->label('Alignment')
                                    ->options([
                                        'left' => 'Left',
                                        'center' => 'Center',
                                        'right' => 'Right',
                                    ])
                                    ->default('right'),
                                
                                KeyValue::make('extra_attributes')
                                    ->label('Extra Attributes'),
                            ]),
                    ]),
                
                // Date/DateTime Column
                Block::make('date_column')
                    ->label('Date/DateTime Column')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Column Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                    
                                    Select::make('type')
                                        ->label('Date Type')
                                        ->options([
                                            'date' => 'Date Only',
                                            'datetime' => 'Date & Time',
                                            'time' => 'Time Only',
                                            'since' => 'Time Ago',
                                            'diff' => 'Difference',
                                        ])
                                        ->default('date')
                                        ->reactive(),
                                ]),
                                
                                Grid::make(3)->schema([
                                    Toggle::make('sortable')
                                        ->label('Sortable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('searchable')
                                        ->label('Searchable')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('toggleable')
                                        ->label('Toggleable')
                                        ->inline()
                                        ->default(true),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                TextInput::make('format')
                                    ->label('Date Format')
                                    ->placeholder('Y-m-d H:i:s')
                                    ->default('Y-m-d')
                                    ->helperText('PHP date format'),
                                
                                Select::make('timezone')
                                    ->label('Timezone')
                                    ->options([
                                        'UTC' => 'UTC',
                                        'user' => 'User Timezone',
                                        'Asia/Riyadh' => 'Riyadh',
                                        'Asia/Dubai' => 'Dubai',
                                        'Europe/London' => 'London',
                                        'America/New_York' => 'New York',
                                    ])
                                    ->default('UTC'),
                                
                                Toggle::make('human_readable')
                                    ->label('Human Readable')
                                    ->inline()
                                    ->default(false)
                                    ->helperText('Shows dates like "2 hours ago"'),
                                
                                TextInput::make('tooltip_format')
                                    ->label('Tooltip Format')
                                    ->placeholder('F j, Y g:i A')
                                    ->helperText('Format for hover tooltip'),
                                
                                TextInput::make('width')
                                    ->label('Column Width')
                                    ->placeholder('150px'),
                                
                                Select::make('alignment')
                                    ->label('Alignment')
                                    ->options([
                                        'left' => 'Left',
                                        'center' => 'Center',
                                        'right' => 'Right',
                                    ])
                                    ->default('left'),
                                
                                KeyValue::make('extra_attributes')
                                    ->label('Extra Attributes'),
                            ]),
                    ]),
                
                // Badge Column
                Block::make('badge_column')
                    ->label('Badge Column')
                    ->icon('heroicon-o-tag')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Column Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                ]),
                                
                                KeyValue::make('options')
                                    ->label('Badge Options')
                                    ->keyLabel('Value')
                                    ->valueLabel('Display Label')
                                    ->required()
                                    ->addActionLabel('Add Option'),
                                
                                Grid::make(3)->schema([
                                    Toggle::make('sortable')
                                        ->label('Sortable')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('searchable')
                                        ->label('Searchable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('toggleable')
                                        ->label('Toggleable')
                                        ->inline()
                                        ->default(true),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                KeyValue::make('colors')
                                    ->label('Badge Colors')
                                    ->keyLabel('Value')
                                    ->valueLabel('Color')
                                    ->helperText('success, danger, warning, info, primary, secondary, gray'),
                                
                                KeyValue::make('icons')
                                    ->label('Badge Icons')
                                    ->keyLabel('Value')
                                    ->valueLabel('Icon')
                                    ->helperText('heroicon-o-check, heroicon-o-x-mark'),
                                
                                Select::make('size')
                                    ->label('Badge Size')
                                    ->options([
                                        'xs' => 'Extra Small',
                                        'sm' => 'Small',
                                        'md' => 'Medium',
                                        'lg' => 'Large',
                                    ])
                                    ->default('md'),
                                
                                Toggle::make('pill')
                                    ->label('Pill Style')
                                    ->inline()
                                    ->default(true),
                                
                                TextInput::make('width')
                                    ->label('Column Width')
                                    ->placeholder('120px'),
                                
                                Select::make('alignment')
                                    ->label('Alignment')
                                    ->options([
                                        'left' => 'Left',
                                        'center' => 'Center',
                                        'right' => 'Right',
                                    ])
                                    ->default('center'),
                                
                                KeyValue::make('extra_attributes')
                                    ->label('Extra Attributes'),
                            ]),
                    ]),
                
                // Icon Column
                Block::make('icon_column')
                    ->label('Icon Column')
                    ->icon('heroicon-o-face-smile')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Column Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                    
                                    Select::make('type')
                                        ->label('Icon Type')
                                        ->options([
                                            'boolean' => 'Boolean (True/False)',
                                            'options' => 'Multiple Options',
                                        ])
                                        ->default('boolean')
                                        ->reactive(),
                                ]),
                                
                                Grid::make(2)->schema([
                                    Toggle::make('sortable')
                                        ->label('Sortable')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('toggleable')
                                        ->label('Toggleable')
                                        ->inline()
                                        ->default(true),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('true_icon')
                                        ->label('True Icon')
                                        ->default('heroicon-o-check-circle')
                                        ->visible(fn ($get) => $get('type') === 'boolean'),
                                    
                                    TextInput::make('false_icon')
                                        ->label('False Icon')
                                        ->default('heroicon-o-x-circle')
                                        ->visible(fn ($get) => $get('type') === 'boolean'),
                                ]),
                                
                                Grid::make(2)->schema([
                                    ColorPicker::make('true_color')
                                        ->label('True Color')
                                        ->default('#10b981')
                                        ->visible(fn ($get) => $get('type') === 'boolean'),
                                    
                                    ColorPicker::make('false_color')
                                        ->label('False Color')
                                        ->default('#ef4444')
                                        ->visible(fn ($get) => $get('type') === 'boolean'),
                                ]),
                                
                                KeyValue::make('icon_options')
                                    ->label('Icon Options')
                                    ->keyLabel('Value')
                                    ->valueLabel('Icon')
                                    ->visible(fn ($get) => $get('type') === 'options'),
                                
                                KeyValue::make('color_options')
                                    ->label('Color Options')
                                    ->keyLabel('Value')
                                    ->valueLabel('Color')
                                    ->visible(fn ($get) => $get('type') === 'options'),
                                
                                Select::make('size')
                                    ->label('Icon Size')
                                    ->options([
                                        'sm' => 'Small',
                                        'md' => 'Medium',
                                        'lg' => 'Large',
                                        'xl' => 'Extra Large',
                                    ])
                                    ->default('md'),
                                
                                Toggle::make('show_tooltip')
                                    ->label('Show Tooltip')
                                    ->inline()
                                    ->default(true),
                                
                                TextInput::make('width')
                                    ->label('Column Width')
                                    ->placeholder('80px'),
                                
                                Select::make('alignment')
                                    ->label('Alignment')
                                    ->options([
                                        'left' => 'Left',
                                        'center' => 'Center',
                                        'right' => 'Right',
                                    ])
                                    ->default('center'),
                            ]),
                    ]),
                
                // Image Column
                Block::make('image_column')
                    ->label('Image Column')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Column Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                ]),
                                
                                Grid::make(3)->schema([
                                    Toggle::make('circular')
                                        ->label('Circular')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('stacked')
                                        ->label('Stacked')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('toggleable')
                                        ->label('Toggleable')
                                        ->inline()
                                        ->default(true),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('width')
                                        ->label('Image Width')
                                        ->numeric()
                                        ->default(40)
                                        ->placeholder('40'),
                                    
                                    TextInput::make('height')
                                        ->label('Image Height')
                                        ->numeric()
                                        ->default(40)
                                        ->placeholder('40'),
                                    
                                    TextInput::make('limit')
                                        ->label('Stack Limit')
                                        ->numeric()
                                        ->default(3)
                                        ->visible(fn ($get) => $get('stacked')),
                                ]),
                                
                                TextInput::make('default_image')
                                    ->label('Default Image URL')
                                    ->placeholder('/images/placeholder.png'),
                                
                                Select::make('disk')
                                    ->label('Storage Disk')
                                    ->options([
                                        'public' => 'Public',
                                        'local' => 'Local',
                                        's3' => 'S3',
                                    ])
                                    ->default('public'),
                                
                                Toggle::make('check_existence')
                                    ->label('Check File Existence')
                                    ->inline()
                                    ->default(true),
                                
                                Select::make('visibility')
                                    ->label('Visibility')
                                    ->options([
                                        'public' => 'Public',
                                        'private' => 'Private',
                                    ])
                                    ->default('public'),
                                
                                TextInput::make('column_width')
                                    ->label('Column Width')
                                    ->placeholder('100px'),
                                
                                Select::make('alignment')
                                    ->label('Alignment')
                                    ->options([
                                        'left' => 'Left',
                                        'center' => 'Center',
                                        'right' => 'Right',
                                    ])
                                    ->default('center'),
                            ]),
                    ]),
                
                // Toggle Column
                Block::make('toggle_column')
                    ->label('Toggle Column')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Column Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                ]),
                                
                                Grid::make(3)->schema([
                                    Toggle::make('updateable')
                                        ->label('Updateable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('disabled')
                                        ->label('Disabled')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('toggleable')
                                        ->label('Column Toggleable')
                                        ->inline()
                                        ->default(true),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('on_icon')
                                        ->label('On Icon')
                                        ->default('heroicon-o-check'),
                                    
                                    TextInput::make('off_icon')
                                        ->label('Off Icon')
                                        ->default('heroicon-o-x-mark'),
                                ]),
                                
                                Grid::make(2)->schema([
                                    ColorPicker::make('on_color')
                                        ->label('On Color')
                                        ->default('#10b981'),
                                    
                                    ColorPicker::make('off_color')
                                        ->label('Off Color')
                                        ->default('#6b7280'),
                                ]),
                                
                                TextInput::make('confirmation_title')
                                    ->label('Confirmation Title')
                                    ->placeholder('Are you sure?'),
                                
                                Textarea::make('confirmation_message')
                                    ->label('Confirmation Message')
                                    ->rows(2)
                                    ->placeholder('This action will update the status'),
                                
                                TextInput::make('width')
                                    ->label('Column Width')
                                    ->placeholder('100px'),
                                
                                Select::make('alignment')
                                    ->label('Alignment')
                                    ->options([
                                        'left' => 'Left',
                                        'center' => 'Center',
                                        'right' => 'Right',
                                    ])
                                    ->default('center'),
                            ]),
                    ]),
                
                // Select Column
                Block::make('select_column')
                    ->label('Select Column')
                    ->icon('heroicon-o-chevron-down')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Column Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                ]),
                                
                                KeyValue::make('options')
                                    ->label('Select Options')
                                    ->keyLabel('Value')
                                    ->valueLabel('Display Label')
                                    ->required()
                                    ->addActionLabel('Add Option'),
                                
                                Grid::make(3)->schema([
                                    Toggle::make('updateable')
                                        ->label('Updateable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('disabled')
                                        ->label('Disabled')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('toggleable')
                                        ->label('Column Toggleable')
                                        ->inline()
                                        ->default(true),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                TextInput::make('placeholder')
                                    ->label('Placeholder')
                                    ->placeholder('Select an option'),
                                
                                Toggle::make('selectable_placeholder')
                                    ->label('Selectable Placeholder')
                                    ->inline()
                                    ->default(false),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|in:option1,option2'),
                                
                                TextInput::make('width')
                                    ->label('Column Width')
                                    ->placeholder('150px'),
                                
                                Select::make('alignment')
                                    ->label('Alignment')
                                    ->options([
                                        'left' => 'Left',
                                        'center' => 'Center',
                                        'right' => 'Right',
                                    ])
                                    ->default('left'),
                                
                                KeyValue::make('extra_attributes')
                                    ->label('Extra Attributes'),
                            ]),
                    ]),
                
                // Color Column
                Block::make('color_column')
                    ->label('Color Column')
                    ->icon('heroicon-o-swatch')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Column Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                ]),
                                
                                Grid::make(3)->schema([
                                    Toggle::make('copyable')
                                        ->label('Copyable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('toggleable')
                                        ->label('Toggleable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('show_hex')
                                        ->label('Show Hex Value')
                                        ->inline()
                                        ->default(true),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Select::make('shape')
                                    ->label('Shape')
                                    ->options([
                                        'square' => 'Square',
                                        'circle' => 'Circle',
                                        'rounded' => 'Rounded',
                                    ])
                                    ->default('square'),
                                
                                Grid::make(2)->schema([
                                    TextInput::make('size')
                                        ->label('Size (px)')
                                        ->numeric()
                                        ->default(20),
                                    
                                    TextInput::make('border_width')
                                        ->label('Border Width')
                                        ->numeric()
                                        ->default(1),
                                ]),
                                
                                ColorPicker::make('border_color')
                                    ->label('Border Color')
                                    ->default('#e5e7eb'),
                                
                                TextInput::make('copy_message')
                                    ->label('Copy Success Message')
                                    ->placeholder('Color copied to clipboard!'),
                                
                                TextInput::make('width')
                                    ->label('Column Width')
                                    ->placeholder('100px'),
                                
                                Select::make('alignment')
                                    ->label('Alignment')
                                    ->options([
                                        'left' => 'Left',
                                        'center' => 'Center',
                                        'right' => 'Right',
                                    ])
                                    ->default('center'),
                            ]),
                    ]),
                
                // Link/Action Column
                Block::make('link_column')
                    ->label('Link/Action Column')
                    ->icon('heroicon-o-link')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Column Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                    
                                    Select::make('type')
                                        ->label('Link Type')
                                        ->options([
                                            'url' => 'External URL',
                                            'route' => 'Internal Route',
                                            'action' => 'Action',
                                            'email' => 'Email',
                                            'tel' => 'Phone',
                                        ])
                                        ->default('url')
                                        ->reactive(),
                                ]),
                                
                                Grid::make(3)->schema([
                                    Toggle::make('open_in_new_tab')
                                        ->label('New Tab')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('icon_position')
                                        ->label('Show Icon')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('toggleable')
                                        ->label('Toggleable')
                                        ->inline()
                                        ->default(true),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                TextInput::make('route_name')
                                    ->label('Route Name')
                                    ->placeholder('posts.edit')
                                    ->visible(fn ($get) => $get('type') === 'route'),
                                
                                TextInput::make('route_parameters')
                                    ->label('Route Parameters')
                                    ->placeholder('id, slug')
                                    ->visible(fn ($get) => $get('type') === 'route'),
                                
                                TextInput::make('url_pattern')
                                    ->label('URL Pattern')
                                    ->placeholder('https://example.com/{id}')
                                    ->visible(fn ($get) => $get('type') === 'url'),
                                
                                TextInput::make('icon')
                                    ->label('Icon')
                                    ->placeholder('heroicon-o-arrow-top-right-on-square'),
                                
                                ColorPicker::make('color')
                                    ->label('Link Color')
                                    ->default('#3b82f6'),
                                
                                Select::make('weight')
                                    ->label('Font Weight')
                                    ->options([
                                        'normal' => 'Normal',
                                        'medium' => 'Medium',
                                        'semibold' => 'Semibold',
                                        'bold' => 'Bold',
                                    ])
                                    ->default('medium'),
                                
                                Toggle::make('underline')
                                    ->label('Underline')
                                    ->inline()
                                    ->default(false),
                                
                                TextInput::make('width')
                                    ->label('Column Width')
                                    ->placeholder('auto'),
                                
                                Select::make('alignment')
                                    ->label('Alignment')
                                    ->options([
                                        'left' => 'Left',
                                        'center' => 'Center',
                                        'right' => 'Right',
                                    ])
                                    ->default('left'),
                            ]),
                    ]),
            ])
            ->addActionLabel('Add Table Column')
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->blockNumbers(false)
            ->columnSpanFull();
    }
}