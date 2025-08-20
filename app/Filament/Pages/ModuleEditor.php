<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use App\Filament\Forms\Components\SafeKeyValue as KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\File;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Computed;
use App\Filament\Forms\Components\Module\FieldsBuilder;
use App\Filament\Forms\Components\Module\TestsBuilder;
use App\Filament\Forms\Components\Module\ProcessesBuilder;
use App\Filament\Forms\Components\Module\ScreensBuilder;

class ModuleEditor extends Page implements HasForms
{
    use InteractsWithForms;
    
    // Filament 4 compatible property declarations
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';
    protected static string|null $title = 'Module Editor';
    protected static ?string $slug = 'module-editor';
    protected static bool $shouldRegisterNavigation = false;

    public ?string $module = null;
    public ?string $mode = 'edit';  // Changed default to edit
    public ?array $data = [];
    public ?string $rawContent = '';
    public bool $prettyPrint = true;
    public int $indentSize = 2;
    
    // Form data properties (for Livewire binding)
    public $meta = [];
    public $models = [];
    public $relationships = [];
    public $shared = [];
    public $tests = [];
    
    // Dynamic form fields
    public array $metaData = [];
    public array $schemaDefinitions = [];
    public array $acfFieldAnalysis = [];
    
    protected ?string $modulePath = null;
    protected array $originalData = [];

    // Override view using method instead of property
    public function getView(): string
    {
        return 'filament.pages.module-editor';
    }

    public function mount(): void
    {
        $this->module = request()->get('module');
        $this->mode = 'edit';  // Always edit mode
        
        if ($this->module) {
            // Initialize empty data structure first
            $this->meta = [];
            $this->models = [];
            $this->relationships = [];
            $this->shared = [
                'processes' => [],
                'screens' => [],
                'utilities' => [
                    'helpers' => [],
                    'validators' => [],
                    'transformers' => [],
                ]
            ];
            $this->tests = [];
            
            // Also initialize data array for backward compatibility
            $this->data = [
                'meta' => [],
                'models' => [],
                'relationships' => [],
                'shared' => [
                    'processes' => [],
                    'screens' => [],
                    'utilities' => [
                        'helpers' => [],
                        'validators' => [],
                        'transformers' => [],
                    ]
                ],
                'tests' => [],
            ];
            
            $this->loadModule();
        } else {
            redirect()->route('filament.admin.pages.modules-manager');
        }
    }
    
