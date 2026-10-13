<?php

namespace App\Filament\Resources\WebhookSubscriptions\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WebhookSubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Subscription Name')
                    ->placeholder('e.g. Data Sync Webhook')
                    ->required(),
                TextInput::make('url')
                    ->label('Target Endpoint URL')
                    ->url()
                    ->placeholder('https://api.yourdomain.com/webhook')
                    ->required(),
                TextInput::make('secret')
                    ->label('HMAC Secret Key (Optional)')
                    ->password()
                    ->revealable()
                    ->helperText('Used to sign the payload header with sha256'),
                CheckboxList::make('events')
                    ->label('Trigger Events')
                    ->options([
                        '*' => 'All Events (*)',
                        'dataset.created' => 'Dataset Created',
                        'dataset.updated' => 'Dataset Updated',
                        'dataset.cleaned' => 'Dataset Cleaned / Transformed',
                    ])
                    ->default(['dataset.created', 'dataset.updated'])
                    ->required(),
                Toggle::make('is_active')
                    ->label('Active Status')
                    ->default(true)
                    ->required(),
            ]);
    }
}
