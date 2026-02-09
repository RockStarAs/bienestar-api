<?php

namespace App\Services;

use App\Http\Requests\StoreQuestionRequest;
use App\Models\TemplateQuestion;
use App\Models\TemplateQuestionOption;
use Exception;
use Illuminate\Support\Facades\DB;

class QuestionService
{
    public function getOrderOfQuestion(int $versionId, StoreQuestionRequest $questionRequest){
        return $questionRequest->order() != null
            ? (int)$questionRequest->order()
            : ((int) (TemplateQuestion::where('template_version_id', $versionId)->max('order') ?? 0) + 1);
    }

    //Manejar siempre dentro de transacciones!!! (para evitar dejar colgado datos)
    public function storeQuestion(int $versionId, StoreQuestionRequest $questionRequest) : TemplateQuestion{
        $order = $this->getOrderOfQuestion($versionId,$questionRequest);
        if($questionRequest->order() != null){
            $this->reorderQuestions($versionId,$order);
        }
        return $this->createQuestion($versionId,$questionRequest,$order);

    }

    public function editQuestion(
        int $questionId, 
        StoreQuestionRequest $r, 
        TemplateQuestion $question
    ): TemplateQuestion {
        return DB::transaction(function () use ($questionId,$r, $question) {

            $oldOrder = (int) $question->order;

            // si no mandan order, se queda igual
            $newOrder = $r->order() !== null ? (int) $r->order() : $oldOrder;

            // normaliza mínimo
            if ($newOrder < 1) {
                $newOrder = 1;
            }

            // limita newOrder al máximo+1
            $maxOrder = (int) TemplateQuestion::where('template_version_id', $question->template_version_id)->max('order');
            if ($newOrder > $maxOrder) $newOrder = $maxOrder;
            $question->order = $maxOrder + 1;
            $question->save();

            // reordenar solo si cambió
            if ($newOrder !== $oldOrder) {
                $this->moveQuestionOrder($question->template_version_id, $oldOrder, $newOrder, $question->id);
            }

            // actualizar datos
            $this->updateQuestion($questionId,$r,$newOrder);

            //actualizar opciones en caso llegue
            $this->syncOptions($question, $r->options());

            //Tratamiento especial para las preguntas agrupadas (importante, si no me manda el tipo explicitamente, en la request)
            //no trabaja acá, por lo que por ejemplo si es un caso de solo ordenamiento no le afectara a sus preguntas (importante)
            if($r->type() == TemplateQuestion::TYPE_GROUPED){
                // Validar al menos 1? (o 0 si se permite grupo vacío)
                $children = $r->childQuestionsObjects();
                if(count($children) < 1 && 1==0){ // Desactivado check por ahora o ajustar lógica
                    throw new Exception("Error, no hay preguntas suficientes para crear una pregunta agrupada.");
                }
                // Actualizar hijos
                $this->manageChildQuestion($question, $children);
            }

            return $question->fresh(['options']);
        });
    }

