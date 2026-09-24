<?php

namespace App\Filament\Resources\Calls\Pages;

use App\Filament\Resources\Calls\CallResource;
use App\Models\Call;
use App\Support\Persian;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
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

    /** Ctrl+S saves, Ctrl+Shift+S saves and opens a fresh form for the next call. */
    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->keyBindings(['mod+s']);
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()->keyBindings(['mod+shift+s']);
    }

    /** Says what happens next, so the secretary knows the call is in the queue. */
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('تماس '.$this->record->customer?->name.' ثبت شد')
            ->body('برای '.Persian::dayName($this->record->follow_up_on).' در فهرست پیگیری قرار گرفت.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
