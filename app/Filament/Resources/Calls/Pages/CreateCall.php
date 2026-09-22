<?php

namespace App\Filament\Resources\Calls\Pages;

use App\Filament\Resources\Calls\CallResource;
use App\Models\Call;
use Filament\Resources\Pages\CreateRecord;

class CreateCall extends CreateRecord
{
    protected static string $resource = CallResource::class;

    protected static bool $canCreateAnother = true;

    public function getTitle(): string
    {
        return 'ثبت تماس جدید';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['follow_up_on'] = Call::workingDayAfter((int) $data['follow_up_in']);
        $data['received_by'] = auth()->id();
        unset($data['follow_up_in']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
