<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Calls\Schemas\CallForm;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\RelationManagers\CallsRelationManager;
use App\Models\Customer;
use App\Support\Persian;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'تماس ها';

    protected static ?string $modelLabel = 'مشتری';

    protected static ?string $pluralModelLabel = 'مشتریان';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->columnSpanFull()->components(CallForm::customerFields()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('نام')->searchable()->sortable(),
                TextColumn::make('phone')->label('شماره')->formatStateUsing(fn ($state): string => Persian::digits($state))->copyable()->searchable(),
                // the badge takes its colour from the enum, the same colour as the slice in the report
                TextColumn::make('type')->label('نوع مشتری')->badge()->placeholder('—'),
                TextColumn::make('company')->label('شرکت / سازمان')->placeholder('—')->searchable(),
                TextColumn::make('calls_count')->label('تعداد تماس')->counts('calls')->formatStateUsing(fn ($state): string => Persian::digits($state))->sortable(),
                TextColumn::make('created_at')->label('اولین تماس')->formatStateUsing(fn ($state): string => Persian::date($state))->sortable()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            // deleted customers live behind their own tab (see ListCustomers), not behind a filter
            ->recordActions([
                EditAction::make()->label('پرونده'),
                // the customer's calls go with them, and come back with them
                DeleteAction::make()
                    ->visible(fn (Customer $record): bool => auth()->user()->isManager() && ! $record->trashed()),
                RestoreAction::make()
                    ->label('برگرداندن')
                    ->visible(fn (Customer $record): bool => auth()->user()->isManager() && $record->trashed()),
            ])
            // an empty list says why it is empty and what to do next
            ->emptyStateIcon(fn (HasTable $livewire): Heroicon => match (true) {
                filled($livewire->getTableSearch()) => Heroicon::OutlinedMagnifyingGlass,
                ($livewire->activeTab ?? null) === 'trashed' => Heroicon::OutlinedTrash,
                default => Heroicon::OutlinedUsers,
            })
            ->emptyStateHeading(fn (HasTable $livewire): string => match (true) {
                filled($livewire->getTableSearch()) => 'نتیجه ای پیدا نشد',
                ($livewire->activeTab ?? null) === 'trashed' => 'مشتری حذف شده ای نیست',
                default => 'هنوز مشتری ای ثبت نشده',
            })
            ->emptyStateDescription(fn (HasTable $livewire): string => match (true) {
                filled($livewire->getTableSearch()) => 'با نام، شرکت یا شماره ی دیگری جستجو کنید.',
                ($livewire->activeTab ?? null) === 'trashed' => 'مشتریانی که حذف شوند اینجا می آیند و می شود برشان گرداند.',
                default => 'مشتری ها با ثبت اولین تماسشان اینجا می آیند، یا با دکمه ی «مشتری جدید» بالای صفحه.',
            });
    }

    /**
     * A manager can reach a deleted customer: the «حذف شده ها» tab lists them and their file opens,
     * which is how they check one before bringing it back. A secretary never sees them.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->isManager()
            ? $query->withoutGlobalScopes([SoftDeletingScope::class])
            : $query;
    }

    /**
     * Deleting and restoring customers is a manager's job. Checked here as well as on the buttons,
     * so no request can do it either; nothing is ever deleted for good.
     */
    public static function canDelete($record): bool
    {
        return auth()->user()?->isManager() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isManager() ?? false;
    }

    public static function canRestore($record): bool
    {
        return auth()->user()?->isManager() ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->isManager() ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [
            CallsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'phone', 'company'];
    }
}
