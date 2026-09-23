<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
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
 * Who can sign in. Managers only. Accounts are switched off, not deleted, so the calls they recorded
 * keep their name.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'مدیریت';

    protected static ?string $modelLabel = 'کاربر';

    protected static ?string $pluralModelLabel = 'کاربران';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isManager() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('نام')->required()->maxLength(255),
            TextInput::make('email')->label('ایمیل (برای ورود)')->email()->required()->unique(ignoreRecord: true)->extraInputAttributes(['dir' => 'ltr']),
            TextInput::make('password')
                ->label('رمز عبور')
                ->password()
                ->revealable()
                ->minLength(8)
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->helperText(fn (string $operation): ?string => $operation === 'edit' ? 'برای تغییر ندادن، خالی بگذارید.' : null),
            // a manager cannot lock themselves out: neither by taking their own role away nor by
            // switching their own account off. Another manager can still do both.
            Select::make('role')
                ->label('نقش')
                ->options(UserRole::class)
                ->default(UserRole::Secretary)
                ->required()
                ->disabled(fn (?User $record): bool => $record?->is(auth()->user()) ?? false)
                ->helperText(fn (?User $record): ?string => $record?->is(auth()->user()) ? 'نقش خودتان را نمی توانید عوض کنید.' : null),
            Toggle::make('is_active')
                ->label('فعال')
                ->default(true)
                ->disabled(fn (?User $record): bool => $record?->is(auth()->user()) ?? false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('نام')->searchable(),
                TextColumn::make('email')->label('ایمیل'),
                TextColumn::make('role')->label('نقش')->badge(),
                IconColumn::make('is_active')->label('فعال')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                // the everyday way out: the account can no longer sign in, its name stays on its records
                Action::make('toggleActive')
                    ->label(fn (User $record): string => $record->is_active ? 'غیرفعال کردن' : 'فعال کردن')
                    ->icon(fn (User $record): Heroicon => $record->is_active ? Heroicon::OutlinedPause : Heroicon::OutlinedPlay)
                    ->color(fn (User $record): string => $record->is_active ? 'gray' : 'success')
                    ->visible(fn (User $record): bool => ! $record->is(auth()->user()))
                    ->requiresConfirmation()
                    ->modalDescription(fn (User $record): string => $record->is_active
                        ? 'این حساب دیگر نمی تواند وارد شود، ولی نامش روی تماس ها و نتیجه های گذشته می ماند.'
                        : 'این حساب دوباره می تواند وارد شود.')
                    ->action(fn (User $record) => $record->update(['is_active' => ! $record->is_active])),

                // only an account that never recorded anything is deleted for good; a name that
                // appears on a call or a result is switched off instead, so the record stays readable
                DeleteAction::make()
                    ->visible(fn (User $record): bool => ! $record->is(auth()->user()) && ! static::isLastManager($record))
                    ->disabled(fn (User $record): bool => $record->hasHistory())
                    // grey, not red, when it cannot be used, so the row does not promise a delete
                    ->color(fn (User $record): string => $record->hasHistory() ? 'gray' : 'danger')
                    ->tooltip(fn (User $record): ?string => $record->hasHistory()
                        ? 'این حساب تماس یا نتیجه ای ثبت کرده و برای سالم ماندن سوابق پاک نمی شود. به جایش غیرفعالش کنید.'
                        : null)
                    ->modalDescription('این حساب چیزی ثبت نکرده، پس کامل پاک می شود. این کار برگشت ندارد.'),
            ]);
    }

    /** The only manager left: deleting them would leave the panel with nobody to manage it. */
    private static function isLastManager(User $record): bool
    {
        return $record->isManager() && User::query()->where('role', UserRole::Manager)->count() <= 1;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
