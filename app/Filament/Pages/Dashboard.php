<?php

namespace App\Filament\Pages;

use App\Models\SalesAgent;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;

/**
 * Reports. Everyone sees today's to-do tiles; managers also get the period figures and charts,
 * narrowed by the period and sales-agent filters above them.
 */
class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    /** Report periods, in days back from today. */
    public const PERIODS = [
        7 => '۷ روز اخیر',
        30 => '۳۰ روز اخیر',
        90 => '۳ ماه اخیر',
        365 => 'یک سال اخیر',
    ];

    public const DEFAULT_PERIOD = 30;

    protected static ?string $title = 'داشبورد و گزارش‌ها';

    public function filtersForm(Schema $schema): Schema
    {
        // secretaries only get today's tiles, which the filters do not touch
        if (! auth()->user()->isManager()) {
            return $schema;
        }

        return $schema
            ->components([
                Select::make('period')
                    ->label('بازه‌ی زمانی')
                    ->options(self::PERIODS)
                    ->default(self::DEFAULT_PERIOD)
                    ->selectablePlaceholder(false),

                Select::make('sales_agent_id')
                    ->label('کارشناس فروش')
                    ->options(fn () => SalesAgent::query()->orderBy('name')->pluck('name', 'id'))
                    ->placeholder('همه‌ی کارشناسان'),
            ]);
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
