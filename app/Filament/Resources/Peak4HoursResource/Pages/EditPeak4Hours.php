<?php

namespace App\Filament\Resources\Peak4HoursResource\Pages;

use App\Filament\Resources\Peak4HoursResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPeak4Hours extends EditRecord
{
    protected static string $resource = Peak4HoursResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
