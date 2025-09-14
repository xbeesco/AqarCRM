<?php

namespace App\Exports;

use App\Models\UnitContract;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UnitContractsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        return UnitContract::with(['tenant', 'unit', 'property'])->get();
    }

    public function headings(): array
    {
        return [
            'م',
            'اسم العقد',
            'اسم المستأجر',
            'الوحدة',
            'العقار',
            'المدة/شهر',
            'نهاية العقد',
            'قيمة الدفعة للإيجار',
        ];
    }

    public function map($contract): array
    {
        return [
            $contract->id,
            $contract->contract_number,
            $contract->tenant?->name ?? '-',
            $contract->unit?->name ?? '-',
            $contract->property?->name ?? '-',
            $contract->duration_months.' شهر',
            $contract->end_date?->format('Y-m-d') ?? '-',
            number_format($contract->monthly_rent, 2).' ريال',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header row
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0'],
            ],
        ]);

        // Set text direction RTL for Arabic
        $sheet->setRightToLeft(true);
    }
}
