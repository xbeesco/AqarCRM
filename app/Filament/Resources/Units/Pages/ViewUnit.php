<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Resources\Units\UnitResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUnit extends ViewRecord
{
    protected static string $resource = UnitResource::class;

    protected static ?string $title = 'عرض الوحدة';

    protected string $view = 'filament.resources.unit-resource.pages.view-unit';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('الوحدات')
                ->icon('heroicon-o-arrow-right')
                ->color('gray')
                ->url(UnitResource::getUrl('index')),
            Action::make('print')
                ->label('طباعة التقرير')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->modalHeading('طباعة تقرير الوحدة')
                ->modalContent(function () {
                    $data = $this->getViewData();

                    return view('filament.resources.unit-resource.pages.print-unit', [
                        'unit' => $this->record,
                        'specifications' => $data['specifications'],
                    ]);
                })
                ->modalWidth('5xl')
                ->modalFooterActions([
                    Action::make('printReport')
                        ->label('طباعة التقرير')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->extraAttributes([
                            'onclick' => "
                                var printContent = document.querySelector('.print-content').innerHTML;
                                var originalContent = document.body.innerHTML;
                                document.body.innerHTML = printContent;
                                window.print();
                                document.body.innerHTML = originalContent;
                                window.location.reload();
                                return false;
                            ",
                        ]),
                    Action::make('close')
                        ->label('إلغاء')
                        ->color('gray')
                        ->close(),
                ]),
            EditAction::make()->label('تعديل'),
        ];
    }

    protected function getViewData(): array
    {
        $unit = $this->record;
        $specifications = [];

        if ($unit->rent_price) {
            $specifications[] = ['label' => 'سعر الإيجار', 'value' => number_format($unit->rent_price).' ر.س'];
        }

        if ($unit->area_sqm) {
            $specifications[] = ['label' => 'المساحة', 'value' => $unit->area_sqm.' م²'];
        }

        if ($unit->floor_number !== null) {
            $specifications[] = ['label' => 'رقم الطابق', 'value' => $unit->floor_number];
        }

        if ($unit->rooms_count) {
            $specifications[] = ['label' => 'عدد الغرف', 'value' => $unit->rooms_count];
        }

        if ($unit->bathrooms_count) {
            $specifications[] = ['label' => 'عدد دورات المياه', 'value' => $unit->bathrooms_count];
        }

        if ($unit->balconies_count) {
            $specifications[] = ['label' => 'عدد الشرفات', 'value' => $unit->balconies_count];
        }

        if ($unit->has_laundry_room) {
            $specifications[] = ['label' => 'غرفة غسيل', 'value' => 'نعم'];
        }

        if ($unit->electricity_account_number) {
            $specifications[] = ['label' => 'رقم حساب الكهرباء', 'value' => $unit->electricity_account_number];
        }

        if ($unit->water_expenses) {
            $specifications[] = ['label' => 'مصروف المياه', 'value' => number_format($unit->water_expenses).' ر.س'];
        }

        return [
            'unit' => $unit,
            'specifications' => $specifications,
        ];
    }
}
