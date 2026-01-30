<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Enums\UserType;
use App\Filament\Concerns\HasFormComponents;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class EmployeeForm
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

                        static::phoneInput('phone', 'الهاتف الأول', true)
                            ->required(),

                        static::secondaryPhoneInput('secondary_phone', 'الهاتف الثاني'),

                        FileUpload::make('identity_file')
                            ->label('ملف الهوية')
                            ->directory('employee--identity-file')
                            ->columnSpan('full'),
                    ])
                    ->columns(12)
                    ->columnSpan('full'),

                Section::make('معلومات الدخول')
                    ->schema([
                        Select::make('type')
                            ->label('نوع المستخدم')
                            ->options([
                                UserType::SUPER_ADMIN->value => UserType::SUPER_ADMIN->label(),
                                UserType::ADMIN->value => UserType::ADMIN->label(),
                                UserType::EMPLOYEE->value => UserType::EMPLOYEE->label(),
                            ])
                            ->default(UserType::EMPLOYEE->value)
                            ->required()
                            ->visible(fn () => auth()->user()->type === 'super_admin')
                            ->disabled(
                                fn (string $operation, $record = null) => $operation === 'edit' &&
                                    $record &&
                                    auth()->user()->type === 'admin' &&
                                    in_array($record->type, ['super_admin', 'admin'])
                            )
                            ->columnSpan(12),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->label('البريد الإلكتروني')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(6),

                        TextInput::make('password')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->label('كلمة المرور')
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->maxLength(255)
                            ->columnSpan(6),
                    ])
                    ->columns(12)
                    ->columnSpan('full'),
            ]);
    }
}
