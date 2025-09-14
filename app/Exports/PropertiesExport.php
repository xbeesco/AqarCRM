<?php

namespace App\Exports;

use App\Models\Property;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PropertiesExport implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected $search;

    protected $filters;

    protected $sortColumn;

    protected $sortDirection;

    public function __construct($search = null, $filters = [], $sortColumn = 'id', $sortDirection = 'asc')
    {
        $this->search = $search;
        $this->filters = $filters;
        $this->sortColumn = $sortColumn;
        $this->sortDirection = $sortDirection;
    }

    public function query()
    {
        $query = Property::with(['owner', 'location.parent.parent.parent']);

        // Apply search if provided
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('address', 'like', '%'.$this->search.'%')
                    ->orWhereHas('owner', function ($subQuery) {
                        $subQuery->where('name', 'like', '%'.$this->search.'%');
                    })
                    ->orWhereHas('location', function ($subQuery) {
                        $subQuery->where('name', 'like', '%'.$this->search.'%');
                    });
            });
        }

        // Apply filters
        if (isset($this->filters['owner_id']) && $this->filters['owner_id']) {
            $query->where('owner_id', $this->filters['owner_id']);
        }

        if (isset($this->filters['location_id']) && $this->filters['location_id']) {
            $query->where('location_id', $this->filters['location_id']);
        }

        if (isset($this->filters['status_id']) && $this->filters['status_id']) {
            $query->where('status_id', $this->filters['status_id']);
        }

        if (isset($this->filters['type_id']) && $this->filters['type_id']) {
            $query->where('type_id', $this->filters['type_id']);
        }

        // Apply sorting
        $query->orderBy($this->sortColumn, $this->sortDirection);

        return $query;
    }

    public function headings(): array
    {
        return [
            'م',
            'اسم العقار',
            'اسم المالك',
            'الوحدات',
            'المنطقة',
            'المدينة',
            'المركز',
            'الحي',
            'العنوان',
            'عدد المواقف',
            'عدد المصاعد',
            'عدد الطوابق',
            'سنة البناء',
            'تاريخ الإنشاء',
        ];
    }

    public function map($property): array
    {
        // Get location hierarchy
        $location = $property->location;

        // Initialize location levels
        $district = '-';  // المنطقة - Level 1
        $city = '-';      // المدينة - Level 2
        $center = '-';    // المركز - Level 3
        $neighborhood = '-'; // الحي - Level 4

        if ($location) {
            // If location is level 4 (neighborhood)
            if ($location->level == 4) {
                $neighborhood = $location->name;
                if ($location->parent) {
                    $center = $location->parent->name;
                    if ($location->parent->parent) {
                        $city = $location->parent->parent->name;
                        if ($location->parent->parent->parent) {
                            $district = $location->parent->parent->parent->name;
                        }
                    }
                }
            }
            // If location is level 3 (center)
            elseif ($location->level == 3) {
                $center = $location->name;
                if ($location->parent) {
                    $city = $location->parent->name;
                    if ($location->parent->parent) {
                        $district = $location->parent->parent->name;
                    }
                }
            }
            // If location is level 2 (city)
            elseif ($location->level == 2) {
                $city = $location->name;
                if ($location->parent) {
                    $district = $location->parent->name;
                }
            }
            // If location is level 1 (district)
            elseif ($location->level == 1) {
                $district = $location->name;
            }
        }

        return [
            $property->id,
            $property->name ?? '-',
            $property->owner?->name ?? '-',
            $property->total_units ?? 0,
            $district,
            $city,
            $center,
            $neighborhood,
            $property->address ?? '-',
            $property->parking_spots ?? 0,
            $property->elevators ?? 0,
            $property->floors_count ?? 0,
            $property->built_year ?? '-',
            $property->created_at ? $property->created_at->format('Y-m-d') : '-',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER,
            'D' => NumberFormat::FORMAT_NUMBER,
            'J' => NumberFormat::FORMAT_NUMBER,
            'K' => NumberFormat::FORMAT_NUMBER,
            'L' => NumberFormat::FORMAT_NUMBER,
            'M' => NumberFormat::FORMAT_NUMBER,
            'N' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Set RTL direction for the entire sheet
        $sheet->setRightToLeft(true);

        // Style the header row
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Set row height for header
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Center align numeric columns
        $sheet->getStyle('A:A')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D:D')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J:M')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Get the highest row number
                $highestRow = $sheet->getHighestRow();

                // Apply borders to all data cells
                $sheet->getStyle('A1:N'.$highestRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D1D5DB'],
                        ],
                    ],
                ]);

                // Apply zebra striping
                for ($row = 2; $row <= $highestRow; $row++) {
                    if ($row % 2 == 0) {
                        $sheet->getStyle('A'.$row.':N'.$row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F9FAFB'],
                            ],
                        ]);
                    }
                }

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(8);  // م
                $sheet->getColumnDimension('B')->setAutoSize(true); // اسم العقار
                $sheet->getColumnDimension('C')->setAutoSize(true); // اسم المالك
                $sheet->getColumnDimension('D')->setWidth(12); // الوحدات
                $sheet->getColumnDimension('E')->setAutoSize(true); // المنطقة
                $sheet->getColumnDimension('F')->setAutoSize(true); // المدينة
                $sheet->getColumnDimension('G')->setAutoSize(true); // المركز
                $sheet->getColumnDimension('H')->setAutoSize(true); // الحي
                $sheet->getColumnDimension('I')->setAutoSize(true); // العنوان
                $sheet->getColumnDimension('J')->setWidth(12); // عدد المواقف
                $sheet->getColumnDimension('K')->setWidth(12); // عدد المصاعد
                $sheet->getColumnDimension('L')->setWidth(12); // عدد الطوابق
                $sheet->getColumnDimension('M')->setWidth(12); // سنة البناء
                $sheet->getColumnDimension('N')->setWidth(15); // تاريخ الإنشاء

                // Freeze the header row
                $sheet->freezePane('A2');

                // Set print options
                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);

                // Set print margins
                $sheet->getPageMargins()->setTop(0.75);
                $sheet->getPageMargins()->setRight(0.25);
                $sheet->getPageMargins()->setLeft(0.25);
                $sheet->getPageMargins()->setBottom(0.75);
            },
        ];
    }
}
