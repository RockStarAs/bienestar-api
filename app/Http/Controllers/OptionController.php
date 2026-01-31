<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOptionRequest;
use App\Http\Requests\UpdateOptionRequest;
use App\Models\TemplateQuestion;
use App\Models\TemplateQuestionOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OptionController extends Controller
{
    /**
     * GET /api/questions/{questionId}/options
     * Lista opciones (paginado siempre).
     */
    public function index($questionId, Request $request){
        $question = TemplateQuestion::findOrFail($questionId);

        $perPage = max(1, (int) $request->get('per_page', 10));

        $q = TemplateQuestionOption::query()
            ->where('question_id', $question->id)
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc');

        return response()->json([
            'question' => [
                'id' => $question->id,
                'type' => $question->type,
            ],
            'options' => $q->paginate($perPage),
        ]);
    }

    /**
     * POST /api/questions/{questionId}/options
     * Crea opción con order automático (o inserta en medio).
     */
    public function store($questionId, StoreOptionRequest $request){
        $question = TemplateQuestion::findOrFail($questionId);

        // Opcional (pero recomendado): no permitir opciones si type = text
        if ($question->type === 'text') {
            return response()->json([
                'message' => 'Esta pregunta es de tipo text y no admite opciones.',
                'code' => 'QUESTION_TYPE_NO_OPTIONS'
            ], 409);
        }

        $payload = $request->validated();

        return DB::transaction(function () use ($question, $payload) {

            // order automático si no llega
            $order = isset($payload['order']) && (int)$payload['order'] > 0
                ? (int) $payload['order']
                : ((int) TemplateQuestionOption::where('question_id', $question->id)->max('order') + 1);

            // Si el order viene "en medio", desplaza >=order +1
            if (isset($payload['order']) && (int)$payload['order'] > 0) {
                TemplateQuestionOption::where('question_id', $question->id)
                    ->where('order', '>=', $order)
                    ->increment('order');
            }

            $option = TemplateQuestionOption::create([
                'question_id' => $question->id,
                'label' => $payload['label'],
                'value' => $payload['value'],
                'order' => $order,
            ]);

            return response()->json([
                'message' => 'Opción creada correctamente.',
                'data' => $option
            ], 201);
        });
    }

    /**
     * PUT /api/options/{optionId}
     * Edita opción (y reordena si cambia order).
     */
    public function update($optionId, UpdateOptionRequest $request){
        $option = TemplateQuestionOption::findOrFail($optionId);
        $payload = $request->validated();

        return DB::transaction(function () use ($option, $payload) {
            $questionId = (int) $option->question_id;

            // Reorder si viene order y cambia
            if (isset($payload['order'])) {
                $newOrder = (int) $payload['order'];

                if ($newOrder > 0 && $newOrder !== (int) $option->order) {
                    $maxOrder = (int) TemplateQuestionOption::where('question_id', $questionId)->max('order');
                    if ($newOrder > $maxOrder) $newOrder = $maxOrder;

                    $oldOrder = (int) $option->order;

                    if ($newOrder < $oldOrder) {
                        // sube: desplaza hacia abajo newOrder..oldOrder-1
                        TemplateQuestionOption::where('question_id', $questionId)
                            ->where('id', '!=', $option->id)
                            ->whereBetween('order', [$newOrder, $oldOrder - 1])
                            ->increment('order');
                    } else {
                        // baja: desplaza hacia arriba oldOrder+1..newOrder
                        TemplateQuestionOption::where('question_id', $questionId)
                            ->where('id', '!=', $option->id)
                            ->whereBetween('order', [$oldOrder + 1, $newOrder])
                            ->decrement('order');
                    }

                    $option->order = $newOrder;
                }
            }

            $option->label = $payload['label'];
            $option->value = $payload['value'];
            $option->save();

            return response()->json([
                'message' => 'Opción actualizada correctamente.',
                'data' => $option
            ]);
        });
    }

    /**
     * DELETE /api/options/{optionId}
     * Elimina opción y cierra hueco de orden.
     */
    public function destroy($optionId){
        $option = TemplateQuestionOption::findOrFail($optionId);

        return DB::transaction(function () use ($option) {
            $questionId = (int) $option->question_id;
            $deletedOrder = (int) $option->order;

            $option->delete();

            TemplateQuestionOption::where('question_id', $questionId)
                ->where('order', '>', $deletedOrder)
                ->decrement('order');

            return response()->json([
                'message' => 'Opción eliminada correctamente.'
            ]);
        });
    }

    /**
     * PATCH /api/questions/{questionId}/options/reorder
     * Acepta:
     * { "items": [ {"id": 1, "order": 1}, {"id": 2, "order": 2} ] }
     * o
     * { "ids": [1,2,3] } => order = index+1
     */
    public function reorder($questionId, Request $request){
        $question = TemplateQuestion::findOrFail($questionId);

        // Opcional: no permitir reorder si type=text (no debería tener opciones)
        if ($question->type === 'text') {
            return response()->json([
                'message' => 'Esta pregunta es de tipo text y no admite opciones.',
                'code' => 'QUESTION_TYPE_NO_OPTIONS'
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

        return DB::transaction(function () use ($question, $items, $ids) {

            $validIds = TemplateQuestionOption::where('question_id', $question->id)->pluck('id')->toArray();

            if ($ids) {
                foreach ($ids as $oid) {
                    if (!in_array((int)$oid, $validIds, true)) {
                        return response()->json([
                            'message' => 'Una o más opciones no pertenecen a esta pregunta.',
                            'code' => 'OPTION_QUESTION_MISMATCH'
                        ], 409);
                    }
                }

                foreach ($ids as $idx => $oid) {
                    TemplateQuestionOption::where('id', (int)$oid)
                        ->where('question_id', $question->id)
                        ->update(['order' => $idx + 1]);
                }
            } else {
                foreach ($items as $it) {
                    $oid = (int) $it['id'];
                    if (!in_array($oid, $validIds, true)) {
                        return response()->json([
                            'message' => 'Una o más opciones no pertenecen a esta pregunta.',
                            'code' => 'OPTION_QUESTION_MISMATCH'
                        ], 409);
                    }
                }

                usort($items, fn($a, $b) => (int)$a['order'] <=> (int)$b['order']);

                $seq = 1;
                foreach ($items as $it) {
                    TemplateQuestionOption::where('id', (int)$it['id'])
                        ->where('question_id', $question->id)
                        ->update(['order' => $seq]);
                    $seq++;
                }
            }

            $updated = TemplateQuestionOption::where('question_id', $question->id)
                ->orderBy('order', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            return response()->json([
                'message' => 'Orden de opciones actualizado correctamente.',
                'data' => $updated
            ]);
        });
    }
}
