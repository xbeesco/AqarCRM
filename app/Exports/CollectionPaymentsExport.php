<?php

namespace App\Exports;

use App\Models\CollectionPayment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CollectionPaymentsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return CollectionPayment::with(['unitContract.tenant', 'unitContract.unit', 'unitContract.property'])->get();
    }
    
    public function headings(): array
    {
        return [
            'م',
            'البيان',
            'العقد',
            'القيمة',
            'الحالة',
            'التاريخ',
            'ملاحظات'
        ];
    }
    
    public function map($payment): array
    {
        $property = $payment->unitContract?->property?->name ?? '';
        $unit = $payment->unitContract?->unit?->name ?? '';
        $tenant = $payment->unitContract?->tenant?->name ?? '';
        $statement = "تحصيل {$property} - {$unit} - {$tenant}";
        
        $status = CollectionPayment::getStatusOptions()[$payment->collection_status] ?? $payment->collection_status;
        
        return [
            $payment->id,
            $statement,
            $payment->unitContract?->contract_number ?? '-',
            number_format($payment->amount, 2) . ' ريال',
            $status,
            $payment->due_date_start?->format('d/m/Y') ?? '-',
            $payment->delay_reason ?? $payment->late_payment_notes ?? '-',
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