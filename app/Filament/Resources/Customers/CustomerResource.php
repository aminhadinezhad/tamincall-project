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
use Filament\Tables\Filters\Filter;
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
            ->filters([
                // one tick box: off, the deleted customers are hidden; on, only they are shown
                Filter::make('trashed')
                    ->label('مشتریان حذف شده')
                    ->baseQuery(fn (Builder $query): Builder => $query->withoutGlobalScopes([SoftDeletingScope::class]))
                    ->query(fn (Builder $query, array $data): Builder => ($data['isActive'] ?? false)
                        ? $query->onlyTrashed()
                        : $query->withoutTrashed())
                    ->visible(fn (): bool => auth()->user()->isManager()),
            ])
            ->recordActions([
                EditAction::make()->label('پرونده'),
                // the customer's calls go with them, and come back with them
                DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()->isManager()),
                RestoreAction::make()->label('برگرداندن'),
            ]);
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
