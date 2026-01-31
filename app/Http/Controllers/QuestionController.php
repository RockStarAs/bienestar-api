<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateQuestionRequest;
use App\Models\TemplateQuestion;
use App\Models\TemplateQuestionOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\QuestionService;

class QuestionController extends Controller
{
    public function show($questionId, Request $request)
    {
        $includeOptions = filter_var($request->get('include_options', true), FILTER_VALIDATE_BOOLEAN);

        $q = TemplateQuestion::query();

        if ($includeOptions) {
            $q->with(['options' => function ($q) {
                $q->orderBy('order', 'asc')->orderBy('id', 'asc');
            }]);
        }

        $question = $q->findOrFail($questionId);

        return response()->json([
            'data' => $question
        ]);
    }

    /**
     * PUT /api/questions/{questionId}
     * Actualiza pregunta (solo si versión está draft; usa middleware version.draft).
     */
    public function update($questionId, UpdateQuestionRequest $request, QuestionService $questionService)
    {
        $question = TemplateQuestion::findOrFail($questionId);

        $payload = $request->validated();


        return DB::transaction(function () use ($question, $payload, $questionService) {
            $versionId = (int) $question->template_version_id;

            // Si viene order y cambia, reacomoda órdenes
            if (isset($payload['order'])) {
                $newOrder = (int) $payload['order'];

                if ($newOrder > 0 && $newOrder !== (int) $question->order) {
                    // limita newOrder al máximo+1
                    $maxOrder = (int) TemplateQuestion::where('template_version_id', $versionId)->max('order');
                    if ($newOrder > $maxOrder) $newOrder = $maxOrder;

                    $oldOrder = (int) $question->order;

                    //De manera temporal hacer que el order no choque
                    //Sacar la pregunta del rango con un valor temporal que no choque
                    $maxOrder = (int) TemplateQuestion::where('template_version_id', $versionId)->max('order');
                    $question->order = $maxOrder + 1;
                    $question->save();

                    // Mover dentro del rango sin duplicar órdenes
                    if ($newOrder < $oldOrder) {
                        // sube: desplaza hacia abajo las preguntas entre newOrder..oldOrder-1
                        TemplateQuestion::where('template_version_id', $versionId)
                            ->where('id', '!=', $question->id)
                            ->whereBetween('order', [$newOrder, $oldOrder - 1])
                            ->increment('order');
                    } else {
                        // baja: desplaza hacia arriba las preguntas entre oldOrder+1..newOrder
                        TemplateQuestion::where('template_version_id', $versionId)
                            ->where('id', '!=', $question->id)
                            ->whereBetween('order', [$oldOrder + 1, $newOrder])
                            ->decrement('order');
                    }

                    $question->order = $newOrder;
                }
            }

            // Campos básicos
            if (array_key_exists('section', $payload)) {
                $question->section = $payload['section'];
            }
            if (array_key_exists('text', $payload)) {
                $question->text = $payload['text'];
            }
            if (array_key_exists('type', $payload)) {
                $question->type = $payload['type'];
            }

            $question->required = array_key_exists('required', $payload) ? (bool) $payload['required'] : $question->required;

            $question->save();

            // si cambió a type=text o date, elimina opciones para mantener consistencia
            if ($question->type === 'text' || $question->type === 'date') {
                TemplateQuestionOption::where('question_id', $question->id)->delete();
            }

            //Manejar las opciones
            $options = $payload['options'] ?? null;
            $questionService->syncOptions($question, $options);

            $question->load(['options' => function ($q) {
                $q->orderBy('order', 'asc')->orderBy('id', 'asc');
            }]);

            return response()->json([
                'message' => 'Pregunta actualizada correctamente.',
                'data' => $question
            ]);
        });
    }

    /**
     * DELETE /api/questions/{questionId}
     * Elimina pregunta y sus opciones (solo si versión está draft).
     * Reacomoda el orden para cerrar huecos.
     */
    public function destroy($questionId)
    {
        $question = TemplateQuestion::findOrFail($questionId);

        return DB::transaction(function () use ($question) {
            $versionId = (int) $question->template_version_id;
            $deletedOrder = (int) $question->order;

            // Elimina opciones primero (si no tienes cascada)
            TemplateQuestionOption::where('question_id', $question->id)->delete();

            $question->delete();

            // Cierra el hueco de order
            TemplateQuestion::where('template_version_id', $versionId)
                ->where('order', '>', $deletedOrder)
                ->decrement('order'); //hace que se baje en un 1 el campo order

            return response()->json([
                'message' => 'Pregunta eliminada correctamente.'
            ]);
        });
    }
}
