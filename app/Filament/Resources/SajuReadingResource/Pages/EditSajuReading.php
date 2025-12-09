<?php

namespace App\Filament\Resources\SajuReadingResource\Pages;

use App\Filament\Resources\SajuReadingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSajuReading extends EditRecord
{
    protected static string $resource = SajuReadingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
