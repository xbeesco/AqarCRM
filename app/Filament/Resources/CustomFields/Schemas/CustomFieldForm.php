<?php

namespace App\Filament\Resources\CustomFields\Schemas;

use App\Enums\CustomFieldTarget;
use App\Enums\CustomFieldType;
use App\Models\CustomField;
use App\Support\CustomFieldSections;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;

class CustomFieldForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('معلومات الحقل')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('اسم الحقل')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),

                    TextInput::make('sort_order')
                        ->label('الترتيب')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->columnSpan(1),
                ]),

            Section::make('المكان')
                ->columns(2)
                ->schema([
                    Select::make('target')
                        ->label('تابع لـ')
                        ->options(collect(CustomFieldTarget::cases())->mapWithKeys(
                            fn (CustomFieldTarget $t) => [$t->value => $t->label()]
                        ))
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('section', null))
                        ->disabled(fn (?CustomField $record): bool => $record?->getUsageCount() > 0)
                        ->hint(fn (?CustomField $record): ?string => $record?->getUsageCount() > 0 ? 'لا يمكن التغيير - الحقل مستخدم' : null)
                        ->columnSpan(1),

                    Select::make('section')
                        ->label('القسم')
                        ->options(fn (callable $get): array => CustomFieldSections::forTarget($get('target') ?? ''))
                        ->required()
                        ->native(false)
                        ->columnSpan(1),

                    Grid::make(2)
                        ->columnSpanFull()
                        ->schema([
                            Toggle::make('is_required')
                                ->label('إلزامي'),

                            Toggle::make('is_searchable')
                                ->label('قابل للبحث'),
                        ]),
                ]),

            Section::make('نوع الحقل')
                ->schema([
                    Select::make('type')
                        ->label('النوع')
                        ->options(collect(CustomFieldType::cases())->mapWithKeys(
                            fn (CustomFieldType $t) => [$t->value => $t->label()]
                        ))
                        ->required()
                        ->live()
                        ->disabled(fn (?CustomField $record): bool => $record?->getUsageCount() > 0)
                        ->hint(fn (?CustomField $record): ?string => $record?->getUsageCount() > 0 ? 'لا يمكن التغيير - الحقل مستخدم' : null)
                        ->afterStateUpdated(function (string $state, callable $set): void {
                            $set('settings', match ($state) {
                                CustomFieldType::Text->value => ['sub_type' => 'text'],
                                CustomFieldType::Number->value => ['min' => null, 'max' => null],
                                CustomFieldType::Options->value => ['choices' => [], 'display' => 'menu', 'multiple' => false],
                                CustomFieldType::Attachment->value => ['accept' => 'file', 'multiple' => false],
                                default => [],
                            });
                        })
                        ->native(false),

                    // Text settings
                    Radio::make('settings.sub_type')
                        ->label('نوع حقل النص')
                        ->options([
                            'text' => 'نص قصير',
                            'textarea' => 'نص طويل',
                            'email' => 'بريد إلكتروني',
                            'link' => 'رابط',
                        ])
                        ->default('text')
                        ->inline()
                        ->required()
                        ->visible(fn (callable $get): bool => $get('type') === CustomFieldType::Text->value),

                    // Number settings
                    Grid::make(2)
                        ->schema([
                            TextInput::make('settings.min')
                                ->label('أقل قيمة')
                                ->numeric()
                                ->nullable(),

                            TextInput::make('settings.max')
                                ->label('أعلى قيمة')
                                ->numeric()
                                ->nullable(),
                        ])
                        ->visible(fn (callable $get): bool => $get('type') === CustomFieldType::Number->value),

                    // Options settings
                    Repeater::make('settings.choices')
                        ->label('الخيارات')
                        ->schema([
                            TextInput::make('value')
                                ->hidden()
                                ->default(fn () => 'opt_'.uniqid())
                                ->dehydrated(),

                            TextInput::make('label')
                                ->hiddenLabel()
                                ->placeholder('اكتب الخيار...')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->addActionLabel('إضافة خيار')
                        ->minItems(1)
                        ->reorderable()
                        ->columnSpanFull()
                        ->deleteAction(function (Action $action) {
                            return $action->before(function (array $arguments, Repeater $component) {
                                $record = $component->getRecord();
                                if (! $record instanceof CustomField) {
                                    return;
                                }

                                $items = $component->getState();
                                $item = $items[$arguments['item']] ?? null;

                                if ($item && isset($item['value'])) {
                                    $usedBy = $record->getRecordsUsingOptionValue($item['value']);
                                    if ($usedBy->isNotEmpty()) {
                                        Notification::make()
                                            ->danger()
                                            ->title('لا يمكن حذف هذا الخيار')
                                            ->body('مستخدم في '.$usedBy->count().' سجل')
                                            ->send();

                                        throw new Halt;
                                    }
                                }
                            });
                        })
                        ->visible(fn (callable $get): bool => $get('type') === CustomFieldType::Options->value),

                    Radio::make('settings.display')
                        ->label('طريقة العرض')
                        ->options([
                            'menu' => 'قائمة ( اختيار واحد )',
                            'multiselect' => 'قائمة ( اختيار متعدد )',
                            'toggle' => 'أزرار اختيار ( اختيار واحد )',
                            'checkboxes' => 'خانات اختيار ( اختيار متعدد )',
                        ])
                        ->default('menu')
                        ->columns(2)
                        ->required()
                        ->visible(fn (callable $get): bool => $get('type') === CustomFieldType::Options->value),

                    // Attachment settings
                    Grid::make(2)
                        ->schema([
                            Radio::make('settings.accept')
                                ->label('نوع الملف')
                                ->options([
                                    'file' => 'ملف',
                                    'image' => 'صورة',
                                ])
                                ->default('file')
                                ->inline()
                                ->required(),

                            Toggle::make('settings.multiple')
                                ->label('رفع متعدد'),
                        ])
                        ->visible(fn (callable $get): bool => $get('type') === CustomFieldType::Attachment->value),
                ]),
        ]);
    }
}
