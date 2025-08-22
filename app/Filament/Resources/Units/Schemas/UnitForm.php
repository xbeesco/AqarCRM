<?php

namespace App\Filament\Resources\Units\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('property_id')
                    ->relationship('property', 'name')
                    ->required(),
                TextInput::make('unit_number')
                    ->required(),
                TextInput::make('floor_number')
                    ->required()
                    ->numeric(),
                TextInput::make('area_sqm')
                    ->required()
                    ->numeric(),
                TextInput::make('rooms_count')
                    ->required()
                    ->numeric(),
                TextInput::make('bathrooms_count')
                    ->required()
                    ->numeric(),
                TextInput::make('rent_price')
                    ->required()
                    ->numeric(),
                Select::make('unit_type')
                    ->options([
            'studio' => 'Studio',
            'apartment' => 'Apartment',
            'duplex' => 'Duplex',
            'penthouse' => 'Penthouse',
            'office' => 'Office',
            'shop' => 'Shop',
            'warehouse' => 'Warehouse',
        ])
                    ->default('apartment')
                    ->required(),
                Select::make('unit_ranking')
                    ->options(['economy' => 'Economy', 'standard' => 'Standard', 'premium' => 'Premium', 'luxury' => 'Luxury']),
                Select::make('direction')
                    ->options([
            'north' => 'North',
            'south' => 'South',
            'east' => 'East',
            'west' => 'West',
            'northeast' => 'Northeast',
            'northwest' => 'Northwest',
            'southeast' => 'Southeast',
            'southwest' => 'Southwest',
        ]),
                Select::make('view_type')
                    ->options([
            'street' => 'Street',
            'garden' => 'Garden',
            'sea' => 'Sea',
            'city' => 'City',
            'mountain' => 'Mountain',
            'courtyard' => 'Courtyard',
        ]),
                TextInput::make('status_id')
                    ->required()
                    ->numeric(),
                TextInput::make('current_tenant_id')
                    ->numeric(),
                Toggle::make('furnished')
                    ->required(),
                Toggle::make('has_balcony')
                    ->required(),
                Toggle::make('has_parking')
                    ->required(),
                Toggle::make('has_storage')
                    ->required(),
                Toggle::make('has_maid_room')
                    ->required(),
                Textarea::make('notes')
                    ->columnSpanFull(),
                DatePicker::make('available_from'),
                DatePicker::make('last_maintenance_date'),
                DatePicker::make('next_maintenance_date'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
