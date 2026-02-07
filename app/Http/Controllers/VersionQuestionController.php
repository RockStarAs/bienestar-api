<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuestionRequest;
use App\Models\TemplateQuestion;
use App\Models\TestTemplateVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\QuestionService;

class VersionQuestionController extends Controller
{
    /**
     * GET /api/versions/{versionId}/questions
     * Lista preguntas de una versión (ordenadas).
     * Soporta paginado opcional: ?per_page=10&page=1
     * Soporta include options: ?include_options=true
     */
    public function index($versionId, Request $request){
        $version = TestTemplateVersion::findOrFail($versionId);
        $includeOptions = filter_var($request->get('include_options', true), FILTER_VALIDATE_BOOLEAN);
        // $perPage = max(1, (int) $request->get('per_page', 10));

        $q = TemplateQuestion::query()
            ->where('template_version_id', $version->id)
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc');

        if ($includeOptions) {
            $q->with(['options' => function ($q) {
                $q->orderBy('order', 'asc')->orderBy('id', 'asc');
            }]);
        }

        return response()->json([
            'version' => [
                'id' => $version->id,
                'status' => $version->status,
            ],
            'questions' => $q->get(), //retornar todas las preguntas para el objeto
        ]);
    }

    /**
     * POST /api/versions/{versionId}/questions
     * Crea pregunta (y opcionalmente opciones si vienen en request).
     * Regla: si order no viene, asigna siguiente.
     */
    public function store($versionId, StoreQuestionRequest $request, QuestionService $questionService){
        $version = TestTemplateVersion::findOrFail($versionId);

        if ($version->status !== TestTemplateVersion::STATUS_DRAFT) {
            return response()->json([
                'message' => 'No se permiten cambios: la versión está publicada.',
                'code' => 'VERSION_LOCKED'
            ], 409);
        }


        return DB::transaction(function () use ($version, $request, $questionService) {
            $question = $questionService->storeQuestion($version->id,$request);

            // Crear opciones opcionales si vienen
            $options = $request->options();
            $questionService->syncOptions($question, $options);

            // Devuelve con opciones ordenadas
            $question->load(['options' => function ($q) {
                $q->orderBy('order', 'asc')->orderBy('id', 'asc');
            }]);

            return response()->json([
                'message' => 'Pregunta creada correctamente.',
                'data' => $question
            ], 201);
        });
    }

    public function update($questionId, StoreQuestionRequest $request, QuestionService $questionService){
        $question = TemplateQuestion::findOrFail($questionId);

        if ($question->itsVersionIsPublished()) {
            return response()->json([
                'message' => 'No se permiten cambios: la versión de esta pregunta está publicada.',
                'code' => 'VERSION_LOCKED'
            ], 409);
        }

        $questionUpdated = $questionService->editQuestion($questionId,$request,$question);
        return response()->json([
            'message' => 'Pregunta modificada correctamente.',
            'data' => $question
        ], 201);
    }
}
