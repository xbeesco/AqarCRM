<?php

namespace App\Exports;

use App\Models\PropertyContract;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PropertyContractsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return PropertyContract::with(['property.owner'])->get();
    }
    
    public function headings(): array
    {
        return [
            'م',
            'اسم العقد',
            'اسم المالك',
            'المدة',
            'العقار',
            'تاريخ الانتهاء',
            'النسبة المتفق عليها'
        ];
    }
    
    public function map($contract): array
    {
        return [
            $contract->id,
            $contract->contract_number,
            $contract->property?->owner?->name ?? '-',
            $contract->duration_months . ' شهر',
            $contract->property?->name ?? '-',
            $contract->end_date?->format('d/m/Y') ?? '-',
            $contract->commission_rate . '%',
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        // Style the header row
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0']
            ]
        ]);
        
        // Set text direction RTL for Arabic
        $sheet->setRightToLeft(true);
    }
}