<?php

namespace App\Filament\Resources\UnitContractResource\Pages;

use App\Exports\UnitContractsExport;
use App\Filament\Resources\UnitContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListUnitContracts extends ListRecords
{
    protected static string $resource = UnitContractResource::class;

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
                    $filename = 'عقود-المستأجرين-'.date('Y-m-d').'.xlsx';

                    return Excel::download(new UnitContractsExport, $filename);
                }),
        ];
    }
}
