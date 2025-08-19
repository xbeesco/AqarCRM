<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\Action as TableAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Sushi\Sushi;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Pages\ModuleEditor;

// Model for modules data using Sushi
class ModuleData extends Model
{
    use Sushi;
    
    protected $schema = [
        'id' => 'string',
        'name' => 'string',
        'path' => 'string',
        'description' => 'text',
        'full_description' => 'text',
        'file_size' => 'integer',
        'last_modified' => 'datetime',
        'status' => 'string',
        'has_models' => 'boolean',
        'has_acf' => 'boolean',
        'models_count' => 'integer',
    ];
    
    public function getRows()
    {
        $modulesPath = base_path('.docs/modules');
        $modules = [];

        if (!File::exists($modulesPath)) {
            File::makeDirectory($modulesPath, 0755, true);
        }

        $files = File::files($modulesPath);
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'json') {
                $content = File::get($file->getPathname());
                $data = json_decode($content, true);
                $isValid = json_last_error() === JSON_ERROR_NONE;
                
                $modules[] = [
                    'id' => $file->getFilenameWithoutExtension(),
                    'name' => $file->getFilenameWithoutExtension(),
                    'path' => $file->getPathname(),
                    'description' => $isValid ? ($data['meta']['description'] ?? 'No description') : 'Invalid JSON file',
                    'full_description' => $isValid ? ($data['meta']['description'] ?? '') : 'Invalid JSON file',
                    'file_size' => $file->getSize(),
                    'last_modified' => \Carbon\Carbon::createFromTimestamp($file->getMTime()),
                    'status' => $isValid ? 'valid' : 'invalid',
                    'has_models' => $isValid && isset($data['models']),
                    'has_acf' => $isValid && isset($data['acf_field_analysis']),
                    'models_count' => $isValid ? count($data['models'] ?? []) : 0,
                ];
            }
        }

        return $modules;
    }
}

class ModulesManager extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string|\UnitEnum|null $navigationGroup = 'System';
    protected static ?int $navigationSort = 100;
    protected static string|null $title = 'Modules Manager';
    protected static string|null $navigationLabel = 'Modules Manager';
    protected static ?string $slug = 'modules-manager';
    
    protected function getViewData(): array
    {
        return [];
    }
    
    public function getView(): string
    {
        return 'filament.pages.modules-manager';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ModuleData::query())
            ->columns([
                TextColumn::make('name')
                    ->label('Module Name')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => Str::title(str_replace(['-', '_'], ' ', $state)))
                    ->icon('heroicon-o-folder')
                    ->iconColor('primary')
                    ->weight('bold'),

                                
                TextColumn::make('models_count')
                    ->label('Models')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->alignCenter(),
                
                TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(fn ($state) => $this->formatFileSize($state))
                    ->sortable()
                    ->alignEnd()
                    ->color('gray'),
                
                TextColumn::make('last_modified')
                    ->label('Last Modified')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->color('gray')
                    ->description(fn ($record) => $record->last_modified ? \Carbon\Carbon::parse($record->last_modified)->diffForHumans() : ''),
                
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'valid' => 'success',
                        'invalid' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'valid' => 'heroicon-o-check',
                        'invalid' => 'heroicon-o-x-mark',
                        default => '',
                    })
            ])
            ->recordActions([
                TableAction::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->url(fn ($record) => ModuleEditor::getUrl(['module' => $record->name]))
                    ->openUrlInNewTab(false),
            ])
            ->bulkActions([])
            ->defaultSort('name')
            ->striped()
            ->paginated(false)
            ->poll('60s');
    }

    protected function formatFileSize($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    protected function getTableEmptyStateHeading(): ?string
    {
        return 'No modules found';
    }
    
    protected function getTableEmptyStateDescription(): ?string
    {
        return 'No module files were found in the .docs/modules directory.';
    }
    
    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-folder-open';
    }

    protected function validateModule($path): void
    {
        try {
            $content = File::get($path);
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Notification::make()
                    ->title('Validation Failed')
                    ->body('Invalid JSON: ' . json_last_error_msg())
                    ->danger()
                    ->send();
                return;
            }

            // Validate required fields
            $errors = [];
            
            if (!isset($data['meta'])) {
                $errors[] = 'Missing "meta" section';
            } else {
                if (!isset($data['meta']['module'])) {
                    $errors[] = 'Missing "meta.module" field';
                }
                if (!isset($data['meta']['description'])) {
                    $errors[] = 'Missing "meta.description" field';
                }
                if (!isset($data['meta']['schema_definition'])) {
                    $errors[] = 'Missing "meta.schema_definition" section';
                }
            }

            if (empty($errors)) {
                Notification::make()
                    ->title('Validation Successful')
                    ->body('Module structure is valid!')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Validation Warnings')
                    ->body(implode(', ', $errors))
                    ->warning()
                    ->send();
            }
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Validation Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function backupModule($path): void
    {
        try {
            $backupDir = base_path('.docs/modules/backups');
            
            if (!File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            $filename = pathinfo($path, PATHINFO_FILENAME);
            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupPath = $backupDir . '/' . $filename . '_' . $timestamp . '.json';
            
            File::copy($path, $backupPath);
            
            Notification::make()
                ->title('Backup Created')
                ->body('Module backed up successfully!')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Backup Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}