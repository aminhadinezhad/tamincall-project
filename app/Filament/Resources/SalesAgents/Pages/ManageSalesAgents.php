<?php

namespace App\Filament\Resources\SalesAgents\Pages;

use App\Filament\Resources\SalesAgents\SalesAgentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSalesAgents extends ManageRecords
{
    protected static string $resource = SalesAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('کارشناس جدید'),
        ];
    }
}
