<?php

namespace App\Filament\Resources\Calls\Tables;

use App\Enums\AcquisitionSource;
use App\Enums\CallStatus;
use App\Enums\CustomerType;
use App\Filament\Resources\Calls\Actions\RecordFollowUpAction;
use App\Models\Call;
use App\Models\Customer;
use App\Support\Persian;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CallsTable
{
    /** Periods for the "date of call" filter, in days back from today. */
    public const PERIODS = [
        'today' => 'امروز',
        '7' => '۷ روز اخیر',
        '30' => '۳۰ روز اخیر',
        '90' => '۳ ماه اخیر',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['customer', 'salesAgent', 'result']))
            ->columns([
                TextColumn::make('created_at')
                    ->label('تاریخ تماس')
                    ->visibleFrom('md')
                    ->formatStateUsing(fn ($state): string => Persian::date($state))
                    ->sortable(),

                // the number sits under the name, so the row fits without a sideways scroll
                TextColumn::make('customer.name')
                    ->label('مشتری')
                    ->description(fn (Call $record): string => collect([Persian::digits($record->customer->phone), $record->customer->company])->filter()->join(' · '))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'customer',
                        fn (Builder $customer) => $customer
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('company', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', '%'.Customer::normalizePhone($search).'%'),
                    )),

                TextColumn::make('customer.phone')
                    ->label('شماره')
                    ->formatStateUsing(fn ($state): string => Persian::digits($state))
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('salesAgent.name')
                    ->label('کارشناس فروش')
                    ->visibleFrom('md')
                    ->placeholder('—'),

                TextColumn::make('request')
                    ->label('درخواست')
                    ->visibleFrom('lg')
                    ->limit(20)
                    ->tooltip(fn (Call $record): string => $record->request)
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('وضعیت')
                    ->visibleFrom('md')
                    ->badge(),

                TextColumn::make('follow_up_on')
                    ->label('تاریخ پیگیری')
                    ->visibleFrom('md')
                    ->formatStateUsing(fn ($state, Call $record): string => $record->status === CallStatus::AwaitingFollowUp ? Persian::date($state) : '—')
                    ->color(fn (Call $record): ?string => $record->status === CallStatus::AwaitingFollowUp && $record->follow_up_on->lt(today()) ? 'danger' : null)
                    ->sortable(),

                IconColumn::make('result.purchased')
                    ->label('خرید کرد؟')
                    ->visibleFrom('md')
                    ->boolean()
                    ->placeholder('—'),

                // switched on from the columns menu when needed; off by default so a row fits a laptop screen
                // the badge takes its colour from the enum, the same colour as the slice in the report
                TextColumn::make('source')
                    ->label('نحوه آشنایی')
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('customer.type')
                    ->label('نوع مشتری')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('notes')
                    ->label('توضیحات')
                    ->limit(30)
                    ->tooltip(fn (Call $record): ?string => $record->notes)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('follow_up_on')
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(5)
            ->filters([
                SelectFilter::make('sales_agent_id')
                    ->label('کارشناس فروش')
                    ->relationship('salesAgent', 'name')
                    ->preload(),

                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(CallStatus::class),

                SelectFilter::make('source')
                    ->label('نحوه آشنایی')
                    ->options(AcquisitionSource::class),

                SelectFilter::make('customer_type')
                    ->label('نوع مشتری')
                    ->options(CustomerType::class)
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, string $type): Builder => $query->whereHas('customer', fn (Builder $c) => $c->where('type', $type)),
                    )),

                Filter::make('period')
                    ->schema([
                        Select::make('period')
                            ->label('تاریخ تماس')
                            ->options(self::PERIODS)
                            ->placeholder('همه'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['period'] ?? null,
                        fn (Builder $query, string $period): Builder => $period === 'today'
                            ? $query->whereDate('created_at', today())
                            : $query->where('created_at', '>=', today()->subDays((int) $period)),
                    )),

                // one tick box: off, the deleted calls are hidden; on, only they are shown, so a
                // manager can bring one back
                Filter::make('trashed')
                    ->label('پاک شده ها')
                    ->baseQuery(fn (Builder $query): Builder => $query->withoutGlobalScopes([SoftDeletingScope::class]))
                    ->query(fn (Builder $query, array $data): Builder => ($data['isActive'] ?? false)
                        ? $query->onlyTrashed()
                        : $query->withoutTrashed())
                    ->visible(fn (): bool => auth()->user()?->isManager() ?? false),
            ])
            // the happy call stays a visible button; the rest fold into the row's menu
            ->recordActions([
                RecordFollowUpAction::make(),
                ActionGroup::make([
                    EditAction::make()->label('جزئیات'),
                    DeleteAction::make(),
                    RestoreAction::make()->label('برگرداندن'),
                ]),
            ])
            ->emptyStateHeading('تماسی نیست')
            ->emptyStateDescription(null);
    }
}
