<?php

namespace App\Filament\Resources\Items\Schemas;

use App\Models\Item;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Item details')
                    ->schema([
                        TextEntry::make('title')
                            ->label('Title')
                            ->columnSpanFull(),

                        TextEntry::make('body')
                            ->label('Description')
                            ->prose()
                            ->columnSpanFull(),

                        TextEntry::make('category.name')
                            ->label('Category')
                            ->placeholder('-'),

                        TextEntry::make('user.name')
                            ->label('Author')
                            ->placeholder('-'),

                        TextEntry::make('price')
                            ->label('Price')
                            ->placeholder('-')
                            ->state(fn (Item $record): ?string => $record->price === null
                                ? null
                                : number_format($record->price, 2).' '.($record->currency->value ?? '')),

                        TextEntry::make('currency')
                            ->label('Currency')
                            ->badge()
                            ->placeholder('-'),

                        TextEntry::make('archived_at')
                            ->label('Archived at')
                            ->dateTime()
                            ->placeholder('-'),

                        TextEntry::make('created_at')
                            ->label('Created at')
                            ->dateTime(),

                        TextEntry::make('updated_at')
                            ->label('Updated at')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Section::make('Contacts')
                    ->schema([
                        RepeatableEntry::make('contacts')
                            ->label('')
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Name'),
                                TextEntry::make('phone')
                                    ->label('Phone'),
                            ])
                            ->columns(2),
                    ])
                    ->visible(fn (Item $record): bool => filled($record->contacts)),

                Section::make('Statistics')
                    ->schema([
                        TextEntry::make('views')
                            ->label('Views')
                            ->numeric(),

                        TextEntry::make('phone_views')
                            ->label('Phone views')
                            ->numeric(),

                        TextEntry::make('email_views')
                            ->label('Email views')
                            ->numeric(),
                    ])
                    ->columns(3),
            ]);
    }
}
