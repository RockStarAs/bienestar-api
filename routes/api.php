<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\TestTemplateController;
use App\Http\Controllers\TestTemplateVersionController;
use App\Http\Controllers\VersionQuestionController;
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
    Route::get('/templates/{templateId}/versions/{versionId}', [TestTemplateVersionController::class, 'findVersionByTemplate']);
    Route::post('/templates/{id}/versions', [TestTemplateVersionController::class, 'storeForTemplate']);

    Route::put('/versions/{versionId}', [TestTemplateVersionController::class, 'update']);
    Route::post('/versions/{versionId}/publish', [TestTemplateVersionController::class, 'publish']);

    // Preguntas
    Route::get('/versions/{versionId}/questions', [VersionQuestionController::class, 'index']);
    Route::post('/versions/{versionId}/questions', [VersionQuestionController::class, 'store']);

    Route::patch('/versions/{versionId}/questions/reorder', [VersionQuestionController::class, 'reorder']);

    Route::get('/questions/{questionId}', [QuestionController::class, 'show']);
    Route::put('/questions/{questionId}', [QuestionController::class, 'update']);
    Route::delete('/questions/{questionId}', [QuestionController::class, 'destroy']);

    // Opciones
    Route::get('/questions/{questionId}/options', [OptionController::class, 'index']);
    Route::post('/questions/{questionId}/options', [OptionController::class, 'store']);

    Route::patch('/questions/{questionId}/options/reorder', [OptionController::class, 'reorder']);

    Route::put('/options/{optionId}', [OptionController::class, 'update']);
    Route::delete('/options/{optionId}', [OptionController::class, 'destroy']);

    // Tests (Aplicaciones / Periodos)
    Route::get('/tests', [TestController::class, 'index']);
    Route::post('/tests', [TestController::class, 'store']);
    Route::get('/tests/{id}', [TestController::class, 'show']);
    Route::put('/tests/{id}', [TestController::class, 'update']);
    Route::delete('/tests/{id}', [TestController::class, 'destroy']);
});