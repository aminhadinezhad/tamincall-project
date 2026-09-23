<?php

namespace App\Filament\Resources\Customers;

use App\Enums\CustomerType;
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
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
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
                TextColumn::make('type')->label('نوع مشتری')->badge()->color(fn ($state): string => $state === CustomerType::Legal ? 'info' : 'gray')->placeholder('—'),
                TextColumn::make('company')->label('شرکت / سازمان')->placeholder('—')->searchable(),
                TextColumn::make('calls_count')->label('تعداد تماس')->counts('calls')->formatStateUsing(fn ($state): string => Persian::digits($state))->sortable(),
                TextColumn::make('created_at')->label('اولین تماس')->formatStateUsing(fn ($state): string => Persian::date($state))->sortable()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make()->label('پرونده'),
                // deleting a customer takes their calls and results with it, so the question says so
                DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()->isManager())
                    ->modalDescription(fn (Customer $record): string => $record->calls()->count() > 0
                        ? 'تمام تماس ها و نتیجه های این مشتری هم پاک می شوند و دیگر در گزارش ها دیده نمی شوند.'
                        : 'این مشتری پاک می شود.'),
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
