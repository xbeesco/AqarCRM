<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\PropertyResource;
use App\Services\PropertyReportService;
use Filament\Resources\Pages\Page;

class PrintProperty extends Page
{
    protected static string $resource = PropertyResource::class;

    protected string $view = 'filament.resources.property-resource.pages.print-property';

    protected static ?string $title = 'طباعة تقرير العقار';

    public $record;

    public function mount($record): void
    {
        $this->record = $this->getResource()::resolveRecordRouteBinding($record);
    }

    protected function getViewData(): array
    {
        $service = app(PropertyReportService::class);
        $data = $service->getReportData($this->record);
        $data['record'] = $this->record;

        return $data;
    }
}
