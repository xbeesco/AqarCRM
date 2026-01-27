<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\PropertyResource;
use App\Services\PropertyReportService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProperty extends ViewRecord
{
    protected static string $resource = PropertyResource::class;

    protected static ?string $title = 'عرض العقار';

    protected string $view = 'filament.resources.property-resource.pages.view-property';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('العقارات')
                ->icon('heroicon-o-arrow-right')
                ->color('gray')
                ->url(PropertyResource::getUrl('index')),
            Action::make('print')
                ->label('طباعة التقرير')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('property.print', $this->record))
                ->openUrlInNewTab(),
            EditAction::make()->label('تعديل'),
        ];
    }

    protected function getViewData(): array
    {
        $service = app(PropertyReportService::class);

        return $service->getReportData($this->record);
    }
}