    protected function getFormStatePath(): ?string
    {
        return 'data';
    }
    
    
    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema($this->getFormSchema());
    }

    protected function loadModule(): void
    {
        $this->modulePath = base_path('.docs/modules/' . $this->module . '.json');
        
        if (!File::exists($this->modulePath)) {
            Notification::make()
                ->title('Module Not Found')
                ->body("Module '{$this->module}' does not exist.")
                ->danger()
                ->send();
            
            redirect()->route('filament.admin.pages.modules-manager');
            return;
        }

        $content = File::get($this->modulePath);
        $this->rawContent = $content;
        $jsonData = json_decode($content, true) ?? [];
        $this->originalData = $jsonData;
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Notification::make()
                ->title('Invalid JSON')
                ->body('The module file contains invalid JSON: ' . json_last_error_msg())
                ->warning()
                ->persistent()
                ->send();
        }
        
        // Clean up data for KeyValue components
        $cleanedData = $this->cleanDataForKeyValue($jsonData);
        
        // Initialize relationships properly with empty arrays for KeyValue fields
        if (isset($cleanedData['relationships']) && is_array($cleanedData['relationships'])) {
            foreach ($cleanedData['relationships'] as $index => $relationship) {
                // Ensure constraints is always an array for KeyValue component
                if (!isset($relationship['constraints'])) {
                    $cleanedData['relationships'][$index]['constraints'] = [];
                } elseif (!is_array($relationship['constraints'])) {
                    $cleanedData['relationships'][$index]['constraints'] = [];
                } elseif (empty($relationship['constraints'])) {
                    $cleanedData['relationships'][$index]['constraints'] = [];
                }
            }
        } else {
            $cleanedData['relationships'] = [];
        }
        
        // Initialize shared utilities properly
        if (!isset($cleanedData['shared'])) {
            $cleanedData['shared'] = [];
        }
        if (!isset($cleanedData['shared']['utilities'])) {
            $cleanedData['shared']['utilities'] = [];
        }
        
        // Ensure all KeyValue fields are arrays and never null
        $keyValueFields = [
            'helpers' => [],
            'validators' => [],
            'transformers' => []
        ];
        
        foreach ($keyValueFields as $field => $default) {
            if (!isset($cleanedData['shared']['utilities'][$field])) {
                $cleanedData['shared']['utilities'][$field] = $default;
            } elseif (!is_array($cleanedData['shared']['utilities'][$field])) {
                $cleanedData['shared']['utilities'][$field] = $default;
            } elseif (empty($cleanedData['shared']['utilities'][$field])) {
                // Keep empty arrays as is, they're valid for KeyValue
                $cleanedData['shared']['utilities'][$field] = [];
            }
        }
        
        // Initialize shared screens and processes
        if (!isset($cleanedData['shared']['screens'])) {
            $cleanedData['shared']['screens'] = [];
        }
        if (!isset($cleanedData['shared']['processes'])) {
            $cleanedData['shared']['processes'] = [];
        }
        
        // Initialize tests
        if (!isset($cleanedData['tests'])) {
            $cleanedData['tests'] = [];
        }
        
        // Initialize models array
        if (!isset($cleanedData['models']) || !is_array($cleanedData['models'])) {
            $cleanedData['models'] = [];
        }
        
        // Parse data into structured fields for the form
        $this->metaData = $cleanedData['meta'] ?? [];
        $this->schemaDefinitions = $cleanedData['meta']['schema_definition'] ?? [];
        $this->acfFieldAnalysis = $cleanedData['acf_field_analysis'] ?? [];
        
        // Add original_model_slug tracking to existing models
        $modelsWithTracking = [];
        $models = $cleanedData['models'] ?? [];
        foreach ($models as $model) {
            $modelWithTracking = $model;
            $modelWithTracking['original_model_slug'] = $model['model_slug'] ?? '';
            $modelsWithTracking[] = $modelWithTracking;
        }
        
        // Set individual Livewire properties for binding
        $this->meta = $this->metaData;
        $this->models = $modelsWithTracking;
        $this->relationships = $cleanedData['relationships'] ?? [];
        $this->shared = [
            'processes' => $cleanedData['shared']['processes'] ?? [],
            'screens' => $cleanedData['shared']['screens'] ?? [],
            'utilities' => [
                'helpers' => $cleanedData['shared']['utilities']['helpers'] ?? [],
                'validators' => $cleanedData['shared']['utilities']['validators'] ?? [],
                'transformers' => $cleanedData['shared']['utilities']['transformers'] ?? [],
            ]
        ];
        $this->tests = $cleanedData['tests'] ?? [];
        
        // Structure data for form binding with proper defaults
        $this->data = [
            'meta' => $this->meta,
            'models' => $this->models,
            'relationships' => $this->relationships,
            'shared' => $this->shared,
            'tests' => $this->tests,
        ];
        
        // Initialize form state properly
        $this->form->fill($this->data);
    }

    protected function cleanDataForKeyValue($data): array
    {
        if (!is_array($data)) {
            return [];
        }
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Recursively clean nested arrays
                $data[$key] = $this->cleanDataForKeyValue($value);
            } elseif ($value === null || (is_object($value) && empty((array)$value))) {
                // Convert null or empty objects to empty arrays for KeyValue components
                $data[$key] = [];
            }
        }
        
        // Ensure specific KeyValue fields are arrays
        $keyValuePaths = [
            'relationships' => ['constraints'],
            'shared' => ['utilities' => ['helpers', 'validators', 'transformers']],
        ];
        
        // Check relationships
        if (isset($data['relationships']) && is_array($data['relationships'])) {
            foreach ($data['relationships'] as $index => $relationship) {
                if (!isset($relationship['constraints']) || !is_array($relationship['constraints'])) {
                    $data['relationships'][$index]['constraints'] = [];
                }
            }
        }
        
        // Check shared utilities
        if (!isset($data['shared'])) {
            $data['shared'] = [];
        }
        if (!isset($data['shared']['utilities'])) {
            $data['shared']['utilities'] = [];
        }
        if (!isset($data['shared']['utilities']['helpers']) || !is_array($data['shared']['utilities']['helpers'])) {
            $data['shared']['utilities']['helpers'] = [];
        }
        if (!isset($data['shared']['utilities']['validators']) || !is_array($data['shared']['utilities']['validators'])) {
            $data['shared']['utilities']['validators'] = [];
        }
        if (!isset($data['shared']['utilities']['transformers']) || !is_array($data['shared']['utilities']['transformers'])) {
            $data['shared']['utilities']['transformers'] = [];
        }
        
        return $data;
    }
    
    protected function getFormSchema(): array
    {
        return [
            // Module Information without title
            Grid::make(12)
                ->schema([
                    TextInput::make('meta.module')
                        ->label('Module Name')
                        ->required()
                        ->placeholder('properties, units, contracts')
                        ->regex('/^[a-z_]+$/')
                        ->validationMessages([
                            'regex' => 'Only lowercase English letters and underscores are allowed',
                        ])
                        ->disabled($this->module !== null) // Disable when editing
                        ->columnSpan(2),
                    
                    TextInput::make('meta.version')
                        ->label('Version')
                        ->placeholder('1.0.0')
                        ->default('1.0.0')
                        ->columnSpan(1),
                    
                    TextInput::make('meta.description')
                        ->label('Description')
                        ->required()
                        ->placeholder('Detailed description of what this module does')
                        ->columnSpan(9),
                ]),
            
            // Main Wizard
            Wizard::make([
                Step::make('Models')
                    ->label('Models Configuration')
                    ->schema([
                        Repeater::make('models')
                            ->label('')
                            ->schema(function () {
                                return [
                                    Hidden::make('original_model_slug'),
                                    
                                    TextInput::make('model_slug')
                                        ->label('Model Slug')
                                        ->required()
                                        ->placeholder('property, unit, tenant')
                                        ->regex('/^[a-z_]+$/')
                                        ->validationMessages([
                                            'regex' => 'Only lowercase English letters and underscores are allowed',
                                        ])
                                        ->reactive()
                                        ->columnSpanFull(),
                                    
                                    // Model Internal Wizard
                                    Wizard::make([
                                        Step::make('Database Schema')
                                            ->label('Database Configuration')
                                            ->schema([
                                                Wizard::make([
                                                    Step::make('Fields')
                                                        ->label('Database Fields')
                                                        ->schema([
                                                            FieldsBuilder::make('database_schema.fields'),
                                                        ]),

                                                    Step::make('Indexes')
                                                        ->label('Table Indexes')
                                                        ->schema([
                                                            Repeater::make('database_schema.indexes')
                                                                ->disableLabel(true)
                                                                ->schema([
                                                                    TextInput::make('index_name')
                                                                        ->label('Index Name')
                                                                        ->required(),
                                                                    TextInput::make('columns')
                                                                        ->label('Columns')
                                                                        ->placeholder('column1, column2')
                                                                        ->required(),
                                                                    Select::make('type')
                                                                        ->label('Index Type')
                                                                        ->options([
                                                                            'index' => 'Regular Index',
                                                                            'unique' => 'Unique Index',
                                                                            'fulltext' => 'Fulltext Index',
                                                                            'spatial' => 'Spatial Index',
                                                                        ])
                                                                        ->default('index'),
                                                                ])
                                                                ->columns(3)
                                                                ->itemLabel(fn ($state) => $state['index_name'] ?? 'Index')
                                                                ->collapsible()
                                                                ->collapsed()
                                                                ->addActionLabel('Add Index'),
                                                        ]),
                                                    
                                                    Step::make('Constraints')
                                                        ->label('Database Constraints')
                                                        ->schema([
                                                            Repeater::make('database_schema.constraints')
                                                                ->label('')
                                                                ->schema([
                                                                    TextInput::make('constraint_name')
                                                                        ->label('Constraint Name')
                                                                        ->required(),
                                                                    Select::make('type')
                                                                        ->label('Constraint Type')
                                                                        ->options([
                                                                            'foreign' => 'Foreign Key',
                                                                            'check' => 'Check Constraint',
                                                                            'unique' => 'Unique Constraint',
                                                                        ])
                                                                        ->reactive(),
                                                                    TextInput::make('definition')
                                                                        ->label('Definition')
                                                                        ->placeholder('table.column or CHECK expression')
                                                                        ->required(),
                                                                ])
                                                                ->columns(3)
                                                                ->itemLabel(fn ($state) => $state['constraint_name'] ?? 'Constraint')
                                                                ->collapsible()
                                                                ->collapsed()
                                                                ->addActionLabel('Add Constraint'),
                                                        ]),
                                                ])
                                                ->persistStepInQueryString()
                                                ->skippable()
                                                ->columnSpanFull(),
                                            ]),
                                        
                                        Step::make('Tests')
                                            ->label('Model Tests')
                                            ->schema([
                                                TestsBuilder::make('tests'),
                                            ]),
                                        
                                        Step::make('Processes')
                                            ->label('Business Processes')
                                            ->schema([
                                                ProcessesBuilder::make('processes'),
                                            ]),
                                        
                                        Step::make('Screens')
                                            ->label('UI Screens')
                                            ->schema([
                                                ScreensBuilder::make('screens'),
                                            ]),
                                    ])
                                    ->persistStepInQueryString()
                                    ->skippable()
                                    ->columnSpanFull(),
                                ];
                            })
                            ->itemLabel(function ($state) {
                                return isset($state['model_slug']) && !empty($state['model_slug']) 
                                    ? ucfirst($state['model_slug']) 
                                    : 'New Model';
                            })
                            ->collapsible()
                            ->collapsed()
                            ->cloneable()
                            ->reorderable()
                            ->addActionLabel('Add Model')
                            ->columnSpanFull(),
                    ]),
                
                Step::make('Relationships')
                    ->label('Module Relationships')
                    ->schema([
                        Repeater::make('relationships')
                            ->label('')
                            ->schema([
                                TextInput::make('relationship_slug')
                                    ->label('Relationship Slug')
                                    ->required(),
                                
                                Select::make('type')
                                    ->label('Type')
                                    ->options([
                                        'one_to_one' => 'One to One',
                                        'one_to_many' => 'One to Many',
                                        'many_to_many' => 'Many to Many',
                                        'polymorphic' => 'Polymorphic',
                                        'morphToMany' => 'Morph To Many',
                                    ])
                                    ->required(),
                                
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('1st_side')
                                            ->label('First Model')
                                            ->required(),
                                        
                                        TextInput::make('2nd_side')
                                            ->label('Second Model')
                                            ->required(),
                                    ]),
                                
                                Repeater::make('constraints')
                                    ->label('Constraints')
                                    ->schema([
                                        TextInput::make('key')
                                            ->label('Constraint'),
                                        TextInput::make('value')
                                            ->label('Value'),
                                    ])
                                    ->columns(2)
                                    ->default([]),
                            ])
                            ->itemLabel(fn ($state) => isset($state['relationship_slug']) ? $state['relationship_slug'] : 'Relationship')
                            ->collapsible()
                            ->collapsed()
                            ->addActionLabel('Add Relationship')
                            ->columnSpanFull(),
                    ]),
                
                Step::make('Shared')
                    ->label('Shared Components')
                    ->schema([
                        ProcessesBuilder::make('shared.processes'),
                        
                        ScreensBuilder::make('shared.screens'),
                        
                        Section::make('Utilities')
                            ->schema([
                                Repeater::make('shared.utilities.helpers')
                                    ->label('Helper Functions')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Function Name'),
                                        TextInput::make('description')
                                            ->label('Description'),
                                    ])
                                    ->columns(2)
                                    ->default([]),
                                
                                Repeater::make('shared.utilities.validators')
                                    ->label('Custom Validators')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Validator Name'),
                                        TextInput::make('logic')
                                            ->label('Validation Logic'),
                                    ])
                                    ->columns(2)
                                    ->default([]),
                                
                                Repeater::make('shared.utilities.transformers')
                                    ->label('Data Transformers')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Transformer Name'),
                                        TextInput::make('logic')
                                            ->label('Transformation Logic'),
                                    ])
                                    ->columns(2)
                                    ->default([]),
                            ])
                            ->collapsed(),
                    ]),
                
                Step::make('Tests')
                    ->label('Module Tests')
                    ->schema([
                        TestsBuilder::make('tests'),
                    ]),
            ])
            ->persistStepInQueryString()
            ->skippable()
            ->columnSpanFull(),
        ];
    }





    public function save(): void
    {
        try {
            // Ensure module path is set
            if (empty($this->modulePath)) {
                $this->modulePath = base_path('.docs/modules/' . $this->module . '.json');
            }
            
            // Check if file exists
            if (!File::exists($this->modulePath)) {
                Notification::make()
                    ->title('Save Failed')
                    ->body("Module file not found: {$this->module}.json")
                    ->danger()
                    ->send();
                return;
            }
            
            // Get the current form state - this includes the user's changes
            $formData = $this->form->getState();
            
            // Deep clean the form data to remove any corruption
            $formData = $this->deepCleanFormData($formData);
            
            // Clean up nested structure issues
            $formData = $this->cleanFormData($formData);
            
            // Load the original file to preserve ACF data and get original models for comparison
            $originalContent = File::get($this->modulePath);
            $originalData = json_decode($originalContent, true) ?? [];
            
            $completeData = [
                'meta' => $formData['meta'] ?? [],
            ];
            
            // Preserve schema_definition from original file
            if (!empty($originalData['meta']['schema_definition'])) {
                $completeData['meta']['schema_definition'] = $originalData['meta']['schema_definition'];
            }
            
            // Always preserve ACF field analysis from original file
            if (!empty($originalData['acf_field_analysis'])) {
                $completeData['acf_field_analysis'] = $originalData['acf_field_analysis'];
            }
            
            if (!empty($formData['models'])) {
                // Handle model renaming before saving
                $processedModels = $this->handleModelRenaming($formData['models'], $originalData['models'] ?? []);
                $completeData['models'] = $processedModels;
            } else {
                $completeData['models'] = [];
            }
            
            // Copy other data structures
            if (isset($formData['relationships'])) {
                $completeData['relationships'] = $formData['relationships'];
            }
            if (isset($formData['shared'])) {
                $completeData['shared'] = $formData['shared'];
            }
            if (isset($formData['tests'])) {
                $completeData['tests'] = $formData['tests'];
            }
            
            $this->createBackup();
            
            $jsonContent = json_encode(
                $completeData,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            
            File::put($this->modulePath, $jsonContent);
            
            Notification::make()
                ->title('Module Saved')
                ->body('The module has been saved successfully.')
                ->success()
                ->actions([
                    \Filament\Actions\Action::make('view')
                        ->label('View Module')
                        ->url(static::getUrl(['module' => $this->module]))
                        ->button(),
                ])
                ->persistent()
                ->send();
            
            $this->loadModule();
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Save Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function deepCleanFormData(array $data): array
    {
        // Start with the expected structure
        $cleanData = [
            'meta' => [],
            'models' => [],
            'relationships' => [],
            'shared' => [
                'processes' => [],
                'screens' => [],
                'utilities' => [
                    'helpers' => [],
                    'validators' => [],
                    'transformers' => []
                ]
            ],
            'tests' => [
                'unit' => [],
                'feature' => [],
                'playwright_mcp' => []
            ]
        ];
        
        // Extract meta data
        if (isset($data['meta']) && is_array($data['meta'])) {
            $cleanData['meta'] = [
                'module' => $data['meta']['module'] ?? '',
                'version' => $data['meta']['version'] ?? '1.0.0',
                'description' => $data['meta']['description'] ?? '',
            ];
            // Preserve schema_definition if it exists and is valid
            if (isset($data['meta']['schema_definition']) && is_array($data['meta']['schema_definition']) && !isset($data['meta']['schema_definition']['shared'])) {
                $cleanData['meta']['schema_definition'] = $data['meta']['schema_definition'];
            }
        }
        
        // Extract models
        if (isset($data['models']) && is_array($data['models'])) {
            $cleanData['models'] = $this->extractCleanModels($data['models']);
        }
        
        // Extract relationships
        if (isset($data['relationships']) && is_array($data['relationships']) && !isset($data['relationships']['shared'])) {
            $cleanData['relationships'] = $this->extractCleanRelationships($data['relationships']);
        }
        
        // Extract shared components
        if (isset($data['shared']) && is_array($data['shared'])) {
            $cleanData['shared'] = $this->extractCleanShared($data['shared']);
        }
        
        // Extract tests
        if (isset($data['tests']) && is_array($data['tests'])) {
            $cleanData['tests'] = $this->extractCleanTests($data['tests']);
        }
        
        return $cleanData;
    }
    
    protected function extractCleanModels(array $models): array
    {
        $cleanModels = [];
        foreach ($models as $model) {
            if (!is_array($model) || !isset($model['model_slug']) || empty($model['model_slug'])) {
                continue;
            }
            
            $cleanModel = [
                'model_slug' => $model['model_slug'],
                'original_model_slug' => $model['original_model_slug'] ?? '',
                'database_schema' => [
                    'fields' => [],
                    'indexes' => [],
                    'constraints' => []
                ],
                'tests' => [],
                'processes' => [],
                'screens' => []
            ];
            
            // Extract database schema
            if (isset($model['database_schema']) && is_array($model['database_schema'])) {
                if (isset($model['database_schema']['fields']) && is_array($model['database_schema']['fields'])) {
                    $cleanModel['database_schema']['fields'] = $model['database_schema']['fields'];
                }
                if (isset($model['database_schema']['indexes']) && is_array($model['database_schema']['indexes'])) {
                    $cleanModel['database_schema']['indexes'] = $model['database_schema']['indexes'];
                }
                if (isset($model['database_schema']['constraints']) && is_array($model['database_schema']['constraints'])) {
                    $cleanModel['database_schema']['constraints'] = $model['database_schema']['constraints'];
                }
            }
            
            // Extract other arrays
            if (isset($model['tests']) && is_array($model['tests']) && !isset($model['tests']['shared'])) {
                $cleanModel['tests'] = $model['tests'];
            }
            if (isset($model['processes']) && is_array($model['processes']) && !isset($model['processes']['shared'])) {
                $cleanModel['processes'] = $model['processes'];
            }
            if (isset($model['screens']) && is_array($model['screens']) && !isset($model['screens']['shared'])) {
                $cleanModel['screens'] = $model['screens'];
            }
            
            $cleanModels[] = $cleanModel;
        }
        return $cleanModels;
    }
    
    protected function extractCleanRelationships(array $relationships): array
    {
        $cleanRelationships = [];
        foreach ($relationships as $relationship) {
            if (!is_array($relationship)) {
                continue;
            }
            
            $cleanRelationship = [
                'relationship_slug' => $relationship['relationship_slug'] ?? '',
                'type' => $relationship['type'] ?? '',
                '1st_side' => $relationship['1st_side'] ?? '',
                '2nd_side' => $relationship['2nd_side'] ?? '',
                'constraints' => []
            ];
            
            if (isset($relationship['constraints']) && is_array($relationship['constraints'])) {
                $cleanRelationship['constraints'] = $relationship['constraints'];
            }
            
            if (!empty($cleanRelationship['relationship_slug'])) {
                $cleanRelationships[] = $cleanRelationship;
            }
        }
        return $cleanRelationships;
    }
    
    protected function extractCleanShared(array $shared): array
    {
        $cleanShared = [
            'processes' => [],
            'screens' => [],
            'utilities' => [
                'helpers' => [],
                'validators' => [],
                'transformers' => []
            ]
        ];
        
        // Extract processes
        if (isset($shared['processes']) && is_array($shared['processes']) && !isset($shared['processes']['shared'])) {
            $cleanShared['processes'] = $shared['processes'];
        }
        
        // Extract screens
        if (isset($shared['screens']) && is_array($shared['screens']) && !isset($shared['screens']['shared'])) {
            $cleanShared['screens'] = $shared['screens'];
        }
        
        // Extract utilities
        if (isset($shared['utilities']) && is_array($shared['utilities'])) {
            if (isset($shared['utilities']['helpers']) && is_array($shared['utilities']['helpers']) && !isset($shared['utilities']['helpers']['shared'])) {
                $cleanShared['utilities']['helpers'] = $shared['utilities']['helpers'];
            }
            if (isset($shared['utilities']['validators']) && is_array($shared['utilities']['validators']) && !isset($shared['utilities']['validators']['shared'])) {
                $cleanShared['utilities']['validators'] = $shared['utilities']['validators'];
            }
            if (isset($shared['utilities']['transformers']) && is_array($shared['utilities']['transformers']) && !isset($shared['utilities']['transformers']['shared'])) {
                $cleanShared['utilities']['transformers'] = $shared['utilities']['transformers'];
            }
        }
        
        return $cleanShared;
    }
    
    protected function extractCleanTests(array $tests): array
    {
        $cleanTests = [
            'unit' => [],
            'feature' => [],
            'playwright_mcp' => []
        ];
        
        if (isset($tests['unit']) && is_array($tests['unit']) && !isset($tests['unit']['shared'])) {
            $cleanTests['unit'] = $tests['unit'];
        }
        if (isset($tests['feature']) && is_array($tests['feature']) && !isset($tests['feature']['shared'])) {
            $cleanTests['feature'] = $tests['feature'];
        }
        if (isset($tests['playwright_mcp']) && is_array($tests['playwright_mcp']) && !isset($tests['playwright_mcp']['shared'])) {
            $cleanTests['playwright_mcp'] = $tests['playwright_mcp'];
        }
        
        return $cleanTests;
    }
    
    protected function cleanFormData(array $data): array
    {
        // Clean up models if they have nested structure issues
        if (isset($data['models']) && is_array($data['models'])) {
            $cleanedModels = [];
            foreach ($data['models'] as $model) {
                // Skip if model doesn't have a model_slug
                if (!isset($model['model_slug']) || empty($model['model_slug'])) {
                    continue;
                }
                
                // Clean nested structures - remove any 'shared' key that shouldn't be there
                $cleanedModel = [
                    'model_slug' => $model['model_slug'],
                    'original_model_slug' => $model['original_model_slug'] ?? '',
                    'database_schema' => $this->cleanNestedStructure($model['database_schema'] ?? []),
                    'tests' => $this->cleanNestedStructure($model['tests'] ?? []),
                    'processes' => $this->cleanNestedStructure($model['processes'] ?? []),
                    'screens' => $this->cleanNestedStructure($model['screens'] ?? []),
                ];
                
                $cleanedModels[] = $cleanedModel;
            }
            $data['models'] = $cleanedModels;
        }
        
        // Clean relationships
        if (isset($data['relationships'])) {
            if (is_array($data['relationships']) && isset($data['relationships']['shared'])) {
                // This is corrupted, reset to empty array
                $data['relationships'] = [];
            }
        }
        
        // Clean shared section
        if (isset($data['shared'])) {
            $cleanedShared = [
                'processes' => [],
                'screens' => [],
                'utilities' => [
                    'helpers' => [],
                    'validators' => [],
                    'transformers' => []
                ]
            ];
            
            // Try to preserve valid data if possible
            if (is_array($data['shared'])) {
                if (isset($data['shared']['processes']) && is_array($data['shared']['processes'])) {
                    // Check if processes is corrupted
                    if (!isset($data['shared']['processes']['shared'])) {
                        $cleanedShared['processes'] = $data['shared']['processes'];
                    }
                }
                if (isset($data['shared']['screens']) && is_array($data['shared']['screens'])) {
                    // Check if screens is corrupted
                    if (!isset($data['shared']['screens']['shared'])) {
                        $cleanedShared['screens'] = $data['shared']['screens'];
                    }
                }
                if (isset($data['shared']['utilities']) && is_array($data['shared']['utilities'])) {
                    // Clean utilities
                    if (isset($data['shared']['utilities']['helpers']) && !is_array($data['shared']['utilities']['helpers'])) {
                        $cleanedShared['utilities']['helpers'] = [];
                    } elseif (isset($data['shared']['utilities']['helpers']) && !isset($data['shared']['utilities']['helpers']['shared'])) {
                        $cleanedShared['utilities']['helpers'] = $data['shared']['utilities']['helpers'];
                    }
                    
                    if (isset($data['shared']['utilities']['validators']) && !is_array($data['shared']['utilities']['validators'])) {
                        $cleanedShared['utilities']['validators'] = [];
                    } elseif (isset($data['shared']['utilities']['validators']) && !isset($data['shared']['utilities']['validators']['shared'])) {
                        $cleanedShared['utilities']['validators'] = $data['shared']['utilities']['validators'];
                    }
                    
                    if (isset($data['shared']['utilities']['transformers']) && !is_array($data['shared']['utilities']['transformers'])) {
                        $cleanedShared['utilities']['transformers'] = [];
                    } elseif (isset($data['shared']['utilities']['transformers']) && !isset($data['shared']['utilities']['transformers']['shared'])) {
                        $cleanedShared['utilities']['transformers'] = $data['shared']['utilities']['transformers'];
                    }
                }
            }
            
            $data['shared'] = $cleanedShared;
        }
        
        // Clean tests section
        if (isset($data['tests'])) {
            if (is_array($data['tests'])) {
                // Check if tests structure is corrupted
                if (isset($data['tests']['shared']) || 
                    (isset($data['tests']['unit']) && isset($data['tests']['unit']['shared'])) ||
                    (isset($data['tests']['feature']) && isset($data['tests']['feature']['shared'])) ||
                    (isset($data['tests']['playwright_mcp']) && isset($data['tests']['playwright_mcp']['shared']))) {
                    // Reset to default structure
                    $data['tests'] = [
                        'unit' => [],
                        'feature' => [],
                        'playwright_mcp' => []
                    ];
                }
            }
        }
        
        return $data;
    }
    
    protected function cleanNestedStructure($structure): array|string
    {
        if (!is_array($structure)) {
            return $structure;
        }
        
        // For special cases where the entire structure is corrupted
        if (isset($structure['shared']) && isset($structure['shared']['utilities'])) {
            // This is a corrupted structure, return empty array
            return [];
        }
        
        // Clean each sub-structure recursively
        $cleaned = [];
        foreach ($structure as $key => $value) {
            if ($key === 'shared') {
                continue; // Skip any 'shared' keys at this level
            }
            
            if (is_array($value)) {
                // Recursively clean nested arrays
                $cleanedValue = $this->cleanNestedStructure($value);
                // Only add if not empty after cleaning
                if (!empty($cleanedValue) || $cleanedValue === []) {
                    $cleaned[$key] = $cleanedValue;
                }
            } else {
                $cleaned[$key] = $value;
            }
        }
        
        return $cleaned;
    }

    protected function handleModelRenaming(array $newModels, array $originalModels): array
    {
        
        $processedModels = [];
        $usedNames = [];
        
        foreach ($newModels as $index => $newModel) {
            $originalSlug = $newModel['original_model_slug'] ?? null;
            $newSlug = $newModel['model_slug'] ?? null;
            
            // Validate model slug is not empty
            if (empty($newSlug)) {
                throw new \InvalidArgumentException("Model slug cannot be empty at index {$index}");
            }
            
            // Check for duplicate model names
            if (in_array($newSlug, $usedNames)) {
                throw new \InvalidArgumentException("Duplicate model slug '{$newSlug}' found. Each model must have a unique name.");
            }
            $usedNames[] = $newSlug;
            
            // If model was renamed, find and migrate the original data
            if (!empty($originalSlug) && $originalSlug !== $newSlug && !empty($originalModels)) {
                $originalModel = $this->findModelBySlug($originalModels, $originalSlug);
                if ($originalModel) {
                    // Preserve all original data but update the slug
                    $migratedModel = $originalModel;
                    $migratedModel['model_slug'] = $newSlug;
                    
                    // Merge with new form data, preserving nested structures
                    $processedModel = $this->mergeModelData($migratedModel, $newModel);
                    $processedModels[] = $processedModel;
                    
                    continue;
                }
            }
            
            // For new models or models that weren't renamed, just use the new data
            // Remove the tracking field from final output
            $cleanModel = $newModel;
            unset($cleanModel['original_model_slug']);
            $processedModels[] = $cleanModel;
        }
        
        
        return $processedModels;
    }
    
    protected function findModelBySlug(array $models, string $slug): ?array
    {
        foreach ($models as $model) {
            if (isset($model['model_slug']) && $model['model_slug'] === $slug) {
                return $model;
            }
        }
        return null;
    }
    
    protected function mergeModelData(array $originalModel, array $newModel): array
    {
        // Start with the new model data from the form
        $merged = $newModel;
        
        // Update the model slug
        $merged['model_slug'] = $newModel['model_slug'];
        
        // For nested structures, use the new data if provided, otherwise keep original
        // This preserves user edits while maintaining data from the original if not edited
        
        // Database schema - use new if exists, otherwise original
        if (!isset($newModel['database_schema']) || empty($newModel['database_schema']['fields'])) {
            $merged['database_schema'] = $originalModel['database_schema'] ?? [
                'fields' => [],
                'indexes' => [],
                'constraints' => []
            ];
        }
        
        // Tests - use new if exists, otherwise original
        if (!isset($newModel['tests']) || (is_array($newModel['tests']) && empty($newModel['tests']))) {
            $merged['tests'] = $originalModel['tests'] ?? [];
        }
        
        // Processes - use new if exists, otherwise original
        if (!isset($newModel['processes']) || (is_array($newModel['processes']) && empty($newModel['processes']))) {
            $merged['processes'] = $originalModel['processes'] ?? [];
        }
        
        // Screens - use new if exists, otherwise original
        if (!isset($newModel['screens']) || (is_array($newModel['screens']) && empty($newModel['screens']))) {
            $merged['screens'] = $originalModel['screens'] ?? [];
        }
        
        // Remove the tracking field from final output
        unset($merged['original_model_slug']);
        
        return $merged;
    }

    protected function createBackup(): void
    {
        // Skip backup if module path is not set
        if (empty($this->modulePath) || !File::exists($this->modulePath)) {
            return;
        }
        
        $backupDir = base_path('.docs/modules/backups');
        
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupPath = $backupDir . '/' . $this->module . '_' . $timestamp . '.json';
        
        File::copy($this->modulePath, $backupPath);
    }




    public function getTitle(): string | Htmlable
    {
        return 'Module Editor: ' . ($this->module ?? 'Unknown');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Save Changes')
                ->icon('heroicon-o-check')
                ->action('save')
                ->keyBindings(['mod+s'])
                ->color('success'),
        ];
    }
}