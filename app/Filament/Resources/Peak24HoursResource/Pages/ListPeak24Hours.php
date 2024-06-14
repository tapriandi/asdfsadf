<?php

namespace App\Filament\Resources\Peak24HoursResource\Pages;

use App\Filament\Resources\Peak24HoursResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPeak24Hours extends ListRecords
{
    protected static string $resource = Peak24HoursResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
