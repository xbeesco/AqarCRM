<?php

namespace App\Filament\Forms\Components\Module;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use App\Filament\Forms\Components\SafeKeyValue as KeyValue;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Fieldset;

class DatabaseFieldsBuilder
{
    public static function make(string $name = 'fields'): Builder
    {
        return Builder::make($name)
            ->label('Database Fields')
            ->blocks([
                // String Field Type
                Block::make('string_field')
                    ->label('String Field')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        // Basic Fields (Always Visible)
                        Section::make('Basic Configuration')
                            ->description('Essential field settings')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('field_name')
                                        ->label('Field Name')
                                        ->required()
                                        ->placeholder('column_name')
                                        ->regex('/^[a-z_]+$/')
                                        ->validationMessages([
                                            'regex' => 'Only lowercase letters and underscores allowed'
                                        ]),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required()
                                        ->placeholder('Field Label'),
                                    
                                    TextInput::make('length')
                                        ->label('Max Length')
                                        ->numeric()
                                        ->default(255)
                                        ->minValue(1)
                                        ->maxValue(65535),
                                ]),
                                
                                Grid::make(4)->schema([
                                    Toggle::make('nullable')
                                        ->label('Nullable')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('unique')
                                        ->label('Unique')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('fillable')
                                        ->label('Fillable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('searchable')
                                        ->label('Searchable')
                                        ->inline()
                                        ->default(false),
                                ]),
                            ]),
                        
                        // Advanced Fields (Collapsible)
                        Section::make('Advanced Configuration')
                            ->description('Additional field settings and validations')
                            ->collapsed()
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('default')
                                        ->label('Default Value')
                                        ->placeholder('null or default value'),
                                    
