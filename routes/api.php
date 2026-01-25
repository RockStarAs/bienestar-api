<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TestTemplateController;
use App\Http\Controllers\TestTemplateVersionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});


Route::middleware(['auth:api', 'roles:admin,aplicador'])->group(function () {

    // Templates
    Route::get('/templates', [TestTemplateController::class, 'index']);
    Route::post('/templates', [TestTemplateController::class, 'store']);
    Route::get('/templates/{id}', [TestTemplateController::class, 'show']);
    Route::put('/templates/{id}', [TestTemplateController::class, 'update']);
    Route::delete('/templates/{id}', [TestTemplateController::class, 'destroy']); // opcional

    // Versions
    Route::get('/templates/{id}/versions', [TestTemplateVersionController::class, 'indexByTemplate']);
    Route::post('/templates/{id}/versions', [TestTemplateVersionController::class, 'storeForTemplate']);

    Route::put('/versions/{versionId}', [TestTemplateVersionController::class, 'update']);
    Route::post('/versions/{versionId}/publish', [TestTemplateVersionController::class, 'publish']);
});