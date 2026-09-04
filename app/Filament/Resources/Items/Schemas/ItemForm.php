<?php

namespace App\Filament\Resources\Items\Schemas;

use App\Enums\Currency;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Textarea::make('body')
                    ->required()
                    ->rows(6)
                    ->columnSpanFull(),

                Repeater::make('contacts')
                    ->schema([
                        TextInput::make('name')
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->required()
                            ->maxLength(32),
                    ])
                    ->table([
                        TableColumn::make('name'),
                        TableColumn::make('phone'),
                    ])
                    ->compact()
                    ->dehydrateStateUsing(static fn (?array $state): ?array => filled($state) ? $state : null)
                    ->columnSpanFull(),

                TextInput::make('price')
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->live(onBlur: true)
                    ->prefix(function (Get $get): ?string {
                        $currency = $get('currency');

                        if ($currency instanceof Currency) {
                            $formatter = new \NumberFormatter($currency->getLocale(), \NumberFormatter::CURRENCY);
                            $formatter->setTextAttribute(\NumberFormatter::CURRENCY_CODE, $currency->value);

                            return $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
                        }

                        return null;
                    }),

                Select::make('currency')
                    ->live()
                    ->options(Currency::class)
                    ->required(fn (Get $get): bool => filled($get('price'))),
            ])
            ->columns(2);
    }
}
