<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\File;
use Illuminate\Contracts\Support\Htmlable;
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
    public array $data = [];
    public ?string $rawContent = '';
    public bool $prettyPrint = true;
    public int $indentSize = 2;
    
    // Dynamic form fields
    public array $metaData = [];
    public array $schemaDefinitions = [];
    public array $acfFieldAnalysis = [];
    public array $models = [];
    
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
            $this->loadModule();
        } else {
            redirect()->route('filament.admin.pages.modules-manager');
        }
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
        $this->data = json_decode($content, true) ?? [];
        $this->originalData = $this->data;
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Notification::make()
                ->title('Invalid JSON')
                ->body('The module file contains invalid JSON: ' . json_last_error_msg())
                ->warning()
                ->persistent()
                ->send();
        }
        
        // Parse data into structured fields
        $this->metaData = $this->data['meta'] ?? [];
        $this->schemaDefinitions = $this->data['meta']['schema_definition'] ?? [];
        $this->acfFieldAnalysis = $this->data['acf_field_analysis'] ?? [];
        $this->models = $this->data['models'] ?? [];
        
        $this->data = [
            'meta' => $this->metaData,
            'schema_definitions' => $this->schemaDefinitions,
            'acf_field_analysis' => $this->acfFieldAnalysis,
            'models' => $this->models,
            'rawContent' => $this->rawContent,
            'prettyPrint' => $this->prettyPrint,
        ];
        
        if (method_exists($this, 'form') && $this->form) {
            $this->form->fill($this->data);
        }
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
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
                                                                ->label('')
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
                                
                                KeyValue::make('constraints')
                                    ->label('Constraints')
                                    ->keyLabel('Constraint')
                                    ->valueLabel('Value'),
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
                                KeyValue::make('shared.utilities.helpers')
                                    ->label('Helper Functions')
                                    ->keyLabel('Function Name')
                                    ->valueLabel('Description'),
                                
                                KeyValue::make('shared.utilities.validators')
                                    ->label('Custom Validators')
                                    ->keyLabel('Validator Name')
                                    ->valueLabel('Validation Logic'),
                                
                                KeyValue::make('shared.utilities.transformers')
                                    ->label('Data Transformers')
                                    ->keyLabel('Transformer Name')
                                    ->valueLabel('Transformation Logic'),
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
            $formData = $this->form->getState();
            
            // Load the original file to preserve ACF data
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
                $completeData['models'] = $formData['models'];
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
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label('View Module')
                        ->url(static::getUrl(['module' => $this->module]))
                        ->button(),
                ])
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

    protected function createBackup(): void
    {
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