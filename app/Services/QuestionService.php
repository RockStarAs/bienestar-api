<?php

namespace App\Services;

use App\Models\TemplateQuestion;
use App\Models\TemplateQuestionOption;

class QuestionService
{
    /**
     * Sincroniza las opciones de una pregunta.
     * - Elimina las que no estén en el payload.
     * - Actualiza las que tengan ID.
     * - Crea las nuevas (sin ID).
     *
     * @param TemplateQuestion $question
     * @param array|null $optionsPayload
     * @return void
     */
    public function syncOptions(TemplateQuestion $question, ?array $optionsPayload)
    {
        // Si es nulo o vacío, no hacemos nada (o podríamos borrar todo si esa fuera la regla, 
        // pero asumiremos que null = no tocar, array vacío = borrar todo)
        if (!is_array($optionsPayload)) {
            return;
        }

        // 1. Obtener IDs recibidos para saber cuáles conservar
        $receivedIds = [];
        foreach ($optionsPayload as $opt) {
            if (isset($opt['id']) && $opt['id']) {
                $receivedIds[] = $opt['id'];
            }
        }

        // 2. Eliminar opciones de esta pregunta que NO estén en los IDs recibidos
        TemplateQuestionOption::where('question_id', $question->id)
            ->whereNotIn('id', $receivedIds)
            ->delete();

        // 3. Recorrer payload para Crear o Actualizar
        // Normalizamos el orden si es necesario
        $usedOrders = [];
        $nextOrder = 1;

        foreach ($optionsPayload as $optData) {
            // Calcular order (simple lógica secuencial o usar el enviado)
            $order = isset($optData['order']) && (int)$optData['order'] > 0 
                ? (int)$optData['order'] 
                : $nextOrder;
            
            // Evitar duplicados de order si se repiten en payload
            while (in_array($order, $usedOrders)) {
                $order++;
            }
            $usedOrders[] = $order;
            $nextOrder = $order + 1;

            if (isset($optData['id']) && $optData['id']) {
                // UPDATE
                // Aseguramos que pertenezca a la pregunta
                $option = TemplateQuestionOption::where('question_id', $question->id)
                    ->where('id', $optData['id'])
                    ->first();

                if ($option) {
                    $option->update([
                        'label' => $optData['label'],
                        'value' => $optData['value'],
                        'order' => $order,
                    ]);
                }
            } else {
                // CREATE
                TemplateQuestionOption::create([
                    'question_id' => $question->id,
                    'label' => $optData['label'],
                    'value' => $optData['value'],
                    'order' => $order,
                ]);
            }
        }
    }
}
