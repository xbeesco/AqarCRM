<?php

namespace App\Filament\Resources\UnitContracts\Pages;

use App\Exports\UnitContractsExport;
use App\Filament\Resources\UnitContracts\UnitContractResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListUnitContracts extends ListRecords
{
    protected static string $resource = UnitContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة عقد'),
            Action::make('export')
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
