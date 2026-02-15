<?php

namespace App\Filament\Concerns;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Validation\Rule;

trait HasFormComponents
{
    /**
     * Create a standardized primary phone input field
     */
    public static function phoneInput(string $name, string $label, bool $required = false, string $userType = null, bool $unique = true): TextInput
    {
        $input = TextInput::make($name)
            ->tel()
            ->regex('/^[0-9]+$/')
            ->label($label)
            ->maxLength(20)
            ->columnSpan(6);

        if ($required) {
            $input->required();
        }

        if ($unique) {
            // Phone must be globally unique (not filtered by type)
            // because email is auto-generated from phone and email is globally unique
            $input->rules([
                fn (Get $get, ?string $operation, $record) => Rule::unique('users', $name)
                    ->ignore($record?->id),
            ]);

            $input->validationMessages([
                'unique' => 'رقم الهاتف هذا مستخدم بالفعل',
            ]);
        }

        return $input;
    }

    /**
     * Create a standardized secondary phone input field
     */
    public static function secondaryPhoneInput(string $name, string $label, bool $unique = true): TextInput
    {
        $input = TextInput::make($name)
            ->tel()
            ->regex('/^[0-9]+$/')
            ->label($label)
            ->maxLength(20)
            ->columnSpan(6)
            ->different('phone');

        if ($unique) {
            // Secondary phone must also be globally unique
            $input->rules([
                fn (Get $get, ?string $operation, $record) => Rule::unique('users', $name)
                    ->ignore($record?->id),
            ]);

            $input->validationMessages([
                'unique' => 'رقم الهاتف هذا مستخدم بالفعل',
                'different' => 'رقم الهاتف الثاني يجب أن يكون مختلفاً عن الأول',
            ]);
        } else {
            $input->validationMessages([
                'different' => 'رقم الهاتف الثاني يجب أن يكون مختلفاً عن الأول',
            ]);
        }

        return $input;
    }
}
