<?php

namespace App\Filament\Resources\Calls;

use App\Filament\Resources\Calls\Pages\CreateCall;
use App\Filament\Resources\Calls\Pages\EditCall;
use App\Filament\Resources\Calls\Pages\ListCalls;
use App\Filament\Resources\Calls\RelationManagers\FollowUpsRelationManager;
use App\Filament\Resources\Calls\Schemas\CallForm;
use App\Filament\Resources\Calls\Tables\CallsTable;
use App\Models\Call;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CallResource extends Resource
{
    protected static ?string $model = Call::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static string|UnitEnum|null $navigationGroup = 'تماس ها';

    protected static ?string $modelLabel = 'تماس';

    protected static ?string $pluralModelLabel = 'تماس ها';

    protected static ?string $navigationLabel = 'تماس ها و پیگیری';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return CallForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CallsTable::configure($table);
    }

    /**
     * A manager's table can reach deleted calls, so its «پاک شده ها» filter has something to show;
     * for everyone else they stay out of reach.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->isManager()
            ? $query->withoutGlobalScopes([SoftDeletingScope::class])
            : $query;
    }

    public static function getRelations(): array
    {
        return [
            FollowUpsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCalls::route('/'),
            'create' => CreateCall::route('/create'),
            'edit' => EditCall::route('/{record}/edit'),
        ];
    }

    /** Calls due today (and overdue ones), on the menu item. */
    public static function getNavigationBadge(): ?string
    {
        $due = Call::query()->dueBy(today())->count();

        return $due > 0 ? (string) $due : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return 'پیگیری های امروز';
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->isManager() ?? false;
    }
}
