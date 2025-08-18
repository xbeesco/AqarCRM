<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeOldDatabaseContent extends Command
{
    protected $signature = 'analyze:old-content';
    protected $description = 'Analyze old WordPress database for post types and taxonomies';

    private $oldDb;
    private $missingFeatures = [];

    public function handle()
    {
        $this->info('🔍 Analyzing Old WordPress Database Content...');
        
        // Connect to old WordPress database
        $databases = ['towntop_crm', 'aqarstage', 'crmfilament'];
        $connected = false;
        
        foreach ($databases as $dbName) {
            try {
                $this->oldDb = new \PDO("mysql:host=127.0.0.1;dbname={$dbName}", 'root', '');
                $this->info("✅ Connected to database: {$dbName}");
                $connected = true;
                break;
            } catch (\Exception $e) {
                $this->warn("Could not connect to {$dbName}");
            }
        }
        
        if (!$connected) {
            $this->error('❌ Could not connect to any WordPress database!');
            return;
        }

        $this->line('');
        $this->analyzePostTypes();
        $this->line('');
        $this->analyzeTaxonomies();
        $this->line('');
        $this->analyzeACFFields();
        $this->line('');
        $this->analyzeCustomTables();
        $this->line('');
        $this->compareWithNewSystem();
        $this->line('');
        $this->generateReport();
    }

    private function analyzePostTypes()
    {
        $this->info('📋 POST TYPES ANALYSIS');
        $this->line('=' . str_repeat('=', 78));

        // Get all post types from posts table
        $query = "SELECT DISTINCT post_type, COUNT(*) as count 
                  FROM wp_posts 
                  WHERE post_status IN ('publish', 'draft', 'private') 
                  GROUP BY post_type 
                  ORDER BY count DESC";
        
        $postTypes = $this->oldDb->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $this->table(['Post Type', 'Count', 'Status in New System'], array_map(function($type) {
            return [
                $type['post_type'],
                $type['count'],
                $this->checkPostTypeStatus($type['post_type'])
            ];
        }, $postTypes));

        // Analyze specific CRM-related post types
        $crmPostTypes = ['properties', 'units', 'contracts', 'tenants', 'owners', 'payments', 'maintenance'];
        
        foreach ($crmPostTypes as $postType) {
            $this->analyzeSpecificPostType($postType);
        }
    }

    private function analyzeSpecificPostType($postType)
    {
        $query = "SELECT COUNT(*) as count FROM wp_posts WHERE post_type = ? AND post_status != 'trash'";
        $stmt = $this->oldDb->prepare($query);
        $stmt->execute([$postType]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $this->warn("  → Found {$result['count']} records for post type: {$postType}");
            
            // Get sample meta keys for this post type
            $metaQuery = "SELECT DISTINCT pm.meta_key, COUNT(*) as count
                         FROM wp_postmeta pm
                         JOIN wp_posts p ON p.ID = pm.post_id
                         WHERE p.post_type = ?
                         GROUP BY pm.meta_key
                         ORDER BY count DESC
                         LIMIT 10";
            $stmt = $this->oldDb->prepare($metaQuery);
            $stmt->execute([$postType]);
            $metaKeys = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            if (!empty($metaKeys)) {
                $this->info("    Meta fields for {$postType}:");
                foreach ($metaKeys as $meta) {
                    $this->line("      • {$meta['meta_key']} (used {$meta['count']} times)");
                }
            }
        }
    }

    private function analyzeTaxonomies()
    {
        $this->info('🏷️ TAXONOMIES ANALYSIS');
        $this->line('=' . str_repeat('=', 78));

        // Get all taxonomies
        $query = "SELECT DISTINCT tt.taxonomy, COUNT(*) as count 
                  FROM wp_term_taxonomy tt
                  GROUP BY tt.taxonomy 
                  ORDER BY count DESC";
        
        $taxonomies = $this->oldDb->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $this->table(['Taxonomy', 'Terms Count', 'Status in New System'], array_map(function($tax) {
            return [
                $tax['taxonomy'],
                $tax['count'],
                $this->checkTaxonomyStatus($tax['taxonomy'])
            ];
        }, $taxonomies));

        // Get taxonomy relationships with post types
        $this->info('  Taxonomy-PostType Relationships:');
        
        foreach ($taxonomies as $tax) {
            $taxName = $tax['taxonomy'];
            
            $query = "SELECT DISTINCT p.post_type, COUNT(*) as count
                     FROM wp_term_relationships tr
                     JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                     JOIN wp_posts p ON tr.object_id = p.ID
                     WHERE tt.taxonomy = ? AND p.post_status != 'trash'
                     GROUP BY p.post_type";
            
            $stmt = $this->oldDb->prepare($query);
            $stmt->execute([$taxName]);
            $relations = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            if (!empty($relations)) {
                $this->line("    {$taxName}:");
                foreach ($relations as $rel) {
                    $this->line("      → {$rel['post_type']} ({$rel['count']} items)");
                }
            }
        }
    }

    private function analyzeACFFields()
    {
        $this->info('🔧 ACF FIELDS ANALYSIS');
        $this->line('=' . str_repeat('=', 78));

        // Get ACF field groups
        $query = "SELECT ID, post_title, post_name, post_content 
                  FROM wp_posts 
                  WHERE post_type = 'acf-field-group' 
                  AND post_status = 'publish'";
        
        $fieldGroups = $this->oldDb->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($fieldGroups)) {
            $this->warn('  No ACF field groups found.');
            return;
        }

        foreach ($fieldGroups as $group) {
            $this->info("  Field Group: {$group['post_title']}");
            
            // Get fields in this group
            $query = "SELECT post_title, post_name, post_content, post_excerpt
                     FROM wp_posts 
                     WHERE post_type = 'acf-field' 
                     AND post_parent = ?
                     AND post_status = 'publish'";
            
            $stmt = $this->oldDb->prepare($query);
            $stmt->execute([$group['ID']]);
            $fields = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($fields as $field) {
                $fieldData = unserialize($field['post_content']);
                $fieldType = $fieldData['type'] ?? 'unknown';
                $this->line("    • {$field['post_title']} ({$field['post_excerpt']}) - Type: {$fieldType}");
                
                // Check if this field exists in new system
                if (!$this->fieldExistsInNewSystem($field['post_excerpt'])) {
                    $this->missingFeatures[] = [
                        'type' => 'ACF Field',
                        'name' => $field['post_title'],
                        'key' => $field['post_excerpt'],
                        'field_type' => $fieldType
                    ];
                }
            }
        }
    }

    private function analyzeCustomTables()
    {
        $this->info('📊 CUSTOM TABLES ANALYSIS');
        $this->line('=' . str_repeat('=', 78));

        // Get all tables in the database
        $query = "SHOW TABLES";
        $tables = $this->oldDb->query($query)->fetchAll(\PDO::FETCH_COLUMN);

        $customTables = [];
        foreach ($tables as $table) {
            // Skip WordPress core tables
            if (!preg_match('/^wp_(posts|postmeta|users|usermeta|terms|term_taxonomy|term_relationships|options|comments|commentmeta|links)$/', $table)) {
                // Check if it's a custom table
                if (strpos($table, 'wp_') === 0 || strpos($table, 'crm_') === 0) {
                    $customTables[] = $table;
                }
            }
        }

        if (empty($customTables)) {
            $this->warn('  No custom tables found.');
            return;
        }

        foreach ($customTables as $table) {
            $countQuery = "SELECT COUNT(*) as count FROM {$table}";
            try {
                $count = $this->oldDb->query($countQuery)->fetch(\PDO::FETCH_ASSOC)['count'];
                $this->info("  Table: {$table} ({$count} records)");
                
                // Get table structure
                $structureQuery = "DESCRIBE {$table}";
                $columns = $this->oldDb->query($structureQuery)->fetchAll(\PDO::FETCH_ASSOC);
                
                $this->line("    Columns:");
                foreach ($columns as $column) {
                    $this->line("      • {$column['Field']} ({$column['Type']})");
                }
                
                // Check if we have equivalent in new system
                if (!$this->tableExistsInNewSystem($table)) {
                    $this->missingFeatures[] = [
                        'type' => 'Custom Table',
                        'name' => $table,
                        'records' => $count
                    ];
                }
            } catch (\Exception $e) {
                $this->error("    Error reading table {$table}");
            }
        }
    }

    private function compareWithNewSystem()
    {
        $this->info('🔄 COMPARISON WITH NEW SYSTEM');
        $this->line('=' . str_repeat('=', 78));

        // Map old content types to new system
        $mappings = [
            'properties' => ['status' => '✅', 'new_table' => 'properties', 'notes' => 'Migrated to properties table'],
            'units' => ['status' => '✅', 'new_table' => 'units', 'notes' => 'Migrated to units table'],
            'tenants' => ['status' => '✅', 'new_table' => 'users (role: tenant)', 'notes' => 'Migrated as users with tenant role'],
            'owners' => ['status' => '✅', 'new_table' => 'users (role: owner)', 'notes' => 'Migrated as users with owner role'],
            'contracts' => ['status' => '✅', 'new_table' => 'unit_contracts & property_contracts', 'notes' => 'Split into two contract types'],
            'payments' => ['status' => '✅', 'new_table' => 'collection_payments & supply_payments', 'notes' => 'Split by payment direction'],
            'maintenance' => ['status' => '✅', 'new_table' => 'property_repairs', 'notes' => 'Renamed to property_repairs'],
            'invoices' => ['status' => '⚠️', 'new_table' => 'N/A', 'notes' => 'Might need separate invoice module'],
            'documents' => ['status' => '❌', 'new_table' => 'N/A', 'notes' => 'Document management not implemented'],
            'messages' => ['status' => '❌', 'new_table' => 'N/A', 'notes' => 'Messaging system not implemented'],
            'tasks' => ['status' => '❌', 'new_table' => 'N/A', 'notes' => 'Task management not implemented'],
        ];

        $this->table(['Old Content Type', 'Status', 'New Location', 'Notes'], array_map(function($key, $value) {
            return [$key, $value['status'], $value['new_table'], $value['notes']];
        }, array_keys($mappings), $mappings));
    }

    private function generateReport()
    {
        $this->info('📝 MISSING FEATURES REPORT');
        $this->line('=' . str_repeat('=', 78));

        if (empty($this->missingFeatures)) {
            $this->info('✅ All major features are covered in the new system!');
            return;
        }

        $this->warn('⚠️ The following features from the old system are not yet implemented:');
        
        foreach ($this->missingFeatures as $feature) {
            $this->line('');
            $this->error("  Missing: {$feature['type']} - {$feature['name']}");
            if (isset($feature['key'])) {
                $this->line("    Key: {$feature['key']}");
            }
            if (isset($feature['field_type'])) {
                $this->line("    Field Type: {$feature['field_type']}");
            }
            if (isset($feature['records'])) {
                $this->line("    Records: {$feature['records']}");
            }
        }

        $this->line('');
        $this->info('📌 RECOMMENDATIONS:');
        $this->line('  1. Review document management requirements');
        $this->line('  2. Consider implementing messaging/notification system');
        $this->line('  3. Evaluate need for task management module');
        $this->line('  4. Check if invoice generation is needed separately');
        $this->line('  5. Review all custom ACF fields for data migration');
    }

    private function checkPostTypeStatus($postType)
    {
        $implemented = [
            'properties' => '✅ Implemented',
            'units' => '✅ Implemented',
            'tenants' => '✅ As Users',
            'owners' => '✅ As Users',
            'contracts' => '✅ Implemented',
            'payments' => '✅ Implemented',
            'maintenance' => '✅ As Repairs',
            'post' => '➖ Not needed',
            'page' => '➖ Not needed',
            'attachment' => '⏳ Media system',
        ];

        return $implemented[$postType] ?? '❌ Not implemented';
    }

    private function checkTaxonomyStatus($taxonomy)
    {
        $implemented = [
            'property_type' => '✅ Implemented',
            'property_status' => '✅ Implemented',
            'unit_type' => '✅ As Unit Features',
            'unit_status' => '✅ Implemented',
            'payment_type' => '✅ Implemented',
            'category' => '➖ Not needed',
            'post_tag' => '➖ Not needed',
        ];

        return $implemented[$taxonomy] ?? '❌ Not implemented';
    }

    private function fieldExistsInNewSystem($fieldKey)
    {
        // Check if field exists in new system
        $implementedFields = [
            'property_code', 'property_name', 'property_address', 'property_area',
            'unit_number', 'unit_floor', 'unit_area', 'unit_rent',
            'tenant_name', 'tenant_phone', 'tenant_email', 'tenant_national_id',
            'owner_name', 'owner_phone', 'owner_email', 'owner_bank_account',
            'contract_number', 'contract_start', 'contract_end', 'contract_amount',
            'payment_amount', 'payment_date', 'payment_method', 'payment_status',
        ];

        foreach ($implementedFields as $field) {
            if (strpos($fieldKey, $field) !== false) {
                return true;
            }
        }

        return false;
    }

    private function tableExistsInNewSystem($tableName)
    {
        $implementedTables = [
            'properties', 'units', 'users', 'property_contracts', 'unit_contracts',
            'collection_payments', 'supply_payments', 'property_repairs', 'transactions',
            'property_types', 'property_statuses', 'unit_statuses', 'payment_methods',
        ];

        foreach ($implementedTables as $table) {
            if (strpos($tableName, $table) !== false) {
                return true;
            }
        }

        return false;
    }
}