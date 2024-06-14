<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Peak24HoursResource\Pages;
use App\Filament\Resources\Peak24HoursResource\RelationManagers;
use App\Models\Peak24Hours;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class Peak24HoursResource extends Resource
{
    protected static ?string $model = Peak24Hours::class;
    protected static ?string $navigationGroup = 'Peak';
    protected static ?string $navigationIcon = 'iconoir-crown';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Main Content')->schema([
                    TextInput::make('symbol')
                        ->readOnly(),
                    TextInput::make('hit_20')
                        ->numeric()
                        ->inputMode('decimal'),
                    TextInput::make('hit_time_20'),
                    TextInput::make('hit_25')
                        ->numeric()
                        ->inputMode('decimal'),
                    TextInput::make('hit_time_25'),
                    Toggle::make('status_25')
                        ->onColor('success')
                        ->offColor('danger'),
                ])
                
                
                
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

                TextColumn::make('hit_20')
                    ->label('Hit 20%')
                    ->prefix('$')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => rtrim(rtrim($state, '0'), '.')),
                TextColumn::make('hit_time_20')
                    ->label('Hit Time 20%')
                    ->sortable(),

                TextColumn::make('hit_25')
                    ->label('Hit 25% ')
                    ->prefix('$')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => rtrim(rtrim($state, '0'), '.')),
                IconColumn::make('status_25')
                    ->label('Status 25%')
                    ->sortable()
                    ->boolean(),
                TextColumn::make('hit_time_25')
                    ->label('Hit Time 25%')
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->label('Date Time')
                    ->sortable()
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListPeak24Hours::route('/'),
            'create' => Pages\CreatePeak24Hours::route('/create'),
            'edit' => Pages\EditPeak24Hours::route('/{record}/edit'),
        ];
    }
}
