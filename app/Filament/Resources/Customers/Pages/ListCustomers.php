<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use App\Support\Persian;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('مشتری جدید'),
        ];
    }

    /**
     * Two tabs, as on the calls page: the customers, and the bin. A manager opens the second one to
     * look at what was deleted and bring it back; a secretary never sees deleted customers at all.
     */
    public function getTabs(): array
    {
        if (! auth()->user()->isManager()) {
            return [];
        }

        $deleted = Customer::onlyTrashed()->count();

        return [
            'active' => Tab::make('مشتریان')
                ->modifyQueryUsing(fn (Builder $query) => $query->withoutTrashed()),

            'trashed' => Tab::make('حذف شده ها')
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed())
                ->badge($deleted > 0 ? Persian::digits($deleted) : null)
                ->badgeColor('danger'),
        ];
    }
}
