<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;
use ReflectionException;

class GenerateFilesTree extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:generate-files-tree 
                           {--force : Force overwrite existing file}
                           {--exclude=* : Additional directories to exclude}
                           {--include-content : Include actual file content for non-PHP files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate files-tree.json documentation for the project structure';

    /**
     * Default directories to exclude from scanning
     *
     * @var array
     */
    protected $excludedDirectories = [
        'vendor',
        'node_modules',
        'storage',
        'bootstrap/cache',
        'public/css',
        'public/js',
        'public/fonts',
        '.git',
        '.docs',
        'dist',
        'build'
    ];

    /**
     * File extensions to analyze as PHP code
     *
     * @var array
     */
    protected $phpExtensions = ['php'];

    /**
     * File extensions to include for content analysis
     *
     * @var array
     */
    protected $includedExtensions = [
        'php', 'js', 'vue', 'blade.php', 'json', 'md', 'yaml', 'yml'
    ];

    /**
     * The directory structure data
     *
     * @var array
     */
    protected $filesTree = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $outputPath = base_path('.docs/files-tree.json');
        
        // Check if output file exists and force flag
        if (File::exists($outputPath) && !$this->option('force')) {
            if (!$this->confirm('File already exists. Do you want to overwrite it?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // Ensure output directory exists
        $outputDir = dirname($outputPath);
        if (!File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
            $this->info("Created directory: {$outputDir}");
        }

        // Add any additional exclusions
        $this->excludedDirectories = array_merge(
            $this->excludedDirectories,
            $this->option('exclude')
        );

        $this->info('Starting files tree generation...');
        $this->info('Target directories: app/, database/migrations/, database/seeders/, tests/');

        $startTime = microtime(true);

        // Define target directories to scan
        $targetDirectories = [
            'app',
            'database/migrations',
            'database/seeders',
            'tests'
        ];

        $totalFiles = 0;

        // Scan each target directory
        foreach ($targetDirectories as $directory) {
            $fullPath = base_path($directory);
            
            if (!File::exists($fullPath)) {
                $this->warn("Directory not found: {$directory}");
                continue;
            }

            $this->info("Scanning: {$directory}/");
            $dirFiles = $this->scanDirectory($fullPath, $directory);
            $totalFiles += $dirFiles;
        }

        // Write the JSON file
        $jsonContent = json_encode($this->filesTree, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        File::put($outputPath, $jsonContent);

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $this->info("✅ Files tree generated successfully!");
        $this->info("📁 Total files processed: {$totalFiles}");
        $this->info("⏱️  Time taken: {$duration} seconds");
        $this->info("📄 Output: {$outputPath}");

        return 0;
    }

    /**
     * Scan a directory recursively
     *
     * @param string $directoryPath
     * @param string $relativePath
     * @return int
     */
    protected function scanDirectory(string $directoryPath, string $relativePath): int
    {
        $fileCount = 0;

        if (!File::isDirectory($directoryPath)) {
            return $fileCount;
        }

        $items = File::glob($directoryPath . '/*');

        foreach ($items as $item) {
            $itemName = basename($item);
            $itemRelativePath = $relativePath . '/' . $itemName;

            // Skip excluded directories
            if (File::isDirectory($item) && $this->shouldExcludeDirectory($itemName, $itemRelativePath)) {
                continue;
            }

            if (File::isDirectory($item)) {
                // Recursively scan subdirectory
                $fileCount += $this->scanDirectory($item, $itemRelativePath);
            } else {
                // Process file
                if ($this->shouldIncludeFile($item)) {
                    $this->processFile($item, $relativePath, $itemName);
                    $fileCount++;
                    
                    if ($fileCount % 10 === 0) {
                        $this->output->write('.');
                    }
                }
            }
        }

        return $fileCount;
    }

    /**
     * Check if directory should be excluded
     *
     * @param string $dirName
     * @param string $relativePath
     * @return bool
     */
    protected function shouldExcludeDirectory(string $dirName, string $relativePath): bool
    {
        // Check against excluded directory patterns
        foreach ($this->excludedDirectories as $excluded) {
            if (str_contains($relativePath, $excluded) || $dirName === $excluded) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if file should be included
     *
     * @param string $filePath
     * @return bool
     */
    protected function shouldIncludeFile(string $filePath): bool
    {
        $fileName = basename($filePath);
        
        // Skip hidden files and common non-source files
        if (str_starts_with($fileName, '.')) {
            return false;
        }

        // Get file extension
        $extension = $this->getFileExtension($fileName);
        
        return in_array($extension, $this->includedExtensions);
    }

    /**
     * Get file extension (handles .blade.php)
     *
     * @param string $fileName
     * @return string
     */
    protected function getFileExtension(string $fileName): string
    {
        if (str_ends_with($fileName, '.blade.php')) {
            return 'blade.php';
        }

        return pathinfo($fileName, PATHINFO_EXTENSION);
    }

    /**
     * Process a single file
     *
     * @param string $filePath
     * @param string $folderPath
     * @param string $fileName
     */
    protected function processFile(string $filePath, string $folderPath, string $fileName): void
    {
        $extension = $this->getFileExtension($fileName);
        $relativeFilePath = str_replace($folderPath . '/', '', $fileName);

        // Initialize folder in tree if not exists
        if (!isset($this->filesTree[$folderPath])) {
            $this->filesTree[$folderPath] = [];
        }

        // Analyze file content
        $fileData = [
            'summarized' => false,
            'content' => [
                'file_use' => $this->determineFileUse($filePath, $extension),
                'functions' => $this->extractFunctions($filePath, $extension)
            ]
        ];

        $this->filesTree[$folderPath][$relativeFilePath] = $fileData;
    }

    /**
     * Determine the use/purpose of a file
     *
     * @param string $filePath
     * @param string $extension
     * @return string
     */
    protected function determineFileUse(string $filePath, string $extension): string
    {
        $fileName = basename($filePath);
        $relativePath = str_replace(base_path() . '/', '', $filePath);

        // Specific file patterns
        if (str_contains($relativePath, 'migration')) {
            return 'Database migration file for schema changes';
        }

        if (str_contains($relativePath, 'seeder')) {
            return 'Database seeder for populating initial data';
        }

        if (str_contains($relativePath, 'factory')) {
            return 'Model factory for generating test data';
        }

        if (str_contains($relativePath, 'test') || str_contains($relativePath, 'Test')) {
            return 'Test file for automated testing';
        }

        if (str_contains($relativePath, 'Resource') && str_contains($relativePath, 'Filament')) {
            return 'Filament admin panel resource definition';
        }

        if (str_contains($relativePath, 'Pages') && str_contains($relativePath, 'Filament')) {
            return 'Filament resource page component';
        }

        // Extension-based determination
        switch ($extension) {
            case 'php':
                return $this->analyzePhpFileUse($filePath);
            case 'js':
                return 'JavaScript file for frontend functionality';
            case 'vue':
                return 'Vue.js component file';
            case 'blade.php':
                return 'Laravel Blade template file';
            case 'json':
                return 'JSON configuration or data file';
            case 'md':
                return 'Markdown documentation file';
            case 'yaml':
            case 'yml':
                return 'YAML configuration file';
            default:
                return 'Source code file';
        }
    }

    /**
     * Analyze PHP file to determine its use
     *
     * @param string $filePath
     * @return string
     */
    protected function analyzePhpFileUse(string $filePath): string
    {
        try {
            $content = File::get($filePath);
            
            // Check for common patterns
            if (str_contains($content, 'extends Model')) {
                return 'Eloquent model class for database entity';
            }

            if (str_contains($content, 'extends Controller')) {
                return 'HTTP controller for handling requests';
            }

            if (str_contains($content, 'extends Resource')) {
                return 'Filament resource for admin panel management';
            }

            if (str_contains($content, 'extends Command')) {
                return 'Artisan console command';
            }

            if (str_contains($content, 'extends Middleware')) {
                return 'HTTP middleware for request processing';
            }

            if (str_contains($content, 'extends ServiceProvider')) {
                return 'Laravel service provider for dependency injection';
            }

            if (str_contains($content, 'extends Migration')) {
                return 'Database migration for schema modifications';
            }

            if (str_contains($content, 'extends Seeder')) {
                return 'Database seeder for data population';
            }

            if (str_contains($content, 'class') && str_contains($content, 'extends')) {
                return 'PHP class extending framework functionality';
            }

            if (str_contains($content, 'interface')) {
                return 'PHP interface definition';
            }

            if (str_contains($content, 'trait')) {
                return 'PHP trait for code reusability';
            }

            return 'PHP source file';

        } catch (\Exception $e) {
            return 'PHP file (analysis failed)';
        }
    }

    /**
     * Extract functions/methods from a file
     *
     * @param string $filePath
     * @param string $extension
     * @return array
     */
    protected function extractFunctions(string $filePath, string $extension): array
    {
        if ($extension !== 'php') {
            return $this->extractNonPhpFunctions($filePath, $extension);
        }

        return $this->extractPhpFunctions($filePath);
    }

    /**
     * Extract functions from PHP files using reflection
     *
     * @param string $filePath
     * @return array
     */
    protected function extractPhpFunctions(string $filePath): array
    {
        $functions = [];

        try {
            // Get content and try to extract namespace and class
            $content = File::get($filePath);
            
            // Extract namespace
            if (preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch)) {
                $namespace = trim($namespaceMatch[1]);
                
                // Extract class name
                if (preg_match('/class\s+(\w+)/', $content, $classMatch)) {
                    $className = $namespace . '\\' . $classMatch[1];
                    
                    try {
                        // Use reflection to get methods
                        $reflection = new ReflectionClass($className);
                        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
                        
                        foreach ($methods as $method) {
                            // Only include methods defined in this class (not inherited)
                            if ($method->getDeclaringClass()->getName() === $className) {
                                $functions[] = $method->getName() . '()';
                            }
                        }
                    } catch (ReflectionException $e) {
                        // Fallback to regex parsing if reflection fails
                        $functions = $this->extractPhpFunctionsRegex($content);
                    }
                } else {
                    // No class found, look for standalone functions
                    $functions = $this->extractPhpFunctionsRegex($content);
                }
            } else {
                // No namespace found, fallback to regex
                $functions = $this->extractPhpFunctionsRegex($content);
            }

        } catch (\Exception $e) {
            // Fallback to regex if file reading fails
            try {
                $content = File::get($filePath);
                $functions = $this->extractPhpFunctionsRegex($content);
            } catch (\Exception $e2) {
                $functions = ['Unable to parse functions'];
            }
        }

        return $functions;
    }

    /**
     * Extract PHP functions using regex (fallback method)
     *
     * @param string $content
     * @return array
     */
    protected function extractPhpFunctionsRegex(string $content): array
    {
        $functions = [];

        // Match public function definitions
        preg_match_all('/public\s+function\s+(\w+)\s*\(/', $content, $publicMatches);
        foreach ($publicMatches[1] as $match) {
            $functions[] = $match . '()';
        }

        // Match protected function definitions
        preg_match_all('/protected\s+function\s+(\w+)\s*\(/', $content, $protectedMatches);
        foreach ($protectedMatches[1] as $match) {
            $functions[] = $match . '() [protected]';
        }

        // Match static function definitions
        preg_match_all('/static\s+function\s+(\w+)\s*\(/', $content, $staticMatches);
        foreach ($staticMatches[1] as $match) {
            $functions[] = $match . '() [static]';
        }

        return array_unique($functions);
    }

    /**
     * Extract functions from non-PHP files
     *
     * @param string $filePath
     * @param string $extension
     * @return array
     */
    protected function extractNonPhpFunctions(string $filePath, string $extension): array
    {
        $functions = [];

        try {
            $content = File::get($filePath);

            switch ($extension) {
                case 'js':
                    // Extract JavaScript functions
                    preg_match_all('/function\s+(\w+)\s*\(/', $content, $matches);
                    foreach ($matches[1] as $match) {
                        $functions[] = $match . '()';
                    }
                    
                    // Extract arrow functions and method definitions
                    preg_match_all('/(\w+)\s*:\s*function\s*\(/', $content, $methodMatches);
                    foreach ($methodMatches[1] as $match) {
                        $functions[] = $match . '()';
                    }
                    break;

                case 'vue':
                    // Extract Vue methods
                    preg_match_all('/(\w+)\s*\([^)]*\)\s*{/', $content, $matches);
                    foreach ($matches[1] as $match) {
                        if (!in_array($match, ['data', 'mounted', 'created', 'setup'])) {
                            $functions[] = $match . '()';
                        }
                    }
                    break;

                case 'blade.php':
                    // Extract Blade directives and sections
                    preg_match_all('/@(\w+)/', $content, $matches);
                    $directives = array_unique($matches[1]);
                    foreach ($directives as $directive) {
                        if (!in_array($directive, ['extends', 'section', 'endsection', 'yield'])) {
                            $functions[] = '@' . $directive;
                        }
                    }
                    break;

                default:
                    return [];
            }

        } catch (\Exception $e) {
            $functions = ['Unable to parse content'];
        }

        return array_unique($functions);
    }
}