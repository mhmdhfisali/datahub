<?php

use App\Http\Controllers\Api\DatasetApiController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| DataHub Developer REST API v1
|--------------------------------------------------------------------------
| Secure, read-only dataset payload endpoints and token generation
*/

Route::prefix('v1')->group(function () {
    // OpenAPI / Swagger Documentation Spec
    Route::get('/openapi', [DatasetApiController::class, 'openApiSpec'])->name('api.v1.openapi');

    // Public / Token-authenticated dataset routes
    Route::get('/datasets', [DatasetApiController::class, 'index'])->name('api.v1.datasets.index');
    Route::get('/datasets/{id}', [DatasetApiController::class, 'show'])->name('api.v1.datasets.show');
    Route::get('/datasets/{id}/data', [DatasetApiController::class, 'data'])->name('api.v1.datasets.data');

    // Token creation helper endpoint for authenticated users / credentials
    Route::post('/tokens/create', function (Request $request) {
        $validated = $request->validate([
            'email' => 'required|email',
            'token_name' => 'nullable|string',
        ]);

        $user = User::where('email', $validated['email'])->first();
        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $tokenName = $validated['token_name'] ?? 'DataHub-API-Token';
        $token = $user->createToken($tokenName);

        return response()->json([
            'status' => 'success',
            'token_name' => $tokenName,
            'plain_text_token' => $token->plainTextToken,
        ]);
    })->name('api.v1.tokens.create');
});
