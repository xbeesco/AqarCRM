<?php

namespace App\Filament\Resources\PropertyContractResource\Pages;

use App\Filament\Resources\PropertyContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Exports\PropertyContractsExport;
use Maatwebsite\Excel\Facades\Excel;

class ListPropertyContracts extends ListRecords
{
    protected static string $resource = PropertyContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة عقد'),
            Actions\Action::make('export')
                ->label('تصدير')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $filename = 'عقود-الملاك-' . date('Y-m-d') . '.xlsx';
                    
                    return Excel::download(new PropertyContractsExport, $filename);
                }),
        ];
    }
}