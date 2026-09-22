<?php

namespace App\Filament\Resources\Calls\Pages;

use App\Enums\CallStatus;
use App\Filament\Resources\Calls\CallResource;
use App\Models\Call;
use App\Support\Persian;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCalls extends ListRecords
{
    protected static string $resource = CallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('ثبت تماس جدید'),
        ];
    }

    public function getSubheading(): string
    {
        return 'امروز '.Persian::dayName(today());
    }

    public function getTabs(): array
    {
        $count = fn (callable $scope): string => Persian::digits($scope(Call::query())->count());

        return [
            'today' => Tab::make('پیگیری امروز')
                ->modifyQueryUsing(fn (Builder $query) => $query->dueBy(today()))
                ->badge($count(fn ($q) => $q->dueBy(today())))
                ->badgeColor('warning'),

            'awaiting' => Tab::make('در انتظار پیگیری')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CallStatus::AwaitingFollowUp)),

            'done' => Tab::make('پیگیری شده')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CallStatus::Done)),

            'unreachable' => Tab::make('پاسخ نداد')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CallStatus::Unreachable)),

            'all' => Tab::make('همه'),
        ];
    }
}
