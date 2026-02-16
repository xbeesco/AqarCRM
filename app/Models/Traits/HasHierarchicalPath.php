<?php

namespace App\Models\Traits;

trait HasHierarchicalPath
{
    /**
     * Get level label (Arabic).
     */
    public function getLevelLabelAttribute(): string
    {
        return match ($this->level) {
            1 => 'منطقة',
            2 => 'مدينة',
            3 => 'مركز',
            4 => 'حي',
            default => 'غير محدد'
        };
    }

    /**
     * Get full path of location.
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
     * Get breadcrumbs for location.
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
                'level_label' => $current->level_label,
            ]);
            $current = $current->parent;
        }

        return $breadcrumbs->toArray();
    }

    /**
     * Get level options for select fields.
     */
    public static function getLevelOptions(): array
    {
        return [
            1 => 'منطقة',
            2 => 'مدينة',
            3 => 'مركز',
            4 => 'حي',
        ];
    }

    /**
     * Get parent options based on level.
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
            $fullPath = self::buildFullPath($location);
            $options[$location->id] = implode(' › ', $fullPath);
        }

        return $options;
    }

    /**
     * Get hierarchical options for select fields.
     */
    public static function getHierarchicalOptions(): array
    {
        $locations = self::orderBy('path')
            ->get();
        $options = [];

        foreach ($locations as $location) {
            $fullPath = self::buildFullPath($location);

            $indent = '';
            if ($location->level > 1) {
                $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $location->level - 1);
            }

            $levelLabel = match ($location->level) {
                1 => '(منطقة)',
                2 => '(مدينة)',
                3 => '(مركز)',
                4 => '(حي)',
                default => ''
            };

            $options[$location->id] = $indent . $levelLabel . ' ' . implode(' › ', $fullPath);
        }

        return $options;
    }

    /**
     * Get formatted table display with badges.
     */
    public function getFormattedTableDisplay(): string
    {
        $badges = [
            1 => '<span class="fi-color fi-color-success fi-text-color-700 dark:fi-text-color-300 fi-badge fi-size-sm"> منطقة </span>&nbsp;',
            2 => '<span class="fi-color fi-color-warning fi-text-color-700 dark:fi-text-color-300 fi-badge fi-size-sm"> مدينة </span>&nbsp;',
            3 => '<span class="fi-color fi-color-info fi-text-color-700 dark:fi-text-color-300 fi-badge fi-size-sm"> مركز </span>&nbsp;',
            4 => '<span class="fi-color fi-color-gray fi-text-color-700 dark:fi-text-color-300 fi-badge fi-size-sm"> حي </span>&nbsp;',
        ];

        $treeStructure = '';
        if ($this->level > 1) {
            $treeStructure = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $this->level - 1);
        }
        $treeStructure .= ($badges[$this->level] ?? '') . '&nbsp;';

        $displayName = '<span class="font-medium text-gray-900">' . e($this->name) . '</span>';

        return '<div class="py-1">' . $treeStructure . $displayName . '</div>';
    }

    /**
     * Build full path array for a location.
     */
    private static function buildFullPath($location): array
    {
        $fullPath = [];
        $current = $location;
        while ($current) {
            array_unshift($fullPath, $current->name);
            $current = $current->parent;
        }

        return $fullPath;
    }

    /**
     * Get hierarchical options with HTML formatting.
     */
    public static function getHierarchicalOptionsWithHtml(): array
    {
        $locations = self::orderBy('path')
            ->get();

        $levelStyles = [
            1 => ['bg' => '#dcfce7', 'color' => '#15803d', 'label' => 'منطقة'],
            2 => ['bg' => '#fef3c7', 'color' => '#a16207', 'label' => 'مدينة'],
            3 => ['bg' => '#dbeafe', 'color' => '#1d4ed8', 'label' => 'مركز'],
            4 => ['bg' => '#f3f4f6', 'color' => '#374151', 'label' => 'حي'],
        ];

        $defaultStyle = ['bg' => '#f3f4f6', 'color' => '#374151', 'label' => ''];
        $options = [];

        foreach ($locations as $location) {
            $fullPath = self::buildFullPath($location);
            $levelConfig = $levelStyles[$location->level] ?? $defaultStyle;

            $html = '';
            if ($location->level > 1) {
                $paddingLeft = ($location->level - 1) * 20;
                $html .= '<span style="padding-left: ' . $paddingLeft . 'px;">';
            }

            $html .= '<span style="display: inline-flex; align-items: center; gap: 8px;">';
            $html .= '<span style="background-color: ' . $levelConfig['bg'] . '; ';
            $html .= 'color: ' . $levelConfig['color'] . '; ';
            $html .= 'padding: 2px 8px; border-radius: 6px; font-size: 11px; ';
            $html .= 'font-weight: 500; white-space: nowrap;">' . $levelConfig['label'] . '</span>';
            $html .= '<span style="color: #111827; font-weight: 500;">' . e(implode(' › ', $fullPath)) . '</span>';
            $html .= '</span>';

            if ($location->level > 1) {
                $html .= '</span>';
            }

            $options[$location->id] = $html;
        }

        return $options;
    }
}
