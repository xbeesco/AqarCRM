<?php

namespace App\Exports;

use App\Models\SupplyPayment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SupplyPaymentsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return SupplyPayment::with(['propertyContract.property.owner'])->get();
    }
    
    public function headings(): array
    {
        return [
            'م',
            'البيان',
            'العقد',
            'القيمة',
            'الحالة',
            'تاريخ الاستحقاق',
            'توريد'
        ];
    }
    
    public function map($payment): array
    {
        $property = $payment->propertyContract?->property?->name ?? 'عقار';
        $contractNum = $payment->propertyContract?->contract_number ?? '';
        $month = $payment->month_year ?? '';
        $statement = "توريد {$property} - {$contractNum} - {$month}";
        
        $contractType = 'عقد إدارة أملاك';
        $ownerName = $payment->propertyContract?->property?->owner?->name ?? '';
        $contract = $contractType . ($ownerName ? " - {$ownerName}" : '');
        
        $status = match($payment->supply_status) {
            'pending' => 'قيد الانتظار',
            'worth_collecting' => 'تستحق التوريد',
            'collected' => 'تم التوريد',
            default => $payment->supply_status,
        };
        
        return [
            $payment->id,
            $statement,
            $contract,
            number_format($payment->net_amount, 2) . ' ريال',
            $status,
            $payment->due_date?->format('d/m/Y') ?? '-',
            $payment->paid_date?->format('d/m/Y') ?? '-',
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