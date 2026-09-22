<?php

namespace App\Filament\Resources\Calls\RelationManagers;

use App\Filament\Resources\Calls\Actions\RecordFollowUpAction;
use App\Support\Persian;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Every attempt to reach the customer about this call, newest first. Read-only: new results are
 * recorded with the "ثبت نتیجه تماس" button.
 */
class FollowUpsRelationManager extends RelationManager
{
    protected static string $relationship = 'followUps';

    protected static ?string $title = 'سابقه‌ی پیگیری';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        $satisfaction = fn (?int $state): string => $state ? RecordFollowUpAction::SATISFACTION[$state] : '—';

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('زمان')
                    ->formatStateUsing(fn ($state): string => Persian::dateTime($state)),

                IconColumn::make('answered')
                    ->label('پاسخ داد؟')
                    ->boolean(),

                IconColumn::make('purchased')
                    ->label('خرید کرد؟')
                    ->boolean()
                    ->placeholder('—'),

                TextColumn::make('no_purchase_reason')
                    ->label('دلیل خرید نکردن')
                    ->placeholder('—'),

                TextColumn::make('agent_satisfaction')
                    ->label('رضایت از کارشناس')
                    ->formatStateUsing($satisfaction)
                    ->badge()
                    ->color(fn (?int $state): string => RecordFollowUpAction::SATISFACTION_COLORS[$state] ?? 'gray')
                    ->placeholder('—'),

                TextColumn::make('overall_satisfaction')
                    ->label('رضایت کلی')
                    ->formatStateUsing($satisfaction)
                    ->badge()
                    ->color(fn (?int $state): string => RecordFollowUpAction::SATISFACTION_COLORS[$state] ?? 'gray')
                    ->placeholder('—'),

                TextColumn::make('notes')
                    ->label('توضیحات')
                    ->wrap()
                    ->placeholder('—'),

                TextColumn::make('user.name')
                    ->label('ثبت توسط')
                    ->placeholder('—'),
            ])
            ->emptyStateHeading('هنوز پیگیری ثبت نشده')
            ->emptyStateDescription(null);
    }
}
