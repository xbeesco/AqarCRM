<?php

namespace App\Exports;

use App\Models\CollectionPayment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CollectionPaymentsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        return CollectionPayment::with(['tenant', 'unit', 'property'])->get();
    }

    public function headings(): array
    {
        return [
            'المستأجر',
            'العقار',
            'الوحدة',
            'القيمة',
            'الحالة',
            'التاريخ',
            'ملاحظات',
        ];
    }

    public function map($payment): array
    {
        // استخدام payment_status_label بدلاً من collection_status
        $status = $payment->payment_status_label;

        return [
            $payment->tenant?->name ?? 'غير محدد',
            $payment->property?->name ?? 'غير محدد',
            $payment->unit?->name ?? 'غير محدد',
            number_format($payment->amount, 2).' ريال',
            $status,
            $payment->due_date_start?->format('Y-m-d') ?? '-',
            $payment->delay_reason ?? $payment->late_payment_notes ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header row (now with 7 columns: A to G)
        $sheet->getStyle('A1:G1')->applyFromArray([
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
