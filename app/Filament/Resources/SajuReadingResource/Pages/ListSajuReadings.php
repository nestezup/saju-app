<?php

namespace App\Filament\Resources\SajuReadingResource\Pages;

use App\Filament\Resources\SajuReadingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSajuReadings extends ListRecords
{
    protected static string $resource = SajuReadingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
