<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HistoryResource\Pages;
use App\Filament\Resources\HistoryResource\RelationManagers;
use App\Models\History;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HistoryResource extends Resource
{
    protected static ?string $model = History::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    private function formatNumber($number)
    {
        $abbreviations = ["", "K", "M", "B", "T"];
        $index = 0;

        while ($number >= 1000 && $index < count($abbreviations) - 1) {
            $number /= 1000;
            $index++;
        }

        return round($number, 2) . " " . $abbreviations[$index];
    }
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('symbol')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('price')
                    ->prefix('$')
                    ->sortable()
                    ->searchable()
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => rtrim(rtrim($state, '0'), '.')),
                TextColumn::make('timeframe')
                    ->label('TF')
                    ->suffix('H')
                    ->sortable()
                    ->searchable()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => rtrim(rtrim($state, '0'), '.')),
                TextColumn::make('volume')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state) => rtrim(rtrim($state, '0'), '.')),
                TextColumn::make('price_high')
                    ->prefix('$')
                    ->sortable()
                    ->searchable()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => rtrim(rtrim($state, '0'), '.')),
                // TextColumn::make('exhange')
                //     ->prefix('$')
                //     ->sortable()
                //     ->searchable()
                //     ->color('primary')
                //     ->formatStateUsing(fn ($state) => rtrim(rtrim($state, '0'), '.')),

                TextColumn::make('price_hit_20')
                    ->label('Hit 20%')
                    ->prefix('$')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => rtrim(rtrim($state, '0'), '.')),
                TextColumn::make('time_hit_20')
                    ->label('Time 20%')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('price_hit_25')
                    ->label('Hit 25%')
                    ->prefix('$')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => rtrim(rtrim($state, '0'), '.')),
                TextColumn::make('time_hit_25')
                    ->label('Time 25%')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('time_price_close')
                    ->label('Time Sell')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('price_close')
                    ->prefix('$')
                    ->sortable()
                    ->searchable()
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => rtrim(rtrim($state, '0'), '.')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHistories::route('/'),
            'create' => Pages\CreateHistory::route('/create'),
            'edit' => Pages\EditHistory::route('/{record}/edit'),
        ];
    }
}
