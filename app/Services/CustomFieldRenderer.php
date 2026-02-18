<?php

namespace App\Services;

use App\Enums\CustomFieldType;
use App\Models\CustomField;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class CustomFieldRenderer
{
    /**
     * Returns Filament form components for the given target and section.
     *
     * @return array<int, mixed>
     */
    public static function formComponents(string $target, string $section): array
    {
        $fields = CustomField::query()
            ->where('target', $target)
            ->where('section', $section)
            ->orderBy('sort_order')
            ->get();

        return $fields->flatMap(function (CustomField $field) {
            $component = static::buildFormComponent($field);

            return is_array($component) ? $component : [$component];
        })->filter()->values()->all();
    }

    /**
     * Returns Filament table filters for searchable custom fields of a target.
     *
     * @return array<int, mixed>
     */
    public static function tableFilters(string $target): array
    {
        $fields = CustomField::query()
            ->where('target', $target)
            ->where('is_searchable', true)
            ->orderBy('sort_order')
            ->get();

        return $fields->flatMap(function (CustomField $field) {
            $filter = static::buildTableFilter($field);
            if ($filter === null) {
                return [];
            }

            return is_array($filter) ? $filter : [$filter];
        })->values()->all();
    }

    private static function buildFormComponent(CustomField $field): mixed
    {
        $statePath = "metadata.{$field->id}";

        $component = match ($field->type) {
            CustomFieldType::Text => static::buildTextComponent($field, $statePath),
            CustomFieldType::Number => static::buildNumberComponent($field, $statePath),
            CustomFieldType::Options => static::buildOptionsComponent($field, $statePath),
            CustomFieldType::Attachment => static::buildAttachmentComponent($field, $statePath),
            default => null,
        };

        return $component?->columnSpanFull();
    }

    private static function buildTextComponent(CustomField $field, string $statePath): mixed
    {
        $subType = $field->getSettingsValue('sub_type', 'text');

        if ($subType === 'textarea') {
            $component = Textarea::make($statePath)
                ->label($field->name)
                ->rows(3);
        } else {
            $component = TextInput::make($statePath)
                ->label($field->name);

            if ($subType === 'email') {
                $component->email();
            } elseif ($subType === 'link') {
                $component->url();
            }
        }

        if ($field->is_required) {
            $component->required();
        } else {
            $component->nullable();
        }

        return $component;
    }

    private static function buildNumberComponent(CustomField $field, string $statePath): TextInput
    {
        $component = TextInput::make($statePath)
            ->label($field->name)
            ->numeric();

        $min = $field->getSettingsValue('min');
        $max = $field->getSettingsValue('max');

        if ($min !== null) {
            $component->minValue((float) $min);
        }

        if ($max !== null) {
            $component->maxValue((float) $max);
        }

        if ($field->is_required) {
            $component->required();
        } else {
            $component->nullable();
        }

        return $component;
    }

    private static function buildOptionsComponent(CustomField $field, string $statePath): mixed
    {
        $rawChoices = $field->getOptionsChoices();
        $choices = collect($rawChoices)->pluck('label', 'value')->all();
        $display = $field->getSettingsValue('display', 'menu');

        if ($display === 'checkboxes') {
            $component = CheckboxList::make($statePath)
                ->label($field->name)
                ->options($choices)
                ->columns(2);
        } elseif ($display === 'multiselect') {
            $component = Select::make($statePath)
                ->label($field->name)
                ->options($choices)
                ->multiple()
                ->native(false);
        } elseif ($display === 'toggle') {
            $component = ToggleButtons::make($statePath)
                ->label($field->name)
                ->options($choices)
                ->inline();
        } else {
            // menu (default)
            $component = Select::make($statePath)
                ->label($field->name)
                ->options($choices)
                ->native(false);
        }

        if ($field->is_required) {
            $component->required();
        }

        return $component;
    }

    private static function buildAttachmentComponent(CustomField $field, string $statePath): FileUpload
    {
        $isImage = $field->getSettingsValue('accept', 'file') === 'image';
        $multiple = (bool) $field->getSettingsValue('multiple', false);

        $component = FileUpload::make($statePath)
            ->label($field->name)
            ->directory("custom-fields/{$field->id}")
            ->multiple($multiple);

        if ($isImage) {
            $component->image();
        }

        if ($field->is_required) {
            $component->required();
        }

        return $component;
    }

    private static function buildTableFilter(CustomField $field): mixed
    {
        return match ($field->type) {
            CustomFieldType::Text => static::buildTextFilter($field),
            CustomFieldType::Number => static::buildNumberFilter($field),
            CustomFieldType::Options => static::buildOptionsFilter($field),
            default => null,
        };
    }

    private static function buildTextFilter(CustomField $field): Filter
    {
        return Filter::make("metadata_{$field->id}")
            ->label($field->name)
            ->form([
                TextInput::make('value')
                    ->label($field->name)
                    ->placeholder('ابحث...'),
            ])
            ->query(function (Builder $query, array $data) use ($field): Builder {
                if (filled($data['value'])) {
                    $query->whereRaw(
                        "JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.\"{$field->id}\"')) LIKE ?",
                        ['%'.$data['value'].'%']
                    );
                }

                return $query;
            })
            ->indicateUsing(function (array $data) use ($field): ?string {
                if (filled($data['value'])) {
                    return "{$field->name}: {$data['value']}";
                }

                return null;
            });
    }

    /**
     * @return array<int, Filter>
     */
    private static function buildNumberFilter(CustomField $field): array
    {
        return [
            Filter::make("metadata_{$field->id}_min")
                ->label("{$field->name} (من)")
                ->form([
                    TextInput::make('value')
                        ->label("{$field->name} (من)")
                        ->numeric(),
                ])
                ->query(function (Builder $query, array $data) use ($field): Builder {
                    if (filled($data['value'])) {
                        $query->whereRaw(
                            "CAST(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.\"{$field->id}\"')) AS DECIMAL(15,4)) >= ?",
                            [(float) $data['value']]
                        );
                    }

                    return $query;
                })
                ->indicateUsing(function (array $data) use ($field): ?string {
                    if (filled($data['value'])) {
                        return "{$field->name} من: {$data['value']}";
                    }

                    return null;
                }),

            Filter::make("metadata_{$field->id}_max")
                ->label("{$field->name} (إلى)")
                ->form([
                    TextInput::make('value')
                        ->label("{$field->name} (إلى)")
                        ->numeric(),
                ])
                ->query(function (Builder $query, array $data) use ($field): Builder {
                    if (filled($data['value'])) {
                        $query->whereRaw(
                            "CAST(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.\"{$field->id}\"')) AS DECIMAL(15,4)) <= ?",
                            [(float) $data['value']]
                        );
                    }

                    return $query;
                })
                ->indicateUsing(function (array $data) use ($field): ?string {
                    if (filled($data['value'])) {
                        return "{$field->name} إلى: {$data['value']}";
                    }

                    return null;
                }),
        ];
    }

    private static function buildOptionsFilter(CustomField $field): SelectFilter
    {
        $rawChoices = $field->getOptionsChoices();
        $choices = collect($rawChoices)->pluck('label', 'value')->all();

        return SelectFilter::make("metadata_{$field->id}")
            ->label($field->name)
            ->options($choices)
            ->query(function (Builder $query, array $data) use ($field): Builder {
                if (filled($data['value'])) {
                    $query->whereRaw(
                        "JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.\"{$field->id}\"')) = ?",
                        [$data['value']]
                    );
                }

                return $query;
            })
            ->indicateUsing(function (array $data) use ($field, $choices): ?string {
                if (filled($data['value'])) {
                    $label = $choices[$data['value']] ?? $data['value'];

                    return "{$field->name}: {$label}";
                }

                return null;
            });
    }
}
