<?php

namespace App\Filament\Concerns;

use Filament\Forms\Components\TextInput;

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
            $uniqueConfig = ['users', $name, 'ignoreRecord' => true];

            if ($userType) {
                $uniqueConfig['modifyRuleUsing'] = function ($rule) use ($userType) {
                    return $rule->where('type', $userType);
                };
            }

            $input->unique(...$uniqueConfig);
        }

        return $input;
    }

    /**
     * Create a standardized secondary phone input field
     */
    public static function secondaryPhoneInput(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->tel()
            ->regex('/^[0-9]+$/')
            ->label($label)
            ->maxLength(20)
            ->columnSpan(6);
    }
}