                                    Select::make('index')
                                        ->label('Index Type')
                                        ->options([
                                            'none' => 'None',
                                            'index' => 'Regular Index',
                                            'unique' => 'Unique Index',
                                            'fulltext' => 'Fulltext Index',
                                        ])
                                        ->default('none'),
                                ]),
                                
                                TextInput::make('validation')
                                    ->label('Laravel Validation Rules')
                                    ->placeholder('required|min:3|max:255|alpha_dash')
                                    ->helperText('Pipe-separated Laravel validation rules'),
                                
                                Textarea::make('description')
                                    ->label('Field Description')
                                    ->rows(2)
                                    ->placeholder('Describe the purpose of this field'),
                                
                                KeyValue::make('attributes')
                                    ->label('Additional Attributes')
                                    ->keyLabel('Attribute')
                                    ->valueLabel('Value')
                                    ->addActionLabel('Add Attribute')
                                    ->default([]),
                            ]),
                    ]),
                
                // Integer Field Type
                Block::make('integer_field')
                    ->label('Integer Field')
                    ->icon('heroicon-o-hashtag')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('field_name')
                                        ->label('Field Name')
                                        ->required()
                                        ->placeholder('column_name')
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                    
                                    Select::make('int_type')
                                        ->label('Integer Type')
                                        ->options([
                                            'tinyint' => 'Tiny Integer (1 byte)',
                                            'smallint' => 'Small Integer (2 bytes)',
                                            'mediumint' => 'Medium Integer (3 bytes)',
                                            'int' => 'Integer (4 bytes)',
                                            'bigint' => 'Big Integer (8 bytes)',
                                        ])
                                        ->default('int'),
                                ]),
                                
                                Grid::make(5)->schema([
                                    Toggle::make('unsigned')
                                        ->label('Unsigned')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('auto_increment')
                                        ->label('Auto Increment')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('nullable')
                                        ->label('Nullable')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('unique')
                                        ->label('Unique')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('fillable')
                                        ->label('Fillable')
                                        ->inline()
                                        ->default(true),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('default')
                                        ->label('Default Value')
                                        ->numeric()
                                        ->placeholder('0'),
                                    
                                    TextInput::make('min_value')
                                        ->label('Minimum Value')
                                        ->numeric(),
                                    
                                    TextInput::make('max_value')
                                        ->label('Maximum Value')
                                        ->numeric(),
                                ]),
                                
                                Grid::make(2)->schema([
                                    Select::make('index')
                                        ->label('Index Type')
                                        ->options([
                                            'none' => 'None',
                                            'index' => 'Regular Index',
                                            'unique' => 'Unique Index',
                                        ])
                                        ->default('none'),
                                    
                                    TextInput::make('foreign_key')
                                        ->label('Foreign Key Reference')
                                        ->placeholder('table.id'),
                                ]),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|numeric|min:0|max:100'),
                                
                                KeyValue::make('attributes')
                                    ->label('Additional Attributes')
                                    ->default([]),
                            ]),
                    ]),
                
                // Text Field Type
                Block::make('text_field')
                    ->label('Text Field')
                    ->icon('heroicon-o-document')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('field_name')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                    
                                    Select::make('text_type')
                                        ->label('Text Type')
                                        ->options([
                                            'text' => 'Text (64KB)',
                                            'mediumtext' => 'Medium Text (16MB)',
                                            'longtext' => 'Long Text (4GB)',
                                        ])
                                        ->default('text'),
                                ]),
                                
                                Grid::make(3)->schema([
                                    Toggle::make('nullable')
                                        ->label('Nullable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('fillable')
                                        ->label('Fillable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('searchable')
                                        ->label('Searchable')
                                        ->inline()
                                        ->default(false),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Select::make('editor_type')
                                    ->label('Editor Type in Forms')
                                    ->options([
                                        'textarea' => 'Simple Textarea',
                                        'rich_editor' => 'Rich Text Editor',
                                        'markdown' => 'Markdown Editor',
                                        'code' => 'Code Editor',
                                    ])
                                    ->default('textarea'),
                                
                                Textarea::make('default')
                                    ->label('Default Value')
                                    ->rows(3),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|min:10|max:5000'),
                                
                                KeyValue::make('attributes')
                                    ->label('Additional Attributes')
                                    ->default([]),
                            ]),
                    ]),
                
                // Boolean Field Type
                Block::make('boolean_field')
                    ->label('Boolean Field')
                    ->icon('heroicon-o-check-circle')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('field_name')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                ]),
                                
                                Grid::make(3)->schema([
                                    Toggle::make('default')
                                        ->label('Default Value')
                                        ->inline(),
                                    
                                    Toggle::make('nullable')
                                        ->label('Nullable')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('fillable')
                                        ->label('Fillable')
                                        ->inline()
                                        ->default(true),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('true_label')
                                        ->label('True Label')
                                        ->default('Yes')
                                        ->placeholder('Active, Enabled, Yes'),
                                    
                                    TextInput::make('false_label')
                                        ->label('False Label')
                                        ->default('No')
                                        ->placeholder('Inactive, Disabled, No'),
                                ]),
                                
                                Select::make('display_type')
                                    ->label('Display Type in Forms')
                                    ->options([
                                        'toggle' => 'Toggle Switch',
                                        'checkbox' => 'Checkbox',
                                        'radio' => 'Radio Buttons',
                                    ])
                                    ->default('toggle'),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|boolean'),
                                
                                Textarea::make('description')
                                    ->label('Field Description')
                                    ->rows(2),
                            ]),
                    ]),
                
                // Date/DateTime Field Type
                Block::make('datetime_field')
                    ->label('Date/DateTime Field')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('field_name')
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
                                            'datetime' => 'Date and Time',
                                            'time' => 'Time Only',
                                            'timestamp' => 'Timestamp',
                                        ])
                                        ->default('datetime'),
                                ]),
                                
                                Grid::make(4)->schema([
                                    Toggle::make('nullable')
                                        ->label('Nullable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('fillable')
                                        ->label('Fillable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('use_current')
                                        ->label('Use Current as Default')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('update_on_save')
                                        ->label('Update on Save')
                                        ->inline()
                                        ->default(false),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('format')
                                        ->label('Display Format')
                                        ->placeholder('Y-m-d H:i:s')
                                        ->default('Y-m-d H:i:s'),
                                    
                                    Select::make('timezone')
                                        ->label('Timezone')
                                        ->options([
                                            'UTC' => 'UTC',
                                            'Asia/Riyadh' => 'Riyadh',
                                            'Asia/Dubai' => 'Dubai',
                                            'Europe/London' => 'London',
                                            'America/New_York' => 'New York',
                                        ])
                                        ->default('UTC'),
                                ]),
                                
                                Grid::make(2)->schema([
                                    TextInput::make('min_date')
                                        ->label('Minimum Date')
                                        ->placeholder('2020-01-01'),
                                    
                                    TextInput::make('max_date')
                                        ->label('Maximum Date')
                                        ->placeholder('2030-12-31'),
                                ]),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|date|after:today'),
                                
                                KeyValue::make('attributes')
                                    ->label('Additional Attributes')
                                    ->default([]),
                            ]),
                    ]),
                
                // Decimal/Float Field Type
                Block::make('decimal_field')
                    ->label('Decimal/Float Field')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('field_name')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                    
                                    Select::make('decimal_type')
                                        ->label('Number Type')
                                        ->options([
                                            'float' => 'Float',
                                            'double' => 'Double',
                                            'decimal' => 'Decimal (Precise)',
                                        ])
                                        ->default('decimal')
                                        ->reactive(),
                                ]),
                                
                                Grid::make(2)->schema([
                                    TextInput::make('precision')
                                        ->label('Total Digits')
                                        ->numeric()
                                        ->default(10)
                                        ->minValue(1)
                                        ->maxValue(65)
                                        ->visible(fn ($get) => $get('decimal_type') === 'decimal'),
                                    
                                    TextInput::make('scale')
                                        ->label('Decimal Places')
                                        ->numeric()
                                        ->default(2)
                                        ->minValue(0)
                                        ->maxValue(30)
                                        ->visible(fn ($get) => $get('decimal_type') === 'decimal'),
                                ]),
                                
                                Grid::make(4)->schema([
                                    Toggle::make('unsigned')
                                        ->label('Unsigned')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('nullable')
                                        ->label('Nullable')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('fillable')
                                        ->label('Fillable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('is_currency')
                                        ->label('Is Currency')
                                        ->inline()
                                        ->default(false),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('default')
                                        ->label('Default Value')
                                        ->numeric()
                                        ->default(0),
                                    
                                    TextInput::make('min_value')
                                        ->label('Minimum Value')
                                        ->numeric(),
                                    
                                    TextInput::make('max_value')
                                        ->label('Maximum Value')
                                        ->numeric(),
                                ]),
                                
                                Grid::make(2)->schema([
                                    TextInput::make('step')
                                        ->label('Step Value')
                                        ->numeric()
                                        ->default(0.01),
                                    
                                    Select::make('currency')
                                        ->label('Currency')
                                        ->options([
                                            'USD' => 'USD',
                                            'EUR' => 'EUR',
                                            'GBP' => 'GBP',
                                            'SAR' => 'SAR',
                                            'AED' => 'AED',
                                        ])
                                        ->visible(fn ($get) => $get('is_currency')),
                                ]),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|numeric|min:0|max:999999.99'),
                                
                                KeyValue::make('attributes')
                                    ->label('Additional Attributes')
                                    ->default([]),
                            ]),
                    ]),
                
                // JSON Field Type
                Block::make('json_field')
                    ->label('JSON Field')
                    ->icon('heroicon-o-code-bracket')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('field_name')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                ]),
                                
                                Grid::make(3)->schema([
                                    Toggle::make('nullable')
                                        ->label('Nullable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('fillable')
                                        ->label('Fillable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('cast_to_array')
                                        ->label('Cast to Array')
                                        ->inline()
                                        ->default(true),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Select::make('editor_type')
                                    ->label('Editor Type')
                                    ->options([
                                        'key_value' => 'Key-Value Editor',
                                        'json_editor' => 'JSON Editor',
                                        'repeater' => 'Repeater Field',
                                        'builder' => 'Builder Field',
                                    ])
                                    ->default('key_value'),
                                
                                Textarea::make('schema')
                                    ->label('JSON Schema')
                                    ->rows(4)
                                    ->placeholder('Define the JSON structure schema'),
                                
                                Textarea::make('default')
                                    ->label('Default Value (JSON)')
                                    ->rows(3)
                                    ->placeholder('{}'),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|json'),
                                
                                KeyValue::make('attributes')
                                    ->label('Additional Attributes')
                                    ->default([]),
                            ]),
                    ]),
                
                // Enum Field Type
                Block::make('enum_field')
                    ->label('Enum Field')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('field_name')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                ]),
                                
                                KeyValue::make('options')
                                    ->label('Enum Options')
                                    ->keyLabel('Value')
                                    ->valueLabel('Display Label')
                                    ->required()
                                    ->addActionLabel('Add Option')
                                    ->default([
                                        'active' => 'Active',
                                        'inactive' => 'Inactive',
                                    ]),
                                
                                Grid::make(3)->schema([
                                    Toggle::make('nullable')
                                        ->label('Nullable')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('fillable')
                                        ->label('Fillable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('multiple')
                                        ->label('Multiple Selection')
                                        ->inline()
                                        ->default(false),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('default')
                                        ->label('Default Value')
                                        ->placeholder('First option value'),
                                    
                                    Select::make('display_type')
                                        ->label('Display Type')
                                        ->options([
                                            'select' => 'Dropdown Select',
                                            'radio' => 'Radio Buttons',
                                            'buttons' => 'Button Group',
                                            'badges' => 'Badges',
                                        ])
                                        ->default('select'),
                                ]),
                                
                                KeyValue::make('colors')
                                    ->label('Option Colors')
                                    ->keyLabel('Value')
                                    ->valueLabel('Color')
                                    ->helperText('Define colors for each option (for badges/buttons)')
                                    ->default([]),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|in:active,inactive,pending'),
                                
                                KeyValue::make('attributes')
                                    ->label('Additional Attributes')
                                    ->default([]),
                            ]),
                    ]),
                
                // Relationship Field Type
                Block::make('relationship_field')
                    ->label('Relationship Field')
                    ->icon('heroicon-o-link')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('field_name')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/')
                                        ->placeholder('user_id, category_id'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                ]),
                                
                                Grid::make(3)->schema([
                                    Select::make('relationship_type')
                                        ->label('Relationship Type')
                                        ->options([
                                            'belongs_to' => 'Belongs To (One to Many)',
                                            'has_one' => 'Has One',
                                            'has_many' => 'Has Many',
                                            'belongs_to_many' => 'Many to Many',
                                            'morph_to' => 'Polymorphic',
                                        ])
                                        ->required()
                                        ->reactive(),
                                    
                                    TextInput::make('related_model')
                                        ->label('Related Model')
                                        ->required()
                                        ->placeholder('User, Category'),
                                    
                                    TextInput::make('related_table')
                                        ->label('Related Table')
                                        ->required()
                                        ->placeholder('users, categories'),
                                ]),
                                
                                Grid::make(4)->schema([
                                    Toggle::make('nullable')
                                        ->label('Nullable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('fillable')
                                        ->label('Fillable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('cascade_delete')
                                        ->label('Cascade Delete')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('cascade_update')
                                        ->label('Cascade Update')
                                        ->inline()
                                        ->default(false),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('foreign_key')
                                        ->label('Foreign Key')
                                        ->placeholder('user_id')
                                        ->helperText('Leave empty for convention'),
                                    
                                    TextInput::make('owner_key')
                                        ->label('Owner Key')
                                        ->placeholder('id')
                                        ->default('id'),
                                ]),
                                
                                TextInput::make('pivot_table')
                                    ->label('Pivot Table')
                                    ->placeholder('category_post')
                                    ->visible(fn ($get) => $get('relationship_type') === 'belongs_to_many'),
                                
                                KeyValue::make('pivot_fields')
                                    ->label('Pivot Table Fields')
                                    ->keyLabel('Field')
                                    ->valueLabel('Type')
                                    ->visible(fn ($get) => $get('relationship_type') === 'belongs_to_many')
                                    ->default([]),
                                
                                Select::make('display_field')
                                    ->label('Display Field in Forms')
                                    ->options([
                                        'select' => 'Select Dropdown',
                                        'searchable_select' => 'Searchable Select',
                                        'radio' => 'Radio Buttons',
                                        'checkbox' => 'Checkboxes',
                                        'tags' => 'Tags Input',
                                    ])
                                    ->default('searchable_select'),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|exists:users,id'),
                                
                                KeyValue::make('attributes')
                                    ->label('Additional Attributes')
                                    ->default([]),
                            ]),
                    ]),
                
                // File/Media Field Type
                Block::make('file_field')
                    ->label('File/Media Field')
                    ->icon('heroicon-o-paper-clip')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('field_name')
                                        ->label('Field Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required(),
                                    
                                    Select::make('file_type')
                                        ->label('File Type')
                                        ->options([
                                            'image' => 'Image',
                                            'document' => 'Document',
                                            'video' => 'Video',
                                            'audio' => 'Audio',
                                            'any' => 'Any File',
                                        ])
                                        ->default('image'),
                                ]),
                                
                                Grid::make(4)->schema([
                                    Toggle::make('multiple')
                                        ->label('Multiple Files')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('nullable')
                                        ->label('Nullable')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('fillable')
                                        ->label('Fillable')
                                        ->inline()
                                        ->default(true),
                                    
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
                                    TextInput::make('max_size')
                                        ->label('Max Size (KB)')
                                        ->numeric()
                                        ->default(2048)
                                        ->minValue(1),
                                    
                                    TextInput::make('min_size')
                                        ->label('Min Size (KB)')
                                        ->numeric()
                                        ->minValue(0),
                                    
                                    Select::make('disk')
                                        ->label('Storage Disk')
                                        ->options([
                                            'local' => 'Local',
                                            'public' => 'Public',
                                            's3' => 'Amazon S3',
                                        ])
                                        ->default('public'),
                                ]),
                                
                                TextInput::make('directory')
                                    ->label('Upload Directory')
                                    ->placeholder('uploads/images')
                                    ->default('uploads'),
                                
                                TextInput::make('accepted_types')
                                    ->label('Accepted File Types')
                                    ->placeholder('image/*, application/pdf, .doc, .docx')
                                    ->helperText('Comma-separated MIME types or extensions'),
                                
                                Grid::make(2)->schema([
                                    TextInput::make('image_width')
                                        ->label('Image Width')
                                        ->numeric()
                                        ->visible(fn ($get) => $get('file_type') === 'image'),
                                    
                                    TextInput::make('image_height')
                                        ->label('Image Height')
                                        ->numeric()
                                        ->visible(fn ($get) => $get('file_type') === 'image'),
                                ]),
                                
                                Toggle::make('optimize_images')
                                    ->label('Optimize Images')
                                    ->inline()
                                    ->default(true)
                                    ->visible(fn ($get) => $get('file_type') === 'image'),
                                
                                TextInput::make('validation')
                                    ->label('Validation Rules')
                                    ->placeholder('required|image|max:2048'),
                                
                                KeyValue::make('attributes')
                                    ->label('Additional Attributes')
                                    ->default([]),
                            ]),
                    ]),
            ])
            ->addActionLabel('Add Database Field')
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->blockNumbers(false)
            ->columnSpanFull();
    }
}