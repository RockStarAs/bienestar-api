<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuestionRequest;
use App\Models\TemplateQuestion;
use App\Models\TemplateQuestionOption;
use App\Models\TestTemplateVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    public function store($versionId, StoreQuestionRequest $request){
        $version = TestTemplateVersion::findOrFail($versionId);

        if ($version->status !== TestTemplateVersion::STATUS_DRAFT) {
            return response()->json([
                'message' => 'No se permiten cambios: la versión está publicada.',
                'code' => 'VERSION_LOCKED'
            ], 409);
        }

        $payload = $request->validated();

        return DB::transaction(function () use ($version, $payload, $request) {

            // order automático si no se envía
            $order = isset($payload['order']) && (int)$payload['order'] > 0
                ? (int) $payload['order']
                : ((int) TemplateQuestion::where('template_version_id', $version->id)->max('order') + 1);

            // Si el order viene "en medio", corremos hacia abajo (no pisar)
            // Ej: insertar en order=2 desplaza >=2 +1
            if (isset($payload['order']) && (int)$payload['order'] > 0) {
                TemplateQuestion::where('template_version_id', $version->id)
                    ->where('order', '>=', $order)
                    ->increment('order');
            }

            $question = TemplateQuestion::create([
                'template_version_id' => $version->id,
                'section' => $payload['section'] ?? null,
                'text' => $payload['text'],
                'type' => $payload['type'],
                'required' => array_key_exists('required', $payload) ? (bool)$payload['required'] : false,
                'order' => $order,
            ]);

            // Crear opciones opcionales si vienen
            $options = $payload['options'] ?? null;
            if (is_array($options) && count($options) > 0) {
                // Normaliza order de opciones si no viene
                $usedOrders = [];
                $nextOptOrder = 1;

                foreach ($options as $opt) {
                    $optOrder = isset($opt['order']) && (int)$opt['order'] > 0 ? (int)$opt['order'] : $nextOptOrder;

                    // Evitar orders duplicados en el payload
                    while (in_array($optOrder, $usedOrders, true)) {
                        $optOrder++;
                    }
                    $usedOrders[] = $optOrder;
                    $nextOptOrder = $optOrder + 1;

                    TemplateQuestionOption::create([
                        'question_id' => $question->id,
                        'label' => $opt['label'],
                        'value' => $opt['value'],
                        'order' => $optOrder,
                    ]);
                }
            }

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

    /**
     * PATCH /api/versions/{versionId}/questions/reorder
     * Reordena preguntas. Acepta:
     * { "items": [ {"id": 10, "order": 1}, {"id": 11, "order": 2} ] }
     * o
     * { "ids": [10, 11, 12] }  => order = index+1
     */
    public function reorder($versionId, Request $request){
        $version = TestTemplateVersion::findOrFail($versionId);

        if ($version->status !== TestTemplateVersion::STATUS_DRAFT) {
            return response()->json([
                'message' => 'No se permiten cambios: la versión está publicada.',
                'code' => 'VERSION_LOCKED'
            ], 409);
        }

        $request->validate([
            'items' => ['nullable', 'array', 'min:1'],
            'items.*.id' => ['required_with:items', 'integer'],
            'items.*.order' => ['required_with:items', 'integer', 'min:1'],

            'ids' => ['nullable', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $items = $request->input('items');
        $ids = $request->input('ids');

        if (!$items && !$ids) {
            return response()->json([
                'message' => 'Debes enviar "items" o "ids".',
                'code' => 'INVALID_PAYLOAD'
            ], 422);
        }

        return DB::transaction(function () use ($version, $items, $ids) {

            // IDs válidos de esta versión
            $validIds = TemplateQuestion::where('template_version_id', $version->id)->pluck('id')->toArray();

            if ($ids) {
                // Verifica que todos pertenezcan a la versión
                foreach ($ids as $qid) {
                    if (!in_array((int)$qid, $validIds, true)) {
                        return response()->json([
                            'message' => 'Una o más preguntas no pertenecen a esta versión.',
                            'code' => 'QUESTION_VERSION_MISMATCH'
                        ], 409);
                    }
                }

                // Aplica orden index+1
                foreach ($ids as $idx => $qid) {
                    TemplateQuestion::where('id', $qid)
                        ->where('template_version_id', $version->id)
                        ->update(['order' => $idx + 1]);
                }
            } else {
                // items con {id, order}
                foreach ($items as $it) {
                    $qid = (int) $it['id'];
                    if (!in_array($qid, $validIds, true)) {
                        return response()->json([
                            'message' => 'Una o más preguntas no pertenecen a esta versión.',
                            'code' => 'QUESTION_VERSION_MISMATCH'
                        ], 409);
                    }
                }

                // Normaliza: ordena por order y reasigna secuencial para evitar duplicados/huecos
                usort($items, fn($a, $b) => (int)$a['order'] <=> (int)$b['order']);

                $seq = 1;
                foreach ($items as $it) {
                    TemplateQuestion::where('id', (int)$it['id'])
                        ->where('template_version_id', $version->id)
                        ->update(['order' => $seq]);
                    $seq++;
                }
            }

            $updated = TemplateQuestion::where('template_version_id', $version->id)
                ->orderBy('order', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            return response()->json([
                'message' => 'Orden actualizado correctamente.',
                'data' => $updated
            ]);
        });
    }
}
