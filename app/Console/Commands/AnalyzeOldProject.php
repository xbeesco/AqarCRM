<?php

namespace App\Console\Commands;

use SplFileInfo;
use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AnalyzeOldProject extends Command
{
    protected $signature = 'analyze:old-project 
                            {--path=D:\Server\crm : Path to old WordPress project}
                            {--output=.docs/old-project-tree.json : Output file path}
                            {--force : Overwrite existing file}';

    protected $description = 'Analyze old WordPress CRM project and generate comprehensive files tree';

    private $tree = [];
    private $processedFiles = 0;
    private $totalSize = 0;

    public function handle()
    {
        $path = $this->option('path');
        $output = $this->option('output');
        
        if (!is_dir($path)) {
            $this->error("Directory does not exist: $path");
            return 1;
        }

        if (file_exists($output) && !$this->option('force')) {
            if (!$this->confirm("File $output exists. Overwrite?")) {
                return 0;
            }
        }

        $this->info("🔍 Analyzing old WordPress project at: $path");
        $this->info("Focus areas: wp-content/themes/alhiaa-system, wp-content/plugins");
        
        // Analyze main areas
        $this->analyzeWordPressStructure($path);
        
        // Save to file
        $json = json_encode($this->tree, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($output, $json);
        
        $this->info("✅ Analysis complete!");
        $this->info("📁 Files processed: {$this->processedFiles}");
        $this->info("📄 Output: $output");
        
        return 0;
    }

    private function analyzeWordPressStructure($basePath)
    {
        // Focus on theme directory
        $themePath = $basePath . '/wp-content/themes/alhiaa-system';
        if (is_dir($themePath)) {
            $this->info("Analyzing theme: alhiaa-system");
            $this->analyzeDirectory($themePath, 'wp-content/themes/alhiaa-system');
        }

        // Analyze plugins for ACF configurations
        $pluginsPath = $basePath . '/wp-content/plugins';
        if (is_dir($pluginsPath)) {
            $this->info("Analyzing plugins directory");
            $this->analyzeAcfConfigurations($pluginsPath);
        }

        // Analyze mu-plugins if exists
        $muPluginsPath = $basePath . '/wp-content/mu-plugins';
        if (is_dir($muPluginsPath)) {
            $this->info("Analyzing mu-plugins");
            $this->analyzeDirectory($muPluginsPath, 'wp-content/mu-plugins');
        }
    }

    private function analyzeDirectory($path, $relativePath)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $filePath = str_replace('\\', '/', $file->getPathname());
                $relativeFilePath = str_replace($path . '/', '', $filePath);
                
                // Skip vendor and node_modules
                if (strpos($relativeFilePath, 'vendor/') !== false || 
                    strpos($relativeFilePath, 'node_modules/') !== false) {
                    continue;
                }

                $this->processedFiles++;
                $this->analyzePhpFile($file, $relativePath, $relativeFilePath);
                
                // Show progress
                if ($this->processedFiles % 10 === 0) {
                    $this->output->write('.');
                }
            }
        }
    }

    private function analyzePhpFile($file, $folderPath, $relativeFilePath)
    {
        $content = file_get_contents($file->getPathname());
        $analysis = $this->extractFileInfo($content, $file->getFilename());
        
        if (!isset($this->tree[$folderPath])) {
            $this->tree[$folderPath] = [];
        }

        $this->tree[$folderPath][$relativeFilePath] = [
            'summarized' => false,
            'content' => $analysis
        ];
    }

    private function extractFileInfo($content, $filename)
    {
        $info = [
            'file_use' => $this->determineFileUse($content, $filename),
            'functions' => [],
            'hooks' => [],
            'shortcodes' => [],
            'post_types' => [],
            'taxonomies' => [],
            'acf_fields' => [],
            'ajax_actions' => [],
            'rest_endpoints' => []
        ];

        // Extract functions
        preg_match_all('/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $content, $matches);
        if (!empty($matches[1])) {
            $info['functions'] = array_unique($matches[1]);
        }

        // Extract WordPress hooks
        preg_match_all('/add_(action|filter)\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches);
        if (!empty($matches[2])) {
            $info['hooks'] = array_unique($matches[2]);
        }

        // Extract shortcodes
        preg_match_all('/add_shortcode\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches);
        if (!empty($matches[1])) {
            $info['shortcodes'] = array_unique($matches[1]);
        }

        // Extract custom post types
        preg_match_all('/register_post_type\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches);
        if (!empty($matches[1])) {
            $info['post_types'] = array_unique($matches[1]);
        }

        // Extract taxonomies
        preg_match_all('/register_taxonomy\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches);
        if (!empty($matches[1])) {
            $info['taxonomies'] = array_unique($matches[1]);
        }

        // Extract ACF field groups
        if (strpos($content, 'acf_add_local_field_group') !== false) {
            preg_match_all('/[\'"]key[\'"]\s*=>\s*[\'"]([^\'"]+)[\'"]/', $content, $matches);
            if (!empty($matches[1])) {
                $info['acf_fields'] = array_unique($matches[1]);
            }
        }

        // Extract AJAX actions
        preg_match_all('/wp_ajax_([a-zA-Z0-9_]+)/', $content, $matches);
        if (!empty($matches[1])) {
            $info['ajax_actions'] = array_unique($matches[1]);
        }

        // Extract REST API endpoints
        preg_match_all('/register_rest_route\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches);
        if (!empty($matches[1])) {
            $info['rest_endpoints'] = array_unique($matches[1]);
        }

        // Clean up empty arrays
        foreach ($info as $key => $value) {
            if (is_array($value) && empty($value)) {
                unset($info[$key]);
            }
        }

        return $info;
    }

    private function determineFileUse($content, $filename)
    {
        // Template files
        if (strpos($content, 'Template Name:') !== false) {
            preg_match('/Template Name:\s*(.+)/', $content, $matches);
            return "WordPress template: " . ($matches[1] ?? 'Custom');
        }

        // Check for specific WordPress file types
        if ($filename === 'functions.php') {
            return "Theme functions file - core theme functionality";
        }

        if ($filename === 'single.php' || strpos($filename, 'single-') === 0) {
            return "Single post/custom post type template";
        }

        if ($filename === 'archive.php' || strpos($filename, 'archive-') === 0) {
            return "Archive template for posts listing";
        }

        if ($filename === 'page.php' || strpos($filename, 'page-') === 0) {
            return "Page template";
        }

        if (strpos($filename, 'template-') === 0) {
            return "Custom page template";
        }

        // Check for ACF Pro functionality
        if (strpos($content, 'get_field') !== false || strpos($content, 'the_field') !== false) {
            return "ACF-powered functionality file";
        }

        // Check for AJAX handlers
        if (strpos($content, 'wp_ajax_') !== false) {
            return "AJAX handler file";
        }

        // Check for custom post type registration
        if (strpos($content, 'register_post_type') !== false) {
            return "Custom post type registration";
        }

        // Check for class definitions
        if (preg_match('/class\s+[A-Z][a-zA-Z0-9_]*/', $content)) {
            return "PHP class file";
        }

        return "PHP functionality file";
    }

    private function analyzeAcfConfigurations($pluginsPath)
    {
        // Look for ACF JSON files
        $acfJsonPath = dirname($pluginsPath) . '/themes/alhiaa-system/acf-json';
        if (is_dir($acfJsonPath)) {
            $this->info("Found ACF JSON configurations");
            $this->analyzeAcfJson($acfJsonPath);
        }

        // Look for ACF PHP exports
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($pluginsPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && 
                ($file->getExtension() === 'json' || $file->getExtension() === 'php') &&
                strpos($file->getPathname(), 'acf') !== false) {
                
                $this->processedFiles++;
                $content = file_get_contents($file->getPathname());
                
                if ($file->getExtension() === 'json') {
                    $this->analyzeAcfJsonFile($file, $content);
                }
            }
        }
    }

    private function analyzeAcfJson($path)
    {
        $files = glob($path . '/*.json');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $this->analyzeAcfJsonFile(new SplFileInfo($file), $content);
        }
    }

    private function analyzeAcfJsonFile($file, $content)
    {
        $data = json_decode($content, true);
        if ($data && isset($data['key'])) {
            $relativePath = 'acf-configurations';
            if (!isset($this->tree[$relativePath])) {
                $this->tree[$relativePath] = [];
            }

            $this->tree[$relativePath][$file->getFilename()] = [
                'summarized' => false,
                'content' => [
                    'file_use' => 'ACF field group configuration',
                    'title' => $data['title'] ?? 'Unknown',
                    'key' => $data['key'],
                    'fields' => $this->extractAcfFieldNames($data['fields'] ?? []),
                    'location' => $this->extractAcfLocation($data['location'] ?? [])
                ]
            ];
        }
    }

    private function extractAcfFieldNames($fields)
    {
        $fieldNames = [];
        foreach ($fields as $field) {
            if (isset($field['name'])) {
                $fieldNames[] = [
                    'name' => $field['name'],
                    'type' => $field['type'] ?? 'unknown',
                    'label' => $field['label'] ?? ''
                ];
            }
            // Handle nested fields (repeater, group, etc.)
            if (isset($field['sub_fields'])) {
                $subFields = $this->extractAcfFieldNames($field['sub_fields']);
                foreach ($subFields as $subField) {
                    $fieldNames[] = $field['name'] . '.' . $subField['name'];
                }
            }
        }
        return $fieldNames;
    }

    private function extractAcfLocation($location)
    {
        $rules = [];
        foreach ($location as $group) {
            foreach ($group as $rule) {
                if (isset($rule['param']) && isset($rule['value'])) {
                    $rules[] = $rule['param'] . ' == ' . $rule['value'];
                }
            }
        }
        return $rules;
    }
}