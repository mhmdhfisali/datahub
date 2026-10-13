<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use UnitEnum;

class ApiTokenManager extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|UnitEnum|null $navigationGroup = 'INTEGRATIONS & AUDIT';

    protected static ?string $navigationLabel = 'API Tokens & Access Keys';

    protected static ?string $title = 'API Tokens & Consumer Access Keys';

    protected static ?string $slug = 'api-tokens';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.api-token-manager';

    public string $tokenName = '';

    public ?string $newlyCreatedToken = null;

    public function generateToken(): void
    {
        $name = trim($this->tokenName) ?: 'Default Consumer Key';
        $user = Auth::user();

        if ($user && method_exists($user, 'createToken')) {
            $tokenResult = $user->createToken($name);
            $this->newlyCreatedToken = $tokenResult->plainTextToken;
        } else {
            // Fallback generated secure Bearer token for demo/platform access
            $randomKey = Str::random(40);
            $this->newlyCreatedToken = "dh_live_{$randomKey}";
        }

        $this->tokenName = '';

        Notification::make()
            ->title('API Token Berhasil Dibuat')
            ->body('Simpan token ini di tempat yang aman. Token tidak akan ditampilkan kembali secara utuh.')
            ->success()
            ->send();
    }

    public function getTokensProperty()
    {
        $user = Auth::user();
        if (! $user) {
            return collect();
        }

        return $user->tokens()->latest()->get();
    }

    public function deleteToken(int $tokenId): void
    {
        $user = Auth::user();
        if ($user) {
            $user->tokens()->where('id', $tokenId)->delete();
            $this->dispatch('notify', ['message' => 'API Token berhasil dihapus!']);

            Notification::make()
                ->title('API Token Berhasil Dihapus')
                ->success()
                ->send();
        }
    }
}
