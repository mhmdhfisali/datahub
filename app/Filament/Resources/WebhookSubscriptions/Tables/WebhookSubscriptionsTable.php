<?php

namespace App\Filament\Resources\WebhookSubscriptions\Tables;

use App\Models\WebhookSubscription;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;

class WebhookSubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('url')
                    ->searchable()
                    ->limit(40),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('events')
                    ->badge()
                    ->color('info'),
                TextColumn::make('last_triggered_at')
                    ->label('Last Dispatched')
                    ->dateTime('d M Y, H:i')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('test_dispatch')
                    ->label('Test Webhook')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->action(function (WebhookSubscription $record) {
                        try {
                            $response = Http::timeout(5)->post($record->url, [
                                'event' => 'ping.test',
                                'timestamp' => now()->toIso8601String(),
                                'message' => 'DataHub webhook test verification signal.',
                            ]);
                            $record->update(['last_triggered_at' => now()]);

                            if ($response->successful()) {
                                Notification::make()
                                    ->title('Webhook Test Success')
                                    ->body("Endpoint responded with HTTP {$response->status()} OK.")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Webhook Test Received Non-200')
                                    ->body("Endpoint returned HTTP {$response->status()}.")
                                    ->warning()
                                    ->send();
                            }
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Test Webhook Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
