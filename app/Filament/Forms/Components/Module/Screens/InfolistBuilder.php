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
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class InfolistBuilder
{
    public static function make(string $name = 'infolist'): Builder
    {
        return Builder::make($name)
            ->label('Infolist Configuration')
            ->blocks([
                // Text Entry
                Block::make('text_entry')
                    ->label('Text Entry')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->description('Display text information')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('field')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/')
                                        ->placeholder('name, title, description'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required()
                                        ->placeholder('Full Name'),
                                    
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
                                ]),
                                
                                Grid::make(4)->schema([
                                    Toggle::make('inline')
                                        ->label('Inline Label')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('copyable')
                                        ->label('Copyable')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('html')
                                        ->label('Allow HTML')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('markdown')
                                        ->label('Parse Markdown')
                                        ->inline()
                                        ->default(false),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Grid::make(3)->schema([
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
                                    
                                    Select::make('font_family')
                                        ->label('Font Family')
                                        ->options([
                                            'sans' => 'Sans Serif',
                                            'serif' => 'Serif',
                                            'mono' => 'Monospace',
                                        ])
                                        ->default('sans'),
                                    
                                    ColorPicker::make('color')
                                        ->label('Text Color'),
                                ]),
                                
                                Grid::make(2)->schema([
                                    TextInput::make('limit')
                                        ->label('Character Limit')
                                        ->numeric()
                                        ->placeholder('200'),
                                    
                                    TextInput::make('tooltip')
                                        ->label('Tooltip Text')
                                        ->placeholder('Additional information'),
                                ]),
                                
                                TextInput::make('placeholder')
                                    ->label('Empty State Text')
                                    ->placeholder('N/A, Not set, -'),
                                
                                TextInput::make('format')
                                    ->label('Format String')
                                    ->placeholder('The value is: {value}')
                                    ->helperText('Use {value} as placeholder'),
                                
                                Select::make('badge_color')
                                    ->label('Badge Color')
                                    ->options([
                                        'primary' => 'Primary',
                                        'secondary' => 'Secondary',
                                        'success' => 'Success',
                                        'danger' => 'Danger',
                                        'warning' => 'Warning',
                                        'info' => 'Info',
                                    ])
                                    ->placeholder('None'),
                                
                                KeyValue::make('state_formatting')
                                    ->label('State-based Formatting')
                                    ->keyLabel('Value')
                                    ->valueLabel('Color/Style')
                                    ->default([]),
                            ]),
                    ]),
                
                // Icon Entry
                Block::make('icon_entry')
                    ->label('Icon Entry')
                    ->icon('heroicon-o-sparkles')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('field')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                    
                                    Select::make('icon_position')
                                        ->label('Icon Position')
                                        ->options([
                                            'before' => 'Before Label',
                                            'after' => 'After Label',
                                        ])
                                        ->default('before'),
                                ]),
                                
                                KeyValue::make('icon_options')
                                    ->label('Icon Options')
                                    ->keyLabel('Value')
                                    ->valueLabel('Icon')
                                    ->required()
                                    ->default([
                                        'true' => 'heroicon-o-check-circle',
                                        'false' => 'heroicon-o-x-circle',
                                    ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Select::make('size')
                                    ->label('Icon Size')
                                    ->options([
                                        'sm' => 'Small',
                                        'md' => 'Medium',
                                        'lg' => 'Large',
                                        'xl' => 'Extra Large',
                                    ])
                                    ->default('md'),
                                
                                KeyValue::make('color_options')
                                    ->label('Color Options')
                                    ->keyLabel('Value')
                                    ->valueLabel('Color')
                                    ->default([
                                        'true' => 'success',
                                        'false' => 'danger',
                                    ]),
                                
                                Toggle::make('show_label')
                                    ->label('Show Label with Icon')
                                    ->inline()
                                    ->default(true),
                            ]),
                    ]),
                
                // Boolean Entry
                Block::make('boolean_entry')
                    ->label('Boolean Entry')
                    ->icon('heroicon-o-check')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('field')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                ]),
                                
                                Grid::make(3)->schema([
                                    TextInput::make('true_label')
                                        ->label('True Label')
                                        ->default('Yes')
                                        ->placeholder('Active, Enabled, Yes'),
                                    
                                    TextInput::make('false_label')
                                        ->label('False Label')
                                        ->default('No')
                                        ->placeholder('Inactive, Disabled, No'),
                                    
                                    Select::make('display_type')
                                        ->label('Display Type')
                                        ->options([
                                            'icon' => 'Icon Only',
                                            'badge' => 'Badge',
                                            'toggle' => 'Toggle Visual',
                                            'text' => 'Text Only',
                                        ])
                                        ->default('icon'),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('true_icon')
                                        ->label('True Icon')
                                        ->default('heroicon-o-check-circle')
                                        ->placeholder('heroicon-o-check'),
                                    
                                    TextInput::make('false_icon')
                                        ->label('False Icon')
                                        ->default('heroicon-o-x-circle')
                                        ->placeholder('heroicon-o-x-mark'),
                                ]),
                                
                                Grid::make(2)->schema([
                                    Select::make('true_color')
                                        ->label('True Color')
                                        ->options([
                                            'success' => 'Success (Green)',
                                            'primary' => 'Primary (Blue)',
                                            'info' => 'Info (Light Blue)',
                                        ])
                                        ->default('success'),
                                    
                                    Select::make('false_color')
                                        ->label('False Color')
                                        ->options([
                                            'danger' => 'Danger (Red)',
                                            'warning' => 'Warning (Yellow)',
                                            'gray' => 'Gray',
                                        ])
                                        ->default('danger'),
                                ]),
                            ]),
                    ]),
                
                // Badge Entry
                Block::make('badge_entry')
                    ->label('Badge Entry')
                    ->icon('heroicon-o-tag')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('field')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                ]),
                                
                                KeyValue::make('badge_options')
                                    ->label('Badge Options')
                                    ->keyLabel('Value')
                                    ->valueLabel('Label')
                                    ->required()
                                    ->default([
                                        'pending' => 'Pending',
                                        'active' => 'Active',
                                        'completed' => 'Completed',
                                    ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                KeyValue::make('color_options')
                                    ->label('Color Options')
                                    ->keyLabel('Value')
                                    ->valueLabel('Color')
                                    ->default([
                                        'pending' => 'warning',
                                        'active' => 'primary',
                                        'completed' => 'success',
                                    ]),
                                
                                KeyValue::make('icon_options')
                                    ->label('Icon Options (Optional)')
                                    ->keyLabel('Value')
                                    ->valueLabel('Icon')
                                    ->default([]),
                                
                                Select::make('size')
                                    ->label('Badge Size')
                                    ->options([
                                        'xs' => 'Extra Small',
                                        'sm' => 'Small',
                                        'md' => 'Medium',
                                        'lg' => 'Large',
                                    ])
                                    ->default('md'),
                            ]),
                    ]),
                
                // Color Entry
                Block::make('color_entry')
                    ->label('Color Entry')
                    ->icon('heroicon-o-swatch')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('field')
                                        ->label('Field Name')
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
                                    
                                    Toggle::make('show_hex')
                                        ->label('Show HEX Value')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('show_name')
                                        ->label('Show Color Name')
                                        ->inline()
                                        ->default(false),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Select::make('display_type')
                                    ->label('Display Type')
                                    ->options([
                                        'swatch' => 'Color Swatch',
                                        'circle' => 'Circle',
                                        'square' => 'Square',
                                        'badge' => 'Badge with Color',
                                    ])
                                    ->default('swatch'),
                                
                                Select::make('size')
                                    ->label('Swatch Size')
                                    ->options([
                                        'sm' => 'Small',
                                        'md' => 'Medium',
                                        'lg' => 'Large',
                                    ])
                                    ->default('md'),
                            ]),
                    ]),
                
                // Date Entry
                Block::make('date_entry')
                    ->label('Date Entry')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('field')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                    
                                    Select::make('date_type')
                                        ->label('Date Type')
                                        ->options([
                                            'date' => 'Date Only',
                                            'datetime' => 'Date & Time',
                                            'time' => 'Time Only',
                                        ])
                                        ->default('date'),
                                ]),
                                
                                Grid::make(2)->schema([
                                    TextInput::make('format')
                                        ->label('Display Format')
                                        ->placeholder('d M Y, H:i')
                                        ->default('M j, Y'),
                                    
                                    Toggle::make('relative')
                                        ->label('Show Relative Time')
                                        ->inline()
                                        ->default(false)
                                        ->helperText('Shows "2 hours ago" format'),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Select::make('timezone')
                                    ->label('Display Timezone')
                                    ->options([
                                        'UTC' => 'UTC',
                                        'user' => 'User Timezone',
                                        'app' => 'Application Timezone',
                                    ])
                                    ->default('app'),
                                
                                Toggle::make('show_timezone')
                                    ->label('Show Timezone')
                                    ->inline()
                                    ->default(false),
                                
                                TextInput::make('empty_state')
                                    ->label('Empty State Text')
                                    ->placeholder('Never, Not set'),
                            ]),
                    ]),
                
                // Image Entry
                Block::make('image_entry')
                    ->label('Image Entry')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('field')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                ]),
                                
                                Grid::make(3)->schema([
                                    TextInput::make('width')
                                        ->label('Width')
                                        ->numeric()
                                        ->placeholder('200'),
                                    
                                    TextInput::make('height')
                                        ->label('Height')
                                        ->numeric()
                                        ->placeholder('200'),
                                    
                                    Select::make('shape')
                                        ->label('Shape')
                                        ->options([
                                            'square' => 'Square',
                                            'circle' => 'Circle',
                                            'rounded' => 'Rounded',
                                        ])
                                        ->default('square'),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Toggle::make('gallery')
                                    ->label('Enable Gallery View')
                                    ->inline()
                                    ->default(false),
                                
                                Toggle::make('lightbox')
                                    ->label('Enable Lightbox')
                                    ->inline()
                                    ->default(true),
                                
                                Toggle::make('lazy_load')
                                    ->label('Lazy Load')
                                    ->inline()
                                    ->default(true),
                                
                                TextInput::make('placeholder')
                                    ->label('Placeholder Image URL')
                                    ->placeholder('/images/placeholder.png'),
                                
                                Select::make('fit')
                                    ->label('Object Fit')
                                    ->options([
                                        'contain' => 'Contain',
                                        'cover' => 'Cover',
                                        'fill' => 'Fill',
                                        'none' => 'None',
                                        'scale-down' => 'Scale Down',
                                    ])
                                    ->default('cover'),
                            ]),
                    ]),
                
                // List Entry
                Block::make('list_entry')
                    ->label('List Entry')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('field')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                ]),
                                
                                Grid::make(2)->schema([
                                    Select::make('list_type')
                                        ->label('List Type')
                                        ->options([
                                            'ul' => 'Unordered List',
                                            'ol' => 'Ordered List',
                                            'dl' => 'Description List',
                                        ])
                                        ->default('ul'),
                                    
                                    TextInput::make('separator')
                                        ->label('Separator (if string)')
                                        ->placeholder(', or |'),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                TextInput::make('limit')
                                    ->label('Item Limit')
                                    ->numeric()
                                    ->placeholder('10'),
                                
                                Toggle::make('expandable')
                                    ->label('Expandable')
                                    ->inline()
                                    ->default(false),
                                
                                Select::make('bullet_style')
                                    ->label('Bullet Style')
                                    ->options([
                                        'disc' => 'Disc',
                                        'circle' => 'Circle',
                                        'square' => 'Square',
                                        'none' => 'None',
                                    ])
                                    ->default('disc'),
                            ]),
                    ]),
                
                // Key-Value Entry
                Block::make('keyvalue_entry')
                    ->label('Key-Value Entry')
                    ->icon('heroicon-o-rectangle-group')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('field')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                ]),
                                
                                Grid::make(2)->schema([
                                    TextInput::make('key_label')
                                        ->label('Key Column Label')
                                        ->default('Property'),
                                    
                                    TextInput::make('value_label')
                                        ->label('Value Column Label')
                                        ->default('Value'),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Toggle::make('bordered')
                                    ->label('Show Borders')
                                    ->inline()
                                    ->default(true),
                                
                                Toggle::make('striped')
                                    ->label('Striped Rows')
                                    ->inline()
                                    ->default(false),
                                
                                Toggle::make('compact')
                                    ->label('Compact Mode')
                                    ->inline()
                                    ->default(false),
                                
                                Select::make('layout')
                                    ->label('Layout')
                                    ->options([
                                        'table' => 'Table',
                                        'list' => 'List',
                                        'grid' => 'Grid',
                                    ])
                                    ->default('table'),
                            ]),
                    ]),
                
                // Repeatable Entry
                Block::make('repeatable_entry')
                    ->label('Repeatable Entry')
                    ->icon('heroicon-o-squares-plus')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('field')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                ]),
                                
                                Repeater::make('schema')
                                    ->label('Entry Schema')
                                    ->schema([
                                        Grid::make(3)->schema([
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
                                                    'badge' => 'Badge',
                                                    'boolean' => 'Boolean',
                                                    'date' => 'Date',
                                                ])
                                                ->default('text'),
                                        ]),
                                    ])
                                    ->minItems(1)
                                    ->defaultItems(2),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Select::make('layout')
                                    ->label('Layout')
                                    ->options([
                                        'table' => 'Table',
                                        'grid' => 'Grid',
                                        'list' => 'List',
                                    ])
                                    ->default('table'),
                                
                                Toggle::make('collapsible')
                                    ->label('Collapsible')
                                    ->inline()
                                    ->default(false),
                                
                                Toggle::make('collapsed')
                                    ->label('Collapsed by Default')
                                    ->inline()
                                    ->default(false)
                                    ->visible(fn ($get) => $get('collapsible')),
                                
                                TextInput::make('empty_state')
                                    ->label('Empty State Message')
                                    ->placeholder('No items found'),
                            ]),
                    ]),
            ])
            ->addActionLabel('Add Infolist Entry')
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->blockNumbers(false)
            ->columnSpanFull();
    }
}