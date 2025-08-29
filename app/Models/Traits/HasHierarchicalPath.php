<?php

namespace App\Models\Traits;

trait HasHierarchicalPath
{
    /**
     * Get the level label in Arabic
     */
    public function getLevelLabelAttribute(): string
    {
        return match($this->level) {
            1 => 'منطقة',
            2 => 'مدينة',
            3 => 'مركز',
            4 => 'حي',
            default => 'غير محدد'
        };
    }
    
    /**
     * Get the full path of the location
     */
    public function getFullPathAttribute(): string
    {
        $path = collect();
        $current = $this;
        
        while ($current) {
            $path->prepend($current->name);
            $current = $current->parent;
        }
        
        return $path->join(' > ');
    }
    
    /**
     * Get breadcrumbs for the location
     */
    public function getBreadcrumbsAttribute(): array
    {
        $breadcrumbs = collect();
        $current = $this;
        
        while ($current) {
            $breadcrumbs->prepend([
                'id' => $current->id,
                'name' => $current->name,
                'level' => $current->level,
                'level_label' => $current->level_label
            ]);
            $current = $current->parent;
        }
        
        return $breadcrumbs->toArray();
    }
    
    /**
     * Get level options for select
     */
    public static function getLevelOptions(): array
    {
        return [
            1 => 'منطقة',
            2 => 'مدينة', 
            3 => 'مركز',
            4 => 'حي'
        ];
    }
    
    /**
     * Get parent options based on level
     */
    public static function getParentOptions(int $level): array
    {
        if ($level <= 1) {
            return [];
        }
        
        $locations = self::where('level', $level - 1)
            ->with('parent')
            ->orderBy('path')
            ->get();
            
        $options = [];
        foreach ($locations as $location) {
            // Build full path for display
            $fullPath = [];
            $current = $location;
            while ($current) {
                array_unshift($fullPath, $current->name);
                $current = $current->parent;
            }
            $options[$location->id] = implode(' › ', $fullPath);
        }
        
        return $options;
    }
    
    /**
     * Get all locations in hierarchical format for select
     */
    public static function getHierarchicalOptions(): array
    {
        $locations = self::where('is_active', true)
            ->orderBy('path')
            ->get();
        $options = [];
        
        foreach ($locations as $location) {
            // Build full path for display - same as table column
            $fullPath = [];
            $current = $location;
            while ($current) {
                array_unshift($fullPath, $current->name);
                $current = $current->parent;
            }
            
            // Use non-breaking spaces for indentation - same as table display
            // Using more nbsp for better visual hierarchy (12 nbsp per level)
            $indent = '';
            if ($location->level > 1) {
                $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $location->level - 1);
            }
            
            // Get level label
            $levelLabel = match($location->level) {
                1 => '(منطقة)',
                2 => '(مدينة)',
                3 => '(مركز)',
                4 => '(حي)',
                default => ''
            };
            
            // Format: indentation + (type) + full path
            $options[$location->id] = $indent . $levelLabel . ' ' . implode(' › ', $fullPath);
        }
        
        return $options;
    }
    
    /**
     * Get formatted table display for location with badges and hierarchy
     */
    public function getFormattedTableDisplay(): string
    {
        // Create hierarchical indentation with enhanced visual tree structure
        $treeStructure = '';
        $badges = [
            1 => '<span class="fi-color fi-color-success fi-text-color-700 dark:fi-text-color-300 fi-badge fi-size-sm"> منطقة </span>&nbsp;',
            2 => '<span class="fi-color fi-color-warning fi-text-color-700 dark:fi-text-color-300 fi-badge fi-size-sm"> مدينة </span>&nbsp;',
            3 => '<span class="fi-color fi-color-info fi-text-color-700 dark:fi-text-color-300 fi-badge fi-size-sm"> مركز </span>&nbsp;',
            4 => '<span class="fi-color fi-color-gray fi-text-color-700 dark:fi-text-color-300 fi-badge fi-size-sm"> حي </span>&nbsp;',
        ];
        
        // Build tree indentation based on level with better styling
        if ($this->level > 1) {
            $treeStructure = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $this->level - 1);
        }
        $treeStructure .= $badges[$this->level] . '&nbsp;';

        // Display name with better styling
        $displayName = '<span class="font-medium text-gray-900">' . $this->name . '</span>';
        
        // Add breadcrumb path for deeper levels
        $breadcrumb = '';
        if ($this->level > 1 && $this->parent) {
            $path = collect();
            $current = $this->parent;
            while ($current) {
                $path->prepend($current->name);
                $current = $current->parent;
            }
            if ($path->isNotEmpty()) {
                $breadcrumb = '<div class="text-xs text-gray-400 mt-1">' . 
                             $path->join(' › ') . 
                             '</div>';
            }
        }
        
        return '<div class="py-1">' . $treeStructure . $displayName . '</div>';
    }
    
    /**
     * Get hierarchical options with HTML formatting for select fields
     */
    public static function getHierarchicalOptionsWithHtml(): array
    {
        $locations = self::where('is_active', true)
            ->orderBy('path')
            ->get();
        $options = [];
        
        $levelStyles = [
            1 => [
                'bg' => '#dcfce7', // green-100
                'color' => '#15803d', // green-700
                'label' => 'منطقة'
            ],
            2 => [
                'bg' => '#fef3c7', // yellow-100
                'color' => '#a16207', // yellow-700
                'label' => 'مدينة'
            ],
            3 => [
                'bg' => '#dbeafe', // blue-100
                'color' => '#1d4ed8', // blue-700
                'label' => 'مركز'
            ],
            4 => [
                'bg' => '#f3f4f6', // gray-100
                'color' => '#374151', // gray-700
                'label' => 'حي'
            ],
        ];
        
        foreach ($locations as $location) {
            // Build full path for display
            $fullPath = [];
            $current = $location;
            while ($current) {
                array_unshift($fullPath, $current->name);
                $current = $current->parent;
            }
            
            $levelConfig = $levelStyles[$location->level] ?? [
                'bg' => '#f3f4f6',
                'color' => '#374151',
                'label' => ''
            ];
            
            // Build HTML with inline styles
            $html = '';
            
            // Add padding for indentation based on level
            if ($location->level > 1) {
                $paddingLeft = ($location->level - 1) * 20;
                $html .= '<span style="padding-left: ' . $paddingLeft . 'px;">';
            }
            
            // Main wrapper
            $html .= '<span style="display: inline-flex; align-items: center; gap: 8px;">';
            
            // Badge with inline styles
            $html .= '<span style="';
            $html .= 'background-color: ' . $levelConfig['bg'] . '; ';
            $html .= 'color: ' . $levelConfig['color'] . '; ';
            $html .= 'padding: 2px 8px; ';
            $html .= 'border-radius: 6px; ';
            $html .= 'font-size: 11px; ';
            $html .= 'font-weight: 500; ';
            $html .= 'white-space: nowrap;';
            $html .= '">' . $levelConfig['label'] . '</span>';
            
            // Location path
            $html .= '<span style="color: #111827; font-weight: 500;">';
            $html .= htmlspecialchars(implode(' › ', $fullPath));
            $html .= '</span>';
            
            $html .= '</span>';
            
            if ($location->level > 1) {
                $html .= '</span>';
            }
            
            $options[$location->id] = $html;
        }
        
        return $options;
    }
}