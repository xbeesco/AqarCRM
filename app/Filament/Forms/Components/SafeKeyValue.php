<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\KeyValue;

class SafeKeyValue extends KeyValue
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Always ensure state is an array
        $this->stateCast(function ($state) {
            // If null or not an array, return empty array
            if (!is_array($state)) {
                if ($state === null || $state === '' || (is_object($state) && empty((array)$state))) {
                    return [];
                }
                
                // Try to decode JSON if it's a string
                if (is_string($state)) {
                    $decoded = json_decode($state, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        return $decoded;
                    }
                }
                
                return [];
            }
            
            // Filter out any non-array values
            return array_filter($state, function ($value) {
                return $value !== null;
            });
        });
        
        // Set default to empty array
        $this->default([]);
        
        // Format state before hydration
        $this->formatStateUsing(function ($state) {
            if (!is_array($state)) {
                return [];
            }
            return $state;
        });
        
        // Dehydrate state to ensure it's always an array
        $this->dehydrateStateUsing(function ($state) {
            if (!is_array($state)) {
                return [];
            }
            return $state;
        });
    }
}