    private function manageChildQuestion(TemplateQuestion $parentQuestion, array $childQuestionRequests){
        // Estrategia: Sincronizar (Crear, Actualizar, Borrar)

        // 1. Obtener IDs recibidos que no sean nulos
        $receivedIds = [];
        foreach ($childQuestionRequests as $req) {
            if ($req->id) {
                $receivedIds[] = $req->id;
            }
        }

        // 2. Eliminar hijos que ya no están en la lista (Delete)
        $parentQuestion->children()
            ->whereNotIn('id', $receivedIds)
            ->delete();

        // 3. Iterar para Crear o Actualizar
        foreach ($childQuestionRequests as $index => $childRequest) {
            // Datos comunes
            $data = [
                'template_version_id' => $parentQuestion->template_version_id,
                'section' => $parentQuestion->section,
                'text' => $childRequest->text,
                'type' => TemplateQuestion::TYPE_GROUPED_CHILD,
                'required' => $parentQuestion->required,
                'parent_question_id' => $parentQuestion->id,
                'order' => $childRequest->order ?? ($index + 1),
            ];

            if ($childRequest->id) {
                // Update
                TemplateQuestion::where('id', $childRequest->id)
                    ->where('parent_question_id', $parentQuestion->id) // Security check
                    ->update($data);
            } else {
                // Create
                TemplateQuestion::create($data);
            }
        }
    }

    
    public function createQuestion(int $versionId, StoreQuestionRequest $questionRequest, int $pOrder){
        //Preparar los datos agregar
        $templateVersionId = $versionId;
        $section =           $questionRequest->section();
        $title =             $questionRequest->title();
        $subtitle =          $questionRequest->subtitle();
        $text =              $questionRequest->text();
        $type =              $questionRequest->type();
        $required =          !!$questionRequest->required();
        $parentQuestionId =  null;
        $order =             $pOrder;
        
        $question = TemplateQuestion::create([
            'template_version_id' => $templateVersionId,
            'section' => $section,
            'title' => $title,
            'subtitle' => $subtitle,
            'text' => $text,
            'type' => $type,
            'required' => $required,
            'parent_question_id' => $parentQuestionId,
            'order' => $order,
        ]);

        // Manejar hijos si es tipo agrupado
        if ($type === TemplateQuestion::TYPE_GROUPED) {
            $this->manageChildQuestion($question, $questionRequest->childQuestionsObjects());
        }

        return $question;
    }
            
    public function reorderQuestions(int $versionId, int $currentOrderToInsert, ?int $parentQuestionId = null){
        $q = TemplateQuestion::where('template_version_id', $versionId)
            ->where('order', '>=', $currentOrderToInsert);
            
        if(is_null($parentQuestionId)){
            $q->whereNull('parent_question_id');
        } else {
            $q->where('parent_question_id', $parentQuestionId);
        }
            
        $q->increment('order');
    }

    /**
     * Mueve una pregunta dentro del orden de una versión, ajustando a las demás.
    */
    public function moveQuestionOrder(int $versionId, int $oldOrder, int $newOrder, int $questionId): void
    {
        // Obtener el scope de la pregunta (si es hija o raíz)
        $question = TemplateQuestion::find($questionId);
        $parentId = $question ? $question->parent_question_id : null;

        $query = TemplateQuestion::where('template_version_id', $versionId)
            ->where('id', '!=', $questionId);

        if(is_null($parentId)){
            $query->whereNull('parent_question_id');
        } else {
            $query->where('parent_question_id', $parentId);
        }

        if ($newOrder < $oldOrder) {
            // sube: empuja hacia abajo a las que estaban en el rango
            $query->whereBetween('order', [$newOrder, $oldOrder - 1])
                  ->orderBy('order', 'desc') // Orden inverso para evitar colisiones al subir (aunque increment es set based, a veces ayuda)
                  ->increment('order');
        } else {
            // baja: jala hacia arriba a las que estaban en el rango
            $query->whereBetween('order', [$oldOrder + 1, $newOrder])
                  ->orderBy('order', 'asc')
                  ->decrement('order');
        }
    }

    public function updateQuestion(
        int $questionId,
        StoreQuestionRequest $questionRequest,
        int $pOrder
    ){
        //Preparar los datos agregar
        $section           =    $questionRequest->section();
        $title             =    $questionRequest->title();
        $subtitle          =    $questionRequest->subtitle();
        $text              =    $questionRequest->text();
        $type              =    $questionRequest->type();
        $required          =    !!$questionRequest->required();
        $parentQuestionId  =    null;
        $order             =    $pOrder;

        $data = array_filter([
            'section' => $section,
            'title' => $title,
            'subtitle' => $subtitle,
            'text' => $text,
            'type' => $type,
            'required' => $required,
            'parentQuestionId' => $parentQuestionId,
            'order' => $order
        ], static fn($v) => $v !== null);
        
        return TemplateQuestion::where('id', $questionId)->update($data);
    }

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
