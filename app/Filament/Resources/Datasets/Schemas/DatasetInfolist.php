<?php

namespace App\Filament\Resources\Datasets\Schemas;

use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Schema;

class DatasetInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ViewEntry::make('payload')
                    ->label('')
                    ->view('filament.resources.datasets.view-payload')
                    ->columnSpanFull(),
            ]);
    }
}
