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
use Filament\Forms\Components\Repeater;

class FormFieldsBuilderAdvanced
{
    public static function make(string $name = 'fields'): Builder
    {
        return Builder::make($name)
            ->label('Form Fields')
            ->blocks([
                // Text Input Field
                Block::make('text_input')
                    ->label('Text Input')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->description('Essential field settings')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/')
                                        ->placeholder('field_name'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required()
                                        ->placeholder('Field Label'),
                                    
                                    Select::make('type')
                                        ->label('Input Type')
                                        ->options([
                                            'text' => 'Text',
                                            'email' => 'Email',
                                            'password' => 'Password',
                                            'tel' => 'Telephone',
                                            'url' => 'URL',
                                            'search' => 'Search',
                                        ])
                                        ->default('text'),
                                ]),
                                
                                Grid::make(4)->schema([
                                    Toggle::make('required')
                                        ->label('Required')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('disabled')
                                        ->label('Disabled')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('readonly')
                                        ->label('Read Only')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('autofocus')
                                        ->label('Auto Focus')
                                        ->inline()
                                        ->default(false),
                                ]),
                                
                                TextInput::make('placeholder')
                                    ->label('Placeholder Text')
                                    ->placeholder('Enter placeholder text'),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('maxLength')
                                        ->label('Max Length')
                                        ->numeric()
                                        ->placeholder('255'),
                                    
                                    TextInput::make('minLength')
                                        ->label('Min Length')
                                        ->numeric()
                                        ->placeholder('0'),
                                ]),
                                
                                Grid::make(2)->schema([
                                    TextInput::make('prefix')
                                        ->label('Prefix')
                                        ->placeholder('$, @, #'),
                                    
                                    TextInput::make('suffix')
                                        ->label('Suffix')
                                        ->placeholder('USD, .com'),
                                ]),
                                
                                Grid::make(2)->schema([
                                    TextInput::make('prefixIcon')
                                        ->label('Prefix Icon')
                                        ->placeholder('heroicon-o-user'),
                                    
                                    TextInput::make('suffixIcon')
                                        ->label('Suffix Icon')
                                        ->placeholder('heroicon-o-envelope'),
                                ]),
                                
                                TextInput::make('hint')
                                    ->label('Hint Text')
                                    ->placeholder('Help text for the field'),
                                
                                Select::make('hintColor')
                                    ->label('Hint Color')
                                    ->options([
                                        'primary' => 'Primary',
                                        'secondary' => 'Secondary',
                                        'success' => 'Success',
                                        'danger' => 'Danger',
                                        'warning' => 'Warning',
                                        'info' => 'Info',
                                        'gray' => 'Gray',
                                    ])
                                    ->default('gray'),
                                
                                Toggle::make('live')
                                    ->label('Live Validation')
                                    ->inline()
                                    ->default(false)
                                    ->helperText('Validate as user types'),
                                
                                TextInput::make('live_debounce')
                                    ->label('Live Debounce (ms)')
                                    ->numeric()
                                    ->default(500)
                                    ->visible(fn ($get) => $get('live')),
                                
                                TextInput::make('mask')
                                    ->label('Input Mask')
                                    ->placeholder('(999) 999-9999'),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|email|min:3|max:255'),
                                
                                TextInput::make('default')
                                    ->label('Default Value')
                                    ->placeholder('Default text'),
                                
                                Toggle::make('autocomplete')
                                    ->label('Enable Autocomplete')
                                    ->inline()
                                    ->default(true),
                                
