<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Components\IconEntry;
use Filament\Schemas\Components\RepeatableEntry;

class ViewLocation extends ViewRecord
{
    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
    
    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make('معلومات الموقع')
                    ->schema([
                        TextEntry::make('level_label')
                            ->label('المستوى')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'منطقة' => 'success',
                                'مدينة' => 'warning', 
                                'مركز' => 'info',
                                'حي' => 'gray',
                                default => 'gray',
                            }),
                            
                        TextEntry::make('name_ar')
                            ->label('الاسم بالعربية'),
                            
                        TextEntry::make('name_en')
                            ->label('الاسم بالإنجليزية'),
                            
                        TextEntry::make('full_path')
                            ->label('المسار الكامل'),
                            
                        TextEntry::make('code')
                            ->label('الكود'),
                            
                        TextEntry::make('postal_code')
                            ->label('الرمز البريدي'),
                            
                        TextEntry::make('coordinates')
                            ->label('الإحداثيات'),
                            
                        IconEntry::make('is_active')
                            ->label('نشط')
                            ->boolean(),
                    ])
                    ->columns(2),
                    
                \Filament\Schemas\Components\Section::make('المواقع الفرعية')
                    ->schema([
                        RepeatableEntry::make('children')
                            ->label('')
                            ->schema([
                                TextEntry::make('level_label')
                                    ->label('المستوى')
                                    ->badge(),
                                TextEntry::make('name_ar')
                                    ->label('الاسم'),
                                TextEntry::make('code')
                                    ->label('الكود'),
                            ])
                            ->columns(3)
                    ])
                    ->visible(fn ($record) => $record->children->count() > 0),
            ]);
    }
}