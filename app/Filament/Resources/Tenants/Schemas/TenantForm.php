<?php

namespace App\Filament\Resources\Tenants\Schemas;

use App\Filament\Concerns\HasFormComponents;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TenantForm
{
    use HasFormComponents;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات عامة')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->label('الاسم الكامل')
                            ->maxLength(255)
                            ->columnSpan('full'),

                        static::phoneInput('phone', 'الهاتف الأول', true, 'tenant')
                            ->required(),

                        static::secondaryPhoneInput('secondary_phone', 'الهاتف الثاني'),

                        FileUpload::make('identity_file')
                            ->label('ملف الهوية')
                            ->directory('tenant--identity-file')
                            ->columnSpan('full'),
                    ])
                    ->columns(12)
                    ->columnSpan('full'),

            ]);
    }
}