                                KeyValue::make('extraAttributes')
                                    ->label('Extra HTML Attributes')
                                    ->keyLabel('Attribute')
                                    ->valueLabel('Value'),
                            ]),
                    ]),
                
                // Textarea Field
                Block::make('textarea')
                    ->label('Textarea')
                    ->icon('heroicon-o-bars-3-bottom-left')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                ]),
                                
                                Grid::make(3)->schema([
                                    TextInput::make('rows')
                                        ->label('Rows')
                                        ->numeric()
                                        ->default(3)
                                        ->minValue(1),
                                    
                                    TextInput::make('cols')
                                        ->label('Columns')
                                        ->numeric()
                                        ->placeholder('50'),
                                    
                                    TextInput::make('maxLength')
                                        ->label('Max Length')
                                        ->numeric()
                                        ->placeholder('1000'),
                                ]),
                                
                                Grid::make(3)->schema([
                                    Toggle::make('required')
                                        ->label('Required')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('disabled')
                                        ->label('Disabled')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('readonly')
                                        ->label('Read Only')
                                        ->inline()
                                        ->default(false),
                                ]),
                                
                                TextInput::make('placeholder')
                                    ->label('Placeholder')
                                    ->placeholder('Enter description...'),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Toggle::make('autosize')
                                    ->label('Auto-resize')
                                    ->inline()
                                    ->default(false)
                                    ->helperText('Automatically adjust height based on content'),
                                
                                Grid::make(2)->schema([
                                    TextInput::make('minHeight')
                                        ->label('Min Height')
                                        ->placeholder('100px'),
                                    
                                    TextInput::make('maxHeight')
                                        ->label('Max Height')
                                        ->placeholder('500px'),
                                ]),
                                
                                TextInput::make('hint')
                                    ->label('Hint Text')
                                    ->placeholder('Help text for the field'),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|min:10|max:5000'),
                                
                                Textarea::make('default')
                                    ->label('Default Value')
                                    ->rows(2),
                                
                                KeyValue::make('extraAttributes')
                                    ->label('Extra Attributes'),
                            ]),
                    ]),
                
                // Select Field
                Block::make('select')
                    ->label('Select Dropdown')
                    ->icon('heroicon-o-chevron-up-down')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                ]),
                                
                                Select::make('options_source')
                                    ->label('Options Source')
                                    ->options([
                                        'static' => 'Static Options',
                                        'model' => 'From Model',
                                        'enum' => 'From Enum',
                                        'api' => 'From API',
                                    ])
                                    ->default('static')
                                    ->reactive(),
                                
                                KeyValue::make('options')
                                    ->label('Options')
                                    ->keyLabel('Value')
                                    ->valueLabel('Label')
                                    ->visible(fn ($get) => $get('options_source') === 'static')
                                    ->default([
                                        'option1' => 'Option 1',
                                        'option2' => 'Option 2',
                                    ]),
                                
                                Grid::make(2)->schema([
                                    TextInput::make('model')
                                        ->label('Model Class')
                                        ->placeholder('App\\Models\\Category')
                                        ->visible(fn ($get) => $get('options_source') === 'model'),
                                    
                                    TextInput::make('model_label')
                                        ->label('Label Column')
                                        ->placeholder('name')
                                        ->default('name')
                                        ->visible(fn ($get) => $get('options_source') === 'model'),
                                ]),
                                
                                Grid::make(4)->schema([
                                    Toggle::make('required')
                                        ->label('Required')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('multiple')
                                        ->label('Multiple')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('searchable')
                                        ->label('Searchable')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('disabled')
                                        ->label('Disabled')
                                        ->inline()
                                        ->default(false),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                TextInput::make('placeholder')
                                    ->label('Placeholder')
                                    ->placeholder('Select an option'),
                                
                                Toggle::make('preload')
                                    ->label('Preload Options')
                                    ->inline()
                                    ->default(false)
                                    ->helperText('Load all options on page load'),
                                
                                Toggle::make('native')
                                    ->label('Native Browser Select')
                                    ->inline()
                                    ->default(false),
                                
                                Grid::make(2)->schema([
                                    Toggle::make('createOption')
                                        ->label('Allow Creating')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('editOption')
                                        ->label('Allow Editing')
                                        ->inline()
                                        ->default(false),
                                ]),
                                
                                TextInput::make('relationship')
                                    ->label('Relationship Name')
                                    ->placeholder('category, tags')
                                    ->visible(fn ($get) => $get('options_source') === 'model'),
                                
                                TextInput::make('api_endpoint')
                                    ->label('API Endpoint')
                                    ->placeholder('/api/options')
                                    ->visible(fn ($get) => $get('options_source') === 'api'),
                                
                                Select::make('default')
                                    ->label('Default Value')
                                    ->options(fn ($get) => $get('options') ?? [])
                                    ->visible(fn ($get) => $get('options_source') === 'static'),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|in:option1,option2'),
                                
                                KeyValue::make('descriptions')
                                    ->label('Option Descriptions')
                                    ->keyLabel('Value')
                                    ->valueLabel('Description'),
                                
                                KeyValue::make('extraAttributes')
                                    ->label('Extra Attributes'),
                            ]),
                    ]),
                
                // Number Input Field
                Block::make('number')
                    ->label('Number Input')
                    ->icon('heroicon-o-hashtag')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                    
                                    Select::make('type')
                                        ->label('Number Type')
                                        ->options([
                                            'integer' => 'Integer',
                                            'decimal' => 'Decimal',
                                            'percentage' => 'Percentage',
                                            'currency' => 'Currency',
                                        ])
                                        ->default('integer')
                                        ->reactive(),
                                ]),
                                
                                Grid::make(3)->schema([
                                    TextInput::make('min')
                                        ->label('Minimum')
                                        ->numeric(),
                                    
                                    TextInput::make('max')
                                        ->label('Maximum')
                                        ->numeric(),
                                    
                                    TextInput::make('step')
                                        ->label('Step')
                                        ->numeric()
                                        ->default(1),
                                ]),
                                
                                Grid::make(3)->schema([
                                    Toggle::make('required')
                                        ->label('Required')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('disabled')
                                        ->label('Disabled')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('readonly')
                                        ->label('Read Only')
                                        ->inline()
                                        ->default(false),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('prefix')
                                        ->label('Prefix')
                                        ->placeholder('$')
                                        ->visible(fn ($get) => $get('type') === 'currency'),
                                    
                                    TextInput::make('suffix')
                                        ->label('Suffix')
                                        ->placeholder('%')
                                        ->visible(fn ($get) => $get('type') === 'percentage'),
                                ]),
                                
                                TextInput::make('placeholder')
                                    ->label('Placeholder')
                                    ->placeholder('Enter number'),
                                
                                TextInput::make('default')
                                    ->label('Default Value')
                                    ->numeric()
                                    ->default(0),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|numeric|min:0|max:100'),
                                
                                TextInput::make('hint')
                                    ->label('Hint Text')
                                    ->placeholder('Enter a value between min and max'),
                                
                                Toggle::make('live')
                                    ->label('Live Validation')
                                    ->inline()
                                    ->default(false),
                                
                                KeyValue::make('extraAttributes')
                                    ->label('Extra Attributes'),
                            ]),
                    ]),
                
                // Date/DateTime Picker
                Block::make('datetime')
                    ->label('Date/DateTime Picker')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                    
                                    Select::make('type')
                                        ->label('Picker Type')
                                        ->options([
                                            'date' => 'Date Only',
                                            'datetime' => 'Date & Time',
                                            'time' => 'Time Only',
                                            'month' => 'Month Picker',
                                            'year' => 'Year Picker',
                                        ])
                                        ->default('date')
                                        ->reactive(),
                                ]),
                                
                                Grid::make(3)->schema([
                                    Toggle::make('required')
                                        ->label('Required')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('disabled')
                                        ->label('Disabled')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('native')
                                        ->label('Native Picker')
                                        ->inline()
                                        ->default(false),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('displayFormat')
                                        ->label('Display Format')
                                        ->placeholder('Y-m-d')
                                        ->default('Y-m-d'),
                                    
                                    TextInput::make('format')
                                        ->label('Storage Format')
                                        ->placeholder('Y-m-d H:i:s')
                                        ->default('Y-m-d'),
                                ]),
                                
                                Grid::make(2)->schema([
                                    TextInput::make('minDate')
                                        ->label('Min Date')
                                        ->placeholder('2020-01-01'),
                                    
                                    TextInput::make('maxDate')
                                        ->label('Max Date')
                                        ->placeholder('2030-12-31'),
                                ]),
                                
                                KeyValue::make('disabledDates')
                                    ->label('Disabled Dates')
                                    ->keyLabel('Date')
                                    ->valueLabel('Reason')
                                    ->visible(fn ($get) => in_array($get('type'), ['date', 'datetime'])),
                                
                                Select::make('timezone')
                                    ->label('Timezone')
                                    ->options([
                                        'UTC' => 'UTC',
                                        'user' => 'User Timezone',
                                        'Asia/Riyadh' => 'Riyadh',
                                        'Asia/Dubai' => 'Dubai',
                                    ])
                                    ->default('UTC')
                                    ->visible(fn ($get) => in_array($get('type'), ['datetime', 'time'])),
                                
                                Grid::make(2)->schema([
                                    TextInput::make('hoursStep')
                                        ->label('Hours Step')
                                        ->numeric()
                                        ->default(1)
                                        ->visible(fn ($get) => in_array($get('type'), ['datetime', 'time'])),
                                    
                                    TextInput::make('minutesStep')
                                        ->label('Minutes Step')
                                        ->numeric()
                                        ->default(1)
                                        ->visible(fn ($get) => in_array($get('type'), ['datetime', 'time'])),
                                ]),
                                
                                Toggle::make('seconds')
                                    ->label('Show Seconds')
                                    ->inline()
                                    ->default(false)
                                    ->visible(fn ($get) => in_array($get('type'), ['datetime', 'time'])),
                                
                                Select::make('firstDayOfWeek')
                                    ->label('First Day of Week')
                                    ->options([
                                        0 => 'Sunday',
                                        1 => 'Monday',
                                        6 => 'Saturday',
                                    ])
                                    ->default(0)
                                    ->visible(fn ($get) => in_array($get('type'), ['date', 'datetime'])),
                                
                                Toggle::make('closeOnDateSelection')
                                    ->label('Close on Selection')
                                    ->inline()
                                    ->default(true),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|date|after:today'),
                                
                                KeyValue::make('extraAttributes')
                                    ->label('Extra Attributes'),
                            ]),
                    ]),
                
                // Toggle/Checkbox Field
                Block::make('toggle')
                    ->label('Toggle/Checkbox')
                    ->icon('heroicon-o-check-circle')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                    
                                    Select::make('type')
                                        ->label('Display Type')
                                        ->options([
                                            'toggle' => 'Toggle Switch',
                                            'checkbox' => 'Checkbox',
                                        ])
                                        ->default('toggle'),
                                ]),
                                
                                Grid::make(3)->schema([
                                    Toggle::make('required')
                                        ->label('Required')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('disabled')
                                        ->label('Disabled')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('inline')
                                        ->label('Inline')
                                        ->inline()
                                        ->default(true),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('onIcon')
                                        ->label('On Icon')
                                        ->placeholder('heroicon-o-check'),
                                    
                                    TextInput::make('offIcon')
                                        ->label('Off Icon')
                                        ->placeholder('heroicon-o-x-mark'),
                                ]),
                                
                                Grid::make(2)->schema([
                                    ColorPicker::make('onColor')
                                        ->label('On Color')
                                        ->default('#10b981'),
                                    
                                    ColorPicker::make('offColor')
                                        ->label('Off Color')
                                        ->default('#6b7280'),
                                ]),
                                
                                Toggle::make('default')
                                    ->label('Default Value')
                                    ->inline()
                                    ->default(false),
                                
                                TextInput::make('hint')
                                    ->label('Hint Text')
                                    ->placeholder('Additional information'),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|boolean'),
                                
                                KeyValue::make('extraAttributes')
                                    ->label('Extra Attributes'),
                            ]),
                    ]),
                
                // File Upload Field
                Block::make('file_upload')
                    ->label('File Upload')
                    ->icon('heroicon-o-paper-clip')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                    
                                    Select::make('type')
                                        ->label('File Type')
                                        ->options([
                                            'file' => 'Any File',
                                            'image' => 'Image Only',
                                            'document' => 'Document',
                                            'video' => 'Video',
                                            'audio' => 'Audio',
                                        ])
                                        ->default('file')
                                        ->reactive(),
                                ]),
                                
                                Grid::make(4)->schema([
                                    Toggle::make('required')
                                        ->label('Required')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('multiple')
                                        ->label('Multiple')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('disabled')
                                        ->label('Disabled')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('downloadable')
                                        ->label('Downloadable')
                                        ->inline()
                                        ->default(true),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('maxSize')
                                        ->label('Max Size (KB)')
                                        ->numeric()
                                        ->default(2048),
                                    
                                    TextInput::make('minSize')
                                        ->label('Min Size (KB)')
                                        ->numeric(),
                                    
                                    TextInput::make('maxFiles')
                                        ->label('Max Files')
                                        ->numeric()
                                        ->visible(fn ($get) => $get('multiple')),
                                ]),
                                
                                TextInput::make('acceptedFileTypes')
                                    ->label('Accepted Types')
                                    ->placeholder('image/*, .pdf, .doc')
                                    ->helperText('Comma-separated MIME types or extensions'),
                                
                                Select::make('disk')
                                    ->label('Storage Disk')
                                    ->options([
                                        'local' => 'Local',
                                        'public' => 'Public',
                                        's3' => 'Amazon S3',
                                    ])
                                    ->default('public'),
                                
                                TextInput::make('directory')
                                    ->label('Upload Directory')
                                    ->placeholder('uploads/files')
                                    ->default('uploads'),
                                
                                Select::make('visibility')
                                    ->label('Visibility')
                                    ->options([
                                        'public' => 'Public',
                                        'private' => 'Private',
                                    ])
                                    ->default('public'),
                                
                                Toggle::make('enableOpen')
                                    ->label('Enable Open')
                                    ->inline()
                                    ->default(true),
                                
                                Toggle::make('enableDownload')
                                    ->label('Enable Download')
                                    ->inline()
                                    ->default(true),
                                
                                Toggle::make('enableReordering')
                                    ->label('Enable Reordering')
                                    ->inline()
                                    ->default(false)
                                    ->visible(fn ($get) => $get('multiple')),
                                
                                // Image-specific options
                                Toggle::make('imageEditor')
                                    ->label('Enable Image Editor')
                                    ->inline()
                                    ->default(false)
                                    ->visible(fn ($get) => $get('type') === 'image'),
                                
                                KeyValue::make('imageAspectRatios')
                                    ->label('Aspect Ratios')
                                    ->keyLabel('Ratio')
                                    ->valueLabel('Label')
                                    ->default([
                                        '16:9' => 'Widescreen',
                                        '4:3' => 'Standard',
                                        '1:1' => 'Square',
                                    ])
                                    ->visible(fn ($get) => $get('type') === 'image' && $get('imageEditor')),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|file|max:2048'),
                                
                                KeyValue::make('extraAttributes')
                                    ->label('Extra Attributes'),
                            ]),
                    ]),
                
                // Rich Editor Field
                Block::make('rich_editor')
                    ->label('Rich Editor')
                    ->icon('heroicon-o-bars-3')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                ]),
                                
                                Grid::make(3)->schema([
                                    Toggle::make('required')
                                        ->label('Required')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('disabled')
                                        ->label('Disabled')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('readonly')
                                        ->label('Read Only')
                                        ->inline()
                                        ->default(false),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Select::make('toolbarButtons')
                                    ->label('Toolbar Buttons')
                                    ->multiple()
                                    ->options([
                                        'attachFiles' => 'Attach Files',
                                        'blockquote' => 'Blockquote',
                                        'bold' => 'Bold',
                                        'bulletList' => 'Bullet List',
                                        'codeBlock' => 'Code Block',
                                        'h2' => 'Heading 2',
                                        'h3' => 'Heading 3',
                                        'italic' => 'Italic',
                                        'link' => 'Link',
                                        'orderedList' => 'Ordered List',
                                        'redo' => 'Redo',
                                        'strike' => 'Strike',
                                        'underline' => 'Underline',
                                        'undo' => 'Undo',
                                        'table' => 'Table',
                                        'image' => 'Image',
                                    ])
                                    ->default(['bold', 'italic', 'link', 'bulletList', 'orderedList']),
                                
                                Toggle::make('fileAttachments')
                                    ->label('Allow File Attachments')
                                    ->inline()
                                    ->default(false),
                                
                                Select::make('fileAttachmentsDisk')
                                    ->label('Attachments Disk')
                                    ->options([
                                        'local' => 'Local',
                                        'public' => 'Public',
                                        's3' => 'S3',
                                    ])
                                    ->default('public')
                                    ->visible(fn ($get) => $get('fileAttachments')),
                                
                                TextInput::make('fileAttachmentsDirectory')
                                    ->label('Attachments Directory')
                                    ->placeholder('attachments')
                                    ->visible(fn ($get) => $get('fileAttachments')),
                                
                                TextInput::make('maxLength')
                                    ->label('Max Length')
                                    ->numeric()
                                    ->placeholder('10000'),
                                
                                TextInput::make('minHeight')
                                    ->label('Min Height')
                                    ->placeholder('200px'),
                                
                                TextInput::make('maxHeight')
                                    ->label('Max Height')
                                    ->placeholder('500px'),
                                
                                Textarea::make('default')
                                    ->label('Default Content')
                                    ->rows(3),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|min:10|max:10000'),
                                
                                KeyValue::make('extraAttributes')
                                    ->label('Extra Attributes'),
                            ]),
                    ]),
                
                // Repeater Field
                Block::make('repeater')
                    ->label('Repeater Field')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                ]),
                                
                                Grid::make(3)->schema([
                                    TextInput::make('minItems')
                                        ->label('Min Items')
                                        ->numeric()
                                        ->default(0),
                                    
                                    TextInput::make('maxItems')
                                        ->label('Max Items')
                                        ->numeric()
                                        ->placeholder('Unlimited'),
                                    
                                    TextInput::make('defaultItems')
                                        ->label('Default Items')
                                        ->numeric()
                                        ->default(1),
                                ]),
                                
                                Grid::make(4)->schema([
                                    Toggle::make('required')
                                        ->label('Required')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('collapsible')
                                        ->label('Collapsible')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('collapsed')
                                        ->label('Collapsed')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('cloneable')
                                        ->label('Cloneable')
                                        ->inline()
                                        ->default(true),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Toggle::make('reorderable')
                                    ->label('Reorderable')
                                    ->inline()
                                    ->default(true),
                                
                                TextInput::make('addActionLabel')
                                    ->label('Add Button Label')
                                    ->placeholder('Add Item'),
                                
                                TextInput::make('deleteActionLabel')
                                    ->label('Delete Button Label')
                                    ->placeholder('Remove Item'),
                                
                                TextInput::make('reorderActionLabel')
                                    ->label('Reorder Button Label')
                                    ->placeholder('Reorder Items'),
                                
                                TextInput::make('cloneActionLabel')
                                    ->label('Clone Button Label')
                                    ->placeholder('Duplicate Item'),
                                
                                TextInput::make('itemLabel')
                                    ->label('Item Label Pattern')
                                    ->placeholder('Item #{index}')
                                    ->helperText('Use {index} for item number, {field_name} for field values'),
                                
                                Textarea::make('schema_json')
                                    ->label('Repeater Schema (JSON)')
                                    ->rows(5)
                                    ->placeholder('Define the fields within the repeater as JSON')
                                    ->helperText('This will be converted to proper field definitions'),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|array|min:1'),
                                
                                KeyValue::make('extraAttributes')
                                    ->label('Extra Attributes'),
                            ]),
                    ]),
                
                // Key-Value Field
                Block::make('key_value')
                    ->label('Key-Value Field')
                    ->icon('heroicon-o-key')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                ]),
                                
                                Grid::make(2)->schema([
                                    TextInput::make('keyLabel')
                                        ->label('Key Label')
                                        ->default('Key')
                                        ->placeholder('Property'),
                                    
                                    TextInput::make('valueLabel')
                                        ->label('Value Label')
                                        ->default('Value')
                                        ->placeholder('Setting'),
                                ]),
                                
                                Grid::make(4)->schema([
                                    Toggle::make('required')
                                        ->label('Required')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('addable')
                                        ->label('Addable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('deletable')
                                        ->label('Deletable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('editableKeys')
                                        ->label('Editable Keys')
                                        ->inline()
                                        ->default(true),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                TextInput::make('keyPlaceholder')
                                    ->label('Key Placeholder')
                                    ->placeholder('Enter key'),
                                
                                TextInput::make('valuePlaceholder')
                                    ->label('Value Placeholder')
                                    ->placeholder('Enter value'),
                                
                                TextInput::make('addActionLabel')
                                    ->label('Add Button Label')
                                    ->placeholder('Add Property'),
                                
                                KeyValue::make('default')
                                    ->label('Default Values')
                                    ->keyLabel('Key')
                                    ->valueLabel('Value'),
                                
                                Toggle::make('reorderable')
                                    ->label('Reorderable')
                                    ->inline()
                                    ->default(false),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|array'),
                                
                                KeyValue::make('extraAttributes')
                                    ->label('Extra Attributes'),
                            ]),
                    ]),
                
                // Color Picker Field
                Block::make('color_picker')
                    ->label('Color Picker')
                    ->icon('heroicon-o-swatch')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                    
                                    Select::make('format')
                                        ->label('Color Format')
                                        ->options([
                                            'hex' => 'HEX',
                                            'rgb' => 'RGB',
                                            'rgba' => 'RGBA',
                                            'hsl' => 'HSL',
                                        ])
                                        ->default('hex'),
                                ]),
                                
                                Grid::make(3)->schema([
                                    Toggle::make('required')
                                        ->label('Required')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('disabled')
                                        ->label('Disabled')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('showAlpha')
                                        ->label('Show Alpha')
                                        ->inline()
                                        ->default(false),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                ColorPicker::make('default')
                                    ->label('Default Color')
                                    ->default('#000000'),
                                
                                KeyValue::make('presetColors')
                                    ->label('Preset Colors')
                                    ->keyLabel('Name')
                                    ->valueLabel('Color')
                                    ->default([
                                        'Primary' => '#3b82f6',
                                        'Secondary' => '#6b7280',
                                        'Success' => '#10b981',
                                        'Danger' => '#ef4444',
                                    ]),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|regex:/^#[0-9A-F]{6}$/i'),
                                
                                KeyValue::make('extraAttributes')
                                    ->label('Extra Attributes'),
                            ]),
                    ]),
            ])
            ->addActionLabel('Add Form Field')
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->blockNumbers(false)
            ->columnSpanFull();
    }
}