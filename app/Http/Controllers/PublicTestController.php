<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Test;
use App\Models\TestAnswer;
use App\Models\TestAssignment;
use App\Models\TestTemplateVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicTestController extends Controller
{
    /**
     * Mostrar informacion del test
     */
    public function show($id)
    {
        $test = Test::with('templateVersion')->findOrFail($id);

        if ($test->status !== Test::STATUS_ACTIVE) {
            return response()->json(['message' => 'Este test no está activo.'], 403);
        }

        return response()->json([
            'id' => $test->id,
            'title' => $test->title,
            'period' => $test->period,
            'description' => $test->templateVersion->template->description ?? '', // Assuming template has description, or just title
        ]);
    }

    /**
     * Inicia el test desde 0 (o recupera lo guardado hasta el momento (autosave)).
     * 1. Busca o crea Student por DNI.
     * 2. Busca o crea Assignment.
     * 3. Retorna las preguntas.
     */
    public function start(Request $request, $id)
    {
        $request->validate([
            'dni' => ['required', 'string', 'regex:/^\d+$/', 'digits:8'],
            'name' => 'required|string|max:150',
            'email' => 'nullable|email|max:150',
            'program.id' => 'required|integer|exists:programs,id',
        ]);

        $test = Test::findOrFail($id);
        
        if ($test->status !== Test::STATUS_ACTIVE) {
            return response()->json(['message' => 'Este test no está activo.'], 403);
        }

        // 1. Busca o crea Student por DNI
        $student = Student::firstOrCreate(
            ['dni' => $request->dni],
            [
                'name' => $request->name,
                'email' => $request->email,
                'program_id' => $request->program['id']
            ]
        );
        
        // Actualiza la informacion del estudiante si hubo cambios
        $student->update([
            // 'name' => $request->name, //ya no actualizar el nombre
            'email' => $request->email,
            // 'program' => $request->program
        ]);

        // 2. Encuentra o crea Assignment
        $assignment = TestAssignment::firstOrCreate(
            [
                'test_id' => $test->id,
                'student_id' => $student->id
            ],
            [
                'status' => 'pending',
                'started_at' => now(),
            ]
        );

        if ($assignment->status === 'completed') {
            return response()->json(['message' => 'Ya has completado este test.'], 403);
        }

        // 3. Obtiene las preguntas del Template Version
        // Necesitamos la estructura [section -> questions] o lista plana
        $version = TestTemplateVersion::with(['questions.options'])->findOrFail($test->template_version_id);
        
        $questions = $version->questions->filter(function($el){
            return $el->parent_question_id == null;
        })
        ->values()
        ->map(function($q) {
            return [
                'id' => $q->id,
                'text' => $q->text,
                'type' => $q->type,
                'section' => $q->section,
                'parent_question_id' => $q->parent_question_id,
                'order' => $q->order,
                'required' => $q->required,
                'options' => $q->options->map(function($opt) {
                    return [
                        'id' => $opt->id,
                        'label' => $opt->label,
                        'value' => $opt->value,
                        'order' => $opt->order
                    ];
                }),
                'children' => $q->children->map(function($chQ){
                    return [
                        'id' => $chQ->id,
                        'text' => $chQ->text,
                        'type' => $chQ->type,
                        'section' => $chQ->section,
                        'order' => $chQ->order,
                        'required' => $chQ->required,
                    ];
                })
            ];
        });

        return response()->json([
            'assignment_id' => $assignment->id,
            'student' => $student,
            'questions' => $questions
        ]);
    }

    /**
     * Envía las respuestas.
     */
    public function submit(Request $request, $assignmentId)
    {
        $assignment = TestAssignment::findOrFail($assignmentId);

        if ($assignment->status === 'completed') {
            return response()->json(['message' => 'Este examen ya fue enviado.'], 409);
        }

        $data = $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:template_questions,id',
            'answers.*.option_id' => 'nullable|exists:template_question_options,id',
            'answers.*.text_value' => 'nullable|string',
        ]);

        DB::transaction(function () use ($assignment, $data) {
            // Eliminar respuestas previas si las hubiera (para evitar duplicados si reenvían)
            // Ojo: Si quisiéramos autosave, la lógica sería updateOrCreate.
            TestAnswer::where('test_assignment_id', $assignment->id)->delete();

            foreach ($data['answers'] as $ans) {
                TestAnswer::create([
                    'test_assignment_id' => $assignment->id,
                    'question_id' => $ans['question_id'],
                    'option_id' => $ans['option_id'] ?? null,
                    'text_value' => $ans['text_value'] ?? null,
                ]);
            }

            $assignment->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);
        });

        return response()->json(['message' => 'Test enviado correctamente.']);
    }
}
