<?php

namespace App\Filament\Resources\CustomPlaylists\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NetworksRelationManager extends RelationManager
{
    protected static string $relationship = 'networks';

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getTabComponent(Model $ownerRecord, string $pageClass): Tab
    {
        return Tab::make('Networks')
            ->badge($ownerRecord->networks()->count())
            ->icon('heroicon-m-tv');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public function table(Table $table): Table
    {
        return $table->persistFiltersInSession()
            ->persistSortInSession()
            ->recordTitleAttribute('name')
            ->columns([
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->checkFileExistence(false)
                    ->size('inherit', 'inherit')
                    ->extraImgAttributes(fn (): array => [
                        'style' => 'height:2.5rem; width:auto; border-radius:4px;',
                    ])
                    ->defaultImageUrl(url('/placeholder.png'))
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                ToggleColumn::make('enabled')
                    ->label('Enabled'),

                TextColumn::make('channel_number')
                    ->label('Ch #')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('schedule_type')
                    ->label('Schedule')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'shuffle' => 'warning',
                        'sequential' => 'info',
                        'manual' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('effective_group_name')
                    ->label('Group')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('mediaServerIntegration.name')
                    ->label('Media Server')
                    ->placeholder('None')
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name'])
                    ->recordSelectOptionsQuery(
                        fn (Builder $query, $livewire) => $query
                            ->select(['id', 'name'])
                            ->where('user_id', $livewire->ownerRecord->user_id)
                            ->orderBy('name')
                    ),
            ])
            ->recordActions([
                DetachAction::make()
                    ->icon('heroicon-o-x-circle')
                    ->button()
                    ->hiddenLabel(),
            ], position: RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()->color('warning'),
                ]),
            ]);
    }
}
