<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\PublicTestController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\TestTemplateController;
use App\Http\Controllers\TestTemplateVersionController;
use App\Http\Controllers\VersionQuestionController;
use App\Http\Controllers\ResultsController;
use App\Http\Controllers\UserController;
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


// Rutas Públicas (Toma de Test)
Route::get('/public/test/{id}', [PublicTestController::class, 'show']);
Route::post('/public/test/{id}/start', [PublicTestController::class, 'start']);
Route::post('/public/test/{assignmentId}/submit', [PublicTestController::class, 'submit']);


Route::middleware(['auth:api', 'roles:admin,aplicador'])->group(function () {

    // Templates
    Route::get('/templates', [TestTemplateController::class, 'index']);
    Route::post('/templates', [TestTemplateController::class, 'store']);
    Route::get('/templates/{id}', [TestTemplateController::class, 'show']);
    Route::put('/templates/{id}', [TestTemplateController::class, 'update']);
    Route::delete('/templates/{id}', [TestTemplateController::class, 'destroy']); // opcional
    Route::get('/templatesWithPublished',[TestTemplateController::class, 'getAllTemplatesWithVersionsPublished']);

    // Versions
    Route::get('/templates/{id}/versions', [TestTemplateVersionController::class, 'indexByTemplate']);
    Route::get('/templates/{templateId}/versions/{versionId}', [TestTemplateVersionController::class, 'findVersionByTemplate']);
    Route::post('/templates/{id}/versions', [TestTemplateVersionController::class, 'storeForTemplate']);

    Route::put('/versions/{versionId}', [TestTemplateVersionController::class, 'update']);
    Route::post('/versions/{versionId}/publish', [TestTemplateVersionController::class, 'publish']);

    // Preguntas
    Route::get('/versions/{versionId}/questions', [VersionQuestionController::class, 'index']);
    Route::post('/versions/{versionId}/questions', [VersionQuestionController::class, 'store']);
    Route::put('/questions/{questionId}',[VersionQuestionController::class,'update']);

    // Tests (Aplicaciones / Periodos)
    Route::get('/tests', [TestController::class, 'index']);
    Route::post('/tests', [TestController::class, 'store']);
    Route::get('/tests/{id}', [TestController::class, 'show']);
    Route::put('/tests/{id}', [TestController::class, 'update']);
    Route::delete('/tests/{id}', [TestController::class, 'destroy']);

    // Resultados
    Route::get('/results/filters', [ResultsController::class, 'filters']);
    Route::get('/results/export', [ResultsController::class, 'export']);
    Route::get('/results', [ResultsController::class, 'index']);
});

// Admin-only routes
Route::middleware(['auth:api', 'roles:admin'])->group(function () {
    // Periods (Admin Configuration)
    Route::apiResource('periods', PeriodController::class);
    Route::get('periods/{period}/dependencies', [PeriodController::class, 'dependencies']);

    //Gestión de administrador
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);

});