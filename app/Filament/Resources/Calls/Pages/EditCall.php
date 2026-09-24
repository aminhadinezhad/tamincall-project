<?php

namespace App\Filament\Resources\Calls\Pages;

use App\Enums\CallStatus;
use App\Filament\Resources\Calls\Actions\RecordFollowUpAction;
use App\Filament\Resources\Calls\CallResource;
use App\Models\Call;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditCall extends EditRecord
{
    protected static string $resource = CallResource::class;

    public function getTitle(): string
    {
        return 'تماس '.($this->getRecord()->customer?->name ?? 'مشتری پاک شده');
    }

    /** A call is never deleted on its own: it goes and comes back with its customer. */
    protected function getHeaderActions(): array
    {
        return [
            RecordFollowUpAction::make()->after(fn () => $this->refreshFormData(['status', 'follow_up_on'])),
        ];
    }

    /** Ctrl+S saves. */
    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->keyBindings(['mod+s']);
    }

    /**
     * Picking a new follow-up day moves the call to that day, and back into the queue if it had closed.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['follow_up_in'])) {
            $data['follow_up_on'] = Call::workingDayAfter((int) $data['follow_up_in']);
            $data['status'] = CallStatus::AwaitingFollowUp;
            $data['unanswered_attempts'] = 0;
        }

        unset($data['follow_up_in']);

        return $data;
    }
}
