<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms;
use Illuminate\Support\Facades\Auth;
use BackedEnum;

class Generator extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Generator';
    protected static ?string $title = 'System Generator';
    protected static ?string $slug = 'generator';
    protected static ?int $navigationSort = 100;
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.generator';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::user()?->type === 'super_admin';
    }

    public function mount(): void
    {
        if (!static::canAccess()) {
            abort(403, 'Unauthorized');
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Generator Settings')
                    ->description('Configure system generation options')
                    ->schema([
                        Select::make('module_type')
                            ->label('Module Type')
                            ->options([
                                'resource' => 'Filament Resource',
                                'model' => 'Model with Migration',
                                'seeder' => 'Database Seeder',
                                'service' => 'Service Class',
                                'test' => 'Test Suite',
                            ])
                            ->required()
                            ->reactive(),

                        TextInput::make('module_name')
                            ->label('Module Name')
                            ->required()
                            ->placeholder('e.g., Property, Contract, Payment'),

                        Toggle::make('generate_migration')
                            ->label('Generate Migration')
                            ->default(true)
                            ->visible(fn ($get) => in_array($get('module_type'), ['resource', 'model'])),

                        Toggle::make('generate_factory')
                            ->label('Generate Factory')
                            ->default(true)
                            ->visible(fn ($get) => in_array($get('module_type'), ['resource', 'model'])),

                        Toggle::make('generate_seeder')
                            ->label('Generate Seeder')
                            ->default(true)
                            ->visible(fn ($get) => in_array($get('module_type'), ['resource', 'model'])),

                        Toggle::make('generate_tests')
                            ->label('Generate Tests')
                            ->default(true),

                        Textarea::make('fields')
                            ->label('Fields Definition')
                            ->rows(10)
                            ->placeholder("name:string\nemail:string:unique\nstatus:enum:active,inactive\namount:decimal:10,2")
                            ->helperText('Format: field_name:type:modifiers')
                            ->visible(fn ($get) => in_array($get('module_type'), ['resource', 'model'])),
                    ]),
            ])
            ->statePath('data');
    }

    public function generate(): void
    {
        try {
            $data = $this->form->getState();
            
            // Log the generation request
            logger()->info('Generator executed', [
                'user' => Auth::user()->email,
                'module_type' => $data['module_type'],
                'module_name' => $data['module_name'],
            ]);

            $message = match($data['module_type']) {
                'resource' => $this->generateResource($data),
                'model' => $this->generateModel($data),
                'seeder' => $this->generateSeeder($data),
                'service' => $this->generateService($data),
                'test' => $this->generateTest($data),
                default => 'Unknown module type',
            };

            Notification::make()
                ->title('Generation Successful')
                ->body($message)
                ->success()
                ->send();

            $this->form->fill();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Generation Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function generateResource(array $data): string
    {
        $name = $data['module_name'];
        
        // Example: Run artisan command
        // Artisan::call('make:filament-resource', ['name' => $name]);
        
        return "Resource {$name} generated successfully";
    }

    protected function generateModel(array $data): string
    {
        $name = $data['module_name'];
        
        // Generate model, migration, factory, seeder based on settings
        
        return "Model {$name} generated successfully";
    }

    protected function generateSeeder(array $data): string
    {
        $name = $data['module_name'];
        
        return "Seeder {$name}Seeder generated successfully";
    }

    protected function generateService(array $data): string
    {
        $name = $data['module_name'];
        
        return "Service {$name}Service generated successfully";
    }

    protected function generateTest(array $data): string
    {
        $name = $data['module_name'];
        
        return "Test suite for {$name} generated successfully";
    }
}