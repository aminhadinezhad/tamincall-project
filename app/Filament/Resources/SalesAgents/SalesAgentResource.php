<?php

namespace App\Filament\Resources\SalesAgents;

use App\Filament\Resources\SalesAgents\Pages\ManageSalesAgents;
use App\Models\SalesAgent;
use App\Support\Persian;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The sales staff customers are referred to. Managers only. An agent who leaves is switched off
 * rather than deleted, so their past calls and figures stay in the reports; deleting is kept for an
 * entry with nothing behind it, such as a name typed by mistake.
 */
class SalesAgentResource extends Resource
{
    protected static ?string $model = SalesAgent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'مدیریت';

    protected static ?string $modelLabel = 'کارشناس فروش';

    protected static ?string $pluralModelLabel = 'کارشناسان فروش';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isManager() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('نام')->required()->maxLength(255),
            TextInput::make('phone')->label('شماره داخلی / موبایل')->maxLength(20),
            Toggle::make('is_active')
                ->label('فعال')
                ->helperText('کارشناس غیرفعال در فرم ارجاع دیده نمی شود، ولی آمار گذشته اش می ماند.')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('نام')->searchable(),
                TextColumn::make('phone')->label('شماره')->placeholder('—'),
                TextColumn::make('calls_count')->label('تعداد ارجاع')->counts('calls')->formatStateUsing(fn ($state): string => Persian::digits($state))->sortable(),
                IconColumn::make('is_active')->label('فعال')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),

                // the everyday way out: the agent leaves the referral form, the reports keep them
                Action::make('toggleActive')
                    ->label(fn (SalesAgent $record): string => $record->is_active ? 'غیرفعال کردن' : 'فعال کردن')
                    ->icon(fn (SalesAgent $record): Heroicon => $record->is_active ? Heroicon::OutlinedPause : Heroicon::OutlinedPlay)
                    ->color(fn (SalesAgent $record): string => $record->is_active ? 'gray' : 'success')
                    ->requiresConfirmation()
                    ->modalDescription(fn (SalesAgent $record): string => $record->is_active
                        ? 'این کارشناس دیگر در فرم ارجاع دیده نمی شود، ولی تماس ها و آمار گذشته اش سر جای خود می ماند.'
                        : 'این کارشناس دوباره در فرم ارجاع دیده می شود.')
                    ->action(fn (SalesAgent $record) => $record->update(['is_active' => ! $record->is_active])),

                // only an agent nobody was referred to is deleted for good; one with calls behind
                // them would take their share of the reports with them
                DeleteAction::make()
                    ->disabled(fn (SalesAgent $record): bool => $record->hasHistory())
                    // grey, not red, when it cannot be used, so the row does not promise a delete
                    ->color(fn (SalesAgent $record): string => $record->hasHistory() ? 'gray' : 'danger')
                    ->tooltip(fn (SalesAgent $record): ?string => $record->hasHistory()
                        ? 'این کارشناس تماس ثبت شده دارد و برای سالم ماندن گزارش ها پاک نمی شود. به جایش غیرفعالش کنید.'
                        : null)
                    ->modalDescription('این کارشناس هیچ تماسی ندارد، پس کامل پاک می شود. این کار برگشت ندارد.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSalesAgents::route('/'),
        ];
    }
}
