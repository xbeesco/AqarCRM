<?php

namespace App\Models;

use App\Enums\CustomFieldTarget;
use App\Enums\CustomFieldType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomField extends Model
{
    /** @use HasFactory<\Database\Factories\CustomFieldFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'target',
        'type',
        'section',
        'is_searchable',
        'is_required',
        'sort_order',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'target' => CustomFieldTarget::class,
            'type' => CustomFieldType::class,
            'is_searchable' => 'boolean',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
            'settings' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CustomField $field) {
            if ($field->type !== CustomFieldType::Options) {
                return;
            }

            $settings = $field->settings ?? [];
            $choices = $settings['choices'] ?? [];

            $settings['choices'] = collect($choices)->map(function ($choice) {
                // Simple string from simple repeater
                if (is_string($choice)) {
                    return [
                        'value' => 'opt_'.uniqid(),
                        'label' => $choice,
                    ];
                }

                // Already has value/label
                if (is_array($choice) && isset($choice['label'])) {
                    return [
                        'value' => $choice['value'] ?? 'opt_'.uniqid(),
                        'label' => $choice['label'],
                    ];
                }

                return $choice;
            })->values()->all();

            $field->settings = $settings;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettingsValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * @return array<array{value: string, label: string}>
     */
    public function getOptionsChoices(): array
    {
        return $this->getSettingsValue('choices', []);
    }

    public function isOptionValueInUse(string $value): bool
    {
        return $this->getRecordsUsingOptionValue($value)->isNotEmpty();
    }

    public function getUsageCount(): int
    {
        $table = $this->target === CustomFieldTarget::Unit ? 'units' : 'properties';

        return DB::table($table)
            ->whereNotNull('metadata')
            ->whereRaw("JSON_EXTRACT(metadata, '$.\"{$this->id}\"') IS NOT NULL")
            ->count();
    }

    /**
     * @return Collection<int, object>
     */
    public function getRecordsUsingOptionValue(string $value): Collection
    {
        $table = $this->target === CustomFieldTarget::Unit ? 'units' : 'properties';

        return DB::table($table)
            ->select('id', 'name')
            ->whereNotNull('metadata')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.\"{$this->id}\"')) = ?", [$value])
            ->limit(10)
            ->get();
    }
}
