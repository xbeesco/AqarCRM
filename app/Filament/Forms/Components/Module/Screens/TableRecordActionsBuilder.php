<?php

namespace App\Filament\Forms\Components\Module\Screens;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\ColorPicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class TableRecordActionsBuilder
{
    public static function make(string $name = 'record_actions'): Builder
    {
        return Builder::make($name)
            ->label('Table Record Actions')
            ->blocks([
                // View Action
                Block::make('view_action')
                    ->label('View Action')
                    ->icon('heroicon-o-eye')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->description('View record details configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Action Name')
                                        ->default('view')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->default('View')
                                        ->required(),
                                    
                                    TextInput::make('icon')
                                        ->label('Icon')
                                        ->default('heroicon-o-eye')
                                        ->placeholder('heroicon-o-eye'),
                                ]),
                                
                                Grid::make(4)->schema([
                                    Toggle::make('visible')
                                        ->label('Visible')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('disabled')
                                        ->label('Disabled')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('modal')
                                        ->label('Open in Modal')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('slideOver')
                                        ->label('Slide Over')
                                        ->inline()
                                        ->default(false),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Select::make('color')
                                    ->label('Button Color')
                                    ->options([
                                        'primary' => 'Primary',
                                        'secondary' => 'Secondary',
                                        'success' => 'Success',
                                        'danger' => 'Danger',
                                        'warning' => 'Warning',
                                        'info' => 'Info',
                                        'gray' => 'Gray',
                                    ])
                                    ->default('gray'),
                                
                                Select::make('size')
                                    ->label('Button Size')
                                    ->options([
                                        'xs' => 'Extra Small',
                                        'sm' => 'Small',
                                        'md' => 'Medium',
                                        'lg' => 'Large',
                                    ])
                                    ->default('sm'),
                                
                                TextInput::make('tooltip')
                                    ->label('Tooltip Text')
                                    ->placeholder('View details'),
                                
                                Select::make('tooltip_position')
                                    ->label('Tooltip Position')
                                    ->options([
                                        'top' => 'Top',
                                        'bottom' => 'Bottom',
                                        'left' => 'Left',
                                        'right' => 'Right',
                                    ])
                                    ->default('top'),
                                
                                TextInput::make('modal_heading')
                                    ->label('Modal Heading')
                                    ->placeholder('View Record'),
                                
                                TextInput::make('modal_width')
                                    ->label('Modal Width')
                                    ->placeholder('2xl, 3xl, 4xl, 5xl, 6xl, 7xl, screen')
                                    ->default('4xl'),
                                
                                Toggle::make('modal_footer')
                                    ->label('Show Modal Footer')
                                    ->inline()
                                    ->default(true),
                                
                                TextInput::make('permission')
                                    ->label('Required Permission')
                                    ->placeholder('view_records'),
                                
                                Textarea::make('authorize_callback')
                                    ->label('Authorization Callback')
                                    ->rows(2)
                                    ->placeholder('return $user->can("view", $record);'),
                                
                                KeyValue::make('extra_attributes')
                                    ->label('Extra Attributes'),
                            ]),
                    ]),
                
                // Edit Action
                Block::make('edit_action')
                    ->label('Edit Action')
                    ->icon('heroicon-o-pencil')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Action Name')
                                        ->default('edit')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->default('Edit')
                                        ->required(),
                                    
                                    TextInput::make('icon')
                                        ->label('Icon')
                                        ->default('heroicon-o-pencil')
                                        ->placeholder('heroicon-o-pencil'),
                                ]),
                                
                                Grid::make(4)->schema([
                                    Toggle::make('visible')
                                        ->label('Visible')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('disabled')
                                        ->label('Disabled')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('modal')
                                        ->label('Open in Modal')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('slideOver')
                                        ->label('Slide Over')
                                        ->inline()
                                        ->default(false),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Select::make('color')
                                    ->label('Button Color')
                                    ->options([
                                        'primary' => 'Primary',
                                        'secondary' => 'Secondary',
                                        'success' => 'Success',
                                        'danger' => 'Danger',
                                        'warning' => 'Warning',
                                        'info' => 'Info',
                                        'gray' => 'Gray',
                                    ])
                                    ->default('primary'),
                                
                                Toggle::make('fill_form')
                                    ->label('Fill Form with Data')
                                    ->inline()
                                    ->default(true),
                                
                                TextInput::make('before_callback')
                                    ->label('Before Edit Callback')
                                    ->placeholder('Function to run before edit'),
                                
                                TextInput::make('after_callback')
                                    ->label('After Save Callback')
                                    ->placeholder('Function to run after save'),
                                
                                Toggle::make('refresh_table')
                                    ->label('Refresh Table After Save')
                                    ->inline()
                                    ->default(true),
                                
                                TextInput::make('success_notification')
                                    ->label('Success Message')
                                    ->default('Record updated successfully'),
                                
                                TextInput::make('modal_heading')
                                    ->label('Modal Heading')
                                    ->placeholder('Edit Record')
                                    ->visible(fn ($get) => $get('modal')),
                                
                                TextInput::make('modal_submit_label')
                                    ->label('Submit Button Label')
                                    ->default('Save changes')
                                    ->visible(fn ($get) => $get('modal')),
                                
                                TextInput::make('permission')
                                    ->label('Required Permission')
                                    ->placeholder('edit_records'),
                                
                                KeyValue::make('extra_attributes')
                                    ->label('Extra Attributes'),
                            ]),
                    ]),
                
                // Delete Action
                Block::make('delete_action')
                    ->label('Delete Action')
                    ->icon('heroicon-o-trash')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Action Name')
                                        ->default('delete')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->default('Delete')
                                        ->required(),
                                    
                                    TextInput::make('icon')
                                        ->label('Icon')
                                        ->default('heroicon-o-trash')
                                        ->placeholder('heroicon-o-trash'),
                                ]),
                                
                                Grid::make(4)->schema([
                                    Toggle::make('visible')
                                        ->label('Visible')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('disabled')
                                        ->label('Disabled')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('require_confirmation')
                                        ->label('Require Confirmation')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('soft_delete')
                                        ->label('Soft Delete')
                                        ->inline()
                                        ->default(true),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Select::make('color')
                                    ->label('Button Color')
                                    ->options([
                                        'primary' => 'Primary',
                                        'secondary' => 'Secondary',
                                        'success' => 'Success',
                                        'danger' => 'Danger',
                                        'warning' => 'Warning',
                                        'info' => 'Info',
                                        'gray' => 'Gray',
                                    ])
                                    ->default('danger'),
                                
                                TextInput::make('confirmation_title')
                                    ->label('Confirmation Title')
                                    ->default('Delete Record'),
                                
                                Textarea::make('confirmation_description')
                                    ->label('Confirmation Message')
                                    ->rows(2)
                                    ->default('Are you sure you want to delete this record? This action cannot be undone.'),
                                
                                TextInput::make('confirmation_button')
                                    ->label('Confirmation Button Text')
                                    ->default('Delete'),
                                
                                TextInput::make('cancel_button')
                                    ->label('Cancel Button Text')
                                    ->default('Cancel'),
                                
                                Toggle::make('refresh_table')
                                    ->label('Refresh Table After Delete')
                                    ->inline()
                                    ->default(true),
                                
                                TextInput::make('success_notification')
                                    ->label('Success Message')
                                    ->default('Record deleted successfully'),
                                
                                TextInput::make('error_notification')
                                    ->label('Error Message')
                                    ->default('Failed to delete record'),
                                
                                TextInput::make('permission')
                                    ->label('Required Permission')
                                    ->placeholder('delete_records'),
                                
                                KeyValue::make('extra_attributes')
                                    ->label('Extra Attributes'),
                            ]),
                    ]),
                
                // Custom Action
                Block::make('custom_action')
                    ->label('Custom Action')
                    ->icon('heroicon-o-cog')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Action Name')
                                        ->required()
                                        ->regex('/^[a-z_]+$/')
                                        ->placeholder('approve, publish, archive'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->required()
                                        ->placeholder('Approve, Publish, Archive'),
                                    
                                    TextInput::make('icon')
                                        ->label('Icon')
                                        ->placeholder('heroicon-o-check'),
                                ]),
                                
                                Select::make('action_type')
                                    ->label('Action Type')
                                    ->options([
                                        'update' => 'Update Record',
                                        'modal' => 'Open Modal',
                                        'redirect' => 'Redirect',
                                        'download' => 'Download',
                                        'api_call' => 'API Call',
                                        'custom' => 'Custom Function',
                                    ])
                                    ->required()
                                    ->reactive(),
                                
                                Grid::make(3)->schema([
                                    Toggle::make('visible')
                                        ->label('Visible')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('disabled')
                                        ->label('Disabled')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('require_confirmation')
                                        ->label('Require Confirmation')
                                        ->inline()
                                        ->default(false),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                Select::make('color')
                                    ->label('Button Color')
                                    ->options([
                                        'primary' => 'Primary',
                                        'secondary' => 'Secondary',
                                        'success' => 'Success',
                                        'danger' => 'Danger',
                                        'warning' => 'Warning',
                                        'info' => 'Info',
                                        'gray' => 'Gray',
                                    ])
                                    ->default('primary'),
                                
                                // Update Action Specific Fields
                                KeyValue::make('update_fields')
                                    ->label('Fields to Update')
                                    ->keyLabel('Field')
                                    ->valueLabel('Value')
                                    ->visible(fn ($get) => $get('action_type') === 'update'),
                                
                                // Modal Action Specific Fields
                                TextInput::make('modal_heading')
                                    ->label('Modal Heading')
                                    ->visible(fn ($get) => $get('action_type') === 'modal'),
                                
                                Select::make('modal_width')
                                    ->label('Modal Width')
                                    ->options([
                                        'sm' => 'Small',
                                        'md' => 'Medium',
                                        'lg' => 'Large',
                                        'xl' => 'Extra Large',
                                        '2xl' => '2XL',
                                        '3xl' => '3XL',
                                        '4xl' => '4XL',
                                        '5xl' => '5XL',
                                    ])
                                    ->default('2xl')
                                    ->visible(fn ($get) => $get('action_type') === 'modal'),
                                
                                // Redirect Action Specific Fields
                                TextInput::make('redirect_url')
                                    ->label('Redirect URL')
                                    ->placeholder('/admin/records/{id}/details')
                                    ->visible(fn ($get) => $get('action_type') === 'redirect'),
                                
                                Toggle::make('open_in_new_tab')
                                    ->label('Open in New Tab')
                                    ->inline()
                                    ->default(false)
                                    ->visible(fn ($get) => $get('action_type') === 'redirect'),
                                
                                // Download Action Specific Fields
                                Select::make('download_type')
                                    ->label('Download Type')
                                    ->options([
                                        'pdf' => 'PDF',
                                        'excel' => 'Excel',
                                        'csv' => 'CSV',
                                        'json' => 'JSON',
                                        'xml' => 'XML',
                                    ])
                                    ->visible(fn ($get) => $get('action_type') === 'download'),
                                
                                TextInput::make('filename_pattern')
                                    ->label('Filename Pattern')
                                    ->placeholder('record_{id}_{date}')
                                    ->visible(fn ($get) => $get('action_type') === 'download'),
                                
                                // API Call Specific Fields
                                Select::make('api_method')
                                    ->label('HTTP Method')
                                    ->options([
                                        'GET' => 'GET',
                                        'POST' => 'POST',
                                        'PUT' => 'PUT',
                                        'PATCH' => 'PATCH',
                                        'DELETE' => 'DELETE',
                                    ])
                                    ->visible(fn ($get) => $get('action_type') === 'api_call'),
                                
                                TextInput::make('api_endpoint')
                                    ->label('API Endpoint')
                                    ->placeholder('/api/records/{id}/action')
                                    ->visible(fn ($get) => $get('action_type') === 'api_call'),
                                
                                // Custom Function Specific Fields
                                TextInput::make('function_name')
                                    ->label('Function Name')
                                    ->placeholder('handleCustomAction')
                                    ->visible(fn ($get) => $get('action_type') === 'custom'),
                                
                                Textarea::make('function_body')
                                    ->label('Function Body')
                                    ->rows(4)
                                    ->placeholder('// Custom JavaScript or PHP code')
                                    ->visible(fn ($get) => $get('action_type') === 'custom'),
                                
                                // Common Advanced Fields
                                TextInput::make('success_notification')
                                    ->label('Success Message')
                                    ->placeholder('Action completed successfully'),
                                
                                TextInput::make('error_notification')
                                    ->label('Error Message')
                                    ->placeholder('Action failed'),
                                
                                Toggle::make('refresh_table')
                                    ->label('Refresh Table After Action')
                                    ->inline()
                                    ->default(true),
                                
                                TextInput::make('permission')
                                    ->label('Required Permission')
                                    ->placeholder('perform_custom_action'),
                                
                                Textarea::make('visible_condition')
                                    ->label('Visibility Condition')
                                    ->rows(2)
                                    ->placeholder('return $record->status === "pending";'),
                                
                                KeyValue::make('extra_attributes')
                                    ->label('Extra Attributes'),
                            ]),
                    ]),
                
                // Replicate Action
                Block::make('replicate_action')
                    ->label('Replicate Action')
                    ->icon('heroicon-o-document-duplicate')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Action Name')
                                        ->default('replicate')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->default('Duplicate')
                                        ->required(),
                                    
                                    TextInput::make('icon')
                                        ->label('Icon')
                                        ->default('heroicon-o-document-duplicate'),
                                ]),
                                
                                Grid::make(3)->schema([
                                    Toggle::make('visible')
                                        ->label('Visible')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('disabled')
                                        ->label('Disabled')
                                        ->inline()
                                        ->default(false),
                                    
                                    Toggle::make('open_form')
                                        ->label('Open Edit Form')
                                        ->inline()
                                        ->default(true),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                KeyValue::make('exclude_fields')
                                    ->label('Fields to Exclude')
                                    ->keyLabel('Field')
                                    ->valueLabel('Reason')
                                    ->default([
                                        'id' => 'Auto-generated',
                                        'created_at' => 'Timestamp',
                                        'updated_at' => 'Timestamp',
                                    ]),
                                
                                KeyValue::make('modify_fields')
                                    ->label('Fields to Modify')
                                    ->keyLabel('Field')
                                    ->valueLabel('New Value')
                                    ->helperText('e.g., title => "{original} (Copy)"'),
                                
                                TextInput::make('before_callback')
                                    ->label('Before Replicate Callback')
                                    ->placeholder('Function to run before replication'),
                                
                                TextInput::make('after_callback')
                                    ->label('After Replicate Callback')
                                    ->placeholder('Function to run after replication'),
                                
                                TextInput::make('success_notification')
                                    ->label('Success Message')
                                    ->default('Record duplicated successfully'),
                                
                                TextInput::make('permission')
                                    ->label('Required Permission')
                                    ->placeholder('replicate_records'),
                            ]),
                    ]),
                
                // Export Action
                Block::make('export_action')
                    ->label('Export Action')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Action Name')
                                        ->default('export')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->default('Export')
                                        ->required(),
                                    
                                    TextInput::make('icon')
                                        ->label('Icon')
                                        ->default('heroicon-o-arrow-down-tray'),
                                ]),
                                
                                Select::make('format')
                                    ->label('Export Format')
                                    ->options([
                                        'pdf' => 'PDF',
                                        'excel' => 'Excel',
                                        'csv' => 'CSV',
                                        'json' => 'JSON',
                                        'xml' => 'XML',
                                    ])
                                    ->required()
                                    ->reactive(),
                                
                                Grid::make(2)->schema([
                                    Toggle::make('visible')
                                        ->label('Visible')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('disabled')
                                        ->label('Disabled')
                                        ->inline()
                                        ->default(false),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                TextInput::make('filename_pattern')
                                    ->label('Filename Pattern')
                                    ->placeholder('record_{id}_{date}')
                                    ->default('record_{id}'),
                                
                                KeyValue::make('columns')
                                    ->label('Columns to Export')
                                    ->keyLabel('Column')
                                    ->valueLabel('Label')
                                    ->helperText('Leave empty to export all columns'),
                                
                                Select::make('pdf_orientation')
                                    ->label('PDF Orientation')
                                    ->options([
                                        'portrait' => 'Portrait',
                                        'landscape' => 'Landscape',
                                    ])
                                    ->default('portrait')
                                    ->visible(fn ($get) => $get('format') === 'pdf'),
                                
                                Select::make('pdf_size')
                                    ->label('PDF Page Size')
                                    ->options([
                                        'a4' => 'A4',
                                        'letter' => 'Letter',
                                        'legal' => 'Legal',
                                    ])
                                    ->default('a4')
                                    ->visible(fn ($get) => $get('format') === 'pdf'),
                                
                                Toggle::make('include_headers')
                                    ->label('Include Headers')
                                    ->inline()
                                    ->default(true)
                                    ->visible(fn ($get) => in_array($get('format'), ['csv', 'excel'])),
                                
                                TextInput::make('permission')
                                    ->label('Required Permission')
                                    ->placeholder('export_records'),
                                
                                KeyValue::make('extra_attributes')
                                    ->label('Extra Attributes'),
                            ]),
                    ]),
                
                // Print Action
                Block::make('print_action')
                    ->label('Print Action')
                    ->icon('heroicon-o-printer')
                    ->schema([
                        Section::make('Basic Configuration')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Action Name')
                                        ->default('print')
                                        ->required()
                                        ->regex('/^[a-z_]+$/'),
                                    
                                    TextInput::make('label')
                                        ->label('Display Label')
                                        ->default('Print')
                                        ->required(),
                                    
                                    TextInput::make('icon')
                                        ->label('Icon')
                                        ->default('heroicon-o-printer'),
                                ]),
                                
                                Grid::make(2)->schema([
                                    Toggle::make('visible')
                                        ->label('Visible')
                                        ->inline()
                                        ->default(true),
                                    
                                    Toggle::make('preview')
                                        ->label('Show Preview')
                                        ->inline()
                                        ->default(true),
                                ]),
                            ]),
                        
                        Section::make('Advanced Configuration')
                            ->collapsed()
                            ->schema([
                                TextInput::make('template')
                                    ->label('Print Template')
                                    ->placeholder('resources/views/print/record.blade.php'),
                                
                                Select::make('orientation')
                                    ->label('Print Orientation')
                                    ->options([
                                        'portrait' => 'Portrait',
                                        'landscape' => 'Landscape',
                                    ])
                                    ->default('portrait'),
                                
                                KeyValue::make('margins')
                                    ->label('Page Margins')
                                    ->keyLabel('Side')
                                    ->valueLabel('Size')
                                    ->default([
                                        'top' => '1cm',
                                        'right' => '1cm',
                                        'bottom' => '1cm',
                                        'left' => '1cm',
                                    ]),
                                
                                Toggle::make('include_header')
                                    ->label('Include Header')
                                    ->inline()
                                    ->default(true),
                                
                                Toggle::make('include_footer')
                                    ->label('Include Footer')
                                    ->inline()
                                    ->default(true),
                                
                                TextInput::make('permission')
                                    ->label('Required Permission')
                                    ->placeholder('print_records'),
                            ]),
                    ]),
            ])
            ->addActionLabel('Add Table Action')
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->blockNumbers(false)
            ->columnSpanFull();
    }
}