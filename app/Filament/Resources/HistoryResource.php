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

use function PHPUnit\Framework\isEmpty;

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

    private function handleFail($value)
    {
        if ($value == '11111') {
            return 'FAIL';
        } elseif (!empty($value)) {
            return '$' . rtrim(rtrim($value, '0'), '.');
        } else {
            return 'OPEN';
        }
    }

    private function handleResult($buy, $sell, $fail)
    {
        if ($fail != '11111' && $buy < $sell) {
            return 'PROFIT';
        } elseif ($fail != '11111' && $buy > $sell) {
            return 'LOSS';
        } elseif ($fail == '11111') {
            return 'FAIL';
        } else {
            return 'ONGOING';
        }
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
                    ->color('info')
                    ->formatStateUsing(fn ($state) => rtrim(rtrim($state, '0'), '.')),
                TextColumn::make('timeframe')
                    ->label('TF')
                    ->suffix('H')
                    ->sortable()
                    ->searchable()
                    ->colors([
                        'success' => '4',
                        'warning' => '24'
                    ])
                    ->formatStateUsing(fn ($state) => rtrim(rtrim($state, '0'), '.')),
                TextColumn::make('volume')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state) => (new self)->formatNumber($state)),

                // Custom Column to show result based on comparison
                TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        return (new self)->handleResult($record->price_hit_25, $record->price_close, $record->price_close);
                    })
                    ->colors([
                        'success' => 'PROFIT',
                        'danger' => 'LOSS',
                        'primary' => 'ONGOING',
                        'gray' => 'FAIL',
                    ]),

                TextColumn::make('price_hit_25')
                    ->label('Price Buy')
                    ->prefix('$')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => rtrim(rtrim($state, '0'), '.')),
                TextColumn::make('time_hit_25')
                    ->label('Time Buy')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('price_high')
                    ->label('Highest')
                    ->prefix('$')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state) => rtrim(rtrim($state, '0'), '.')),

                TextColumn::make('price_close')
                    ->label('Price Sell')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state) => (new self)->handleFail($state)),
                TextColumn::make('time_price_close')
                    ->label('Time Sell')
                    ->sortable(),

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
                TextColumn::make('time_hit_target_10')
                    ->label('Time Target 10%')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('time_hit_target_20')
                    ->label('Time Target 20%')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('time_hit_target_30')
                    ->label('Time Target 30%')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('time_hit_target_40')
                    ->label('Time Target 40%')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('time_hit_target_50')
                    ->label('Time Target 50%')
                    ->dateTime()
                    ->sortable(),

            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
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
