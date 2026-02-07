<?php

namespace App\Http\Controllers;

use App\Models\Period;
use Illuminate\Http\Request;
use App\Models\TestAssignment;
use App\Models\Test;
use App\Models\TestTemplateVersion;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResultsController extends Controller
{
    /**
     * Obtener opciones de filtros disponibles
     */
    public function filters()
    {
        // Tests activos con sus versiones
        $tests = Test::with(['templateVersion.template','period'])
            ->where('status', 'active')
            ->orWhere('status', 'closed')
            ->get()
            ->map(function ($test) {
                return [
                    'id' => $test->id,
                    'label' => $test->templateVersion->template->name . ' - ' . $test->period->name,
                    'period_id' => $test->period->id,
                    'version_id' => $test->template_version_id,
                    'version_name' => 'v' . $test->templateVersion->version,
                    'template_name' => $test->templateVersion->template->name,
                ];
            });

        // Periodos únicos
        $periods = Period::query()
            ->whereHas('tests')
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->values();

        // Versiones con plantillas
        $versions = TestTemplateVersion::with('template')
            ->get()
            ->map(function ($version) {
                return [
                    'id' => $version->id,
                    'label' => $version->template->name . ' v' . $version->version,
                    'template_id' => $version->template_id,
                    'template_name' => $version->template->name,
                    'version' => $version->version,
                ];
            });

        return response()->json([
            'tests' => $tests,
            'periods' => $periods,
            'versions' => $versions,
        ]);
    }

    /**
     * Listar resultados con filtros
     */
    public function index(Request $request)
    {
        $query = TestAssignment::with([
            'student',
            'test.templateVersion.template',
            'answers.question',
            'answers.option'
        ])->where('status', 'completed');

        // Filtrar por test específico
        if ($request->has('test_id') && $request->test_id) {
            $query->where('test_id', $request->test_id);
        }

        // Filtrar por versión
        if ($request->has('version_id') && $request->version_id) {
            $query->whereHas('test', function ($q) use ($request) {
                $q->where('template_version_id', $request->version_id);
            });
        }

        // Filtrar por periodo
        if ($request->has('period_id') && $request->period_id) {
            $query->whereHas('test', function ($q) use ($request) {
                $q->where('period_id', $request->period_id);
            });
        }

        // Búsqueda por DNI o nombre
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('dni', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Paginación
        $perPage = $request->get('per_page', 15);
        $results = $query->orderBy('completed_at', 'desc')
            ->paginate($perPage);

        // Transformar datos
        $results->getCollection()->transform(function ($assignment) {
            return [
                'id' => $assignment->id,
                'student' => [
                    'dni' => $assignment->student->dni,
                    'name' => $assignment->student->name,
                    'email' => $assignment->student->email,
                    'program' => $assignment->student->program,
                ],
                'test' => [
                    'id' => $assignment->test->id,
                    'period' => $assignment->test->period,
                    'template_name' => $assignment->test->templateVersion->template->name,
                    'version' => $assignment->test->templateVersion->version,
                ],
                'completed_at' => $assignment->completed_at,
                'answers_count' => $assignment->answers->count(),
            ];
        });

        return response()->json($results);
    }

    /**
     * Exportar resultados a Excel
     */
    public function export(Request $request)
    {
        // Obtener assignments filtrados (sin paginar)
        $query = TestAssignment::with([
            'student',
            'test.templateVersion.template',
            'test.templateVersion.questions.options',
            'answers.question',
            'answers.option'
        ])->where('status', 'completed');

        // Aplicar los mismos filtros que index()
        if ($request->has('test_id') && $request->test_id) {
            $query->where('test_id', $request->test_id);
        }

        if ($request->has('version_id') && $request->version_id) {
            $query->whereHas('test', function ($q) use ($request) {
                $q->where('template_version_id', $request->version_id);
            });
        }

        if ($request->has('period_id') && $request->period_id) {
            $query->whereHas('test', function ($q) use ($request) {
                $q->where('period_id', $request->period_id);
            });
        }

        $assignments = $query->orderBy('completed_at', 'desc')->get();

        if ($assignments->isEmpty()) {
            return response()->json(['message' => 'No hay resultados para exportar'], 404);
        }

        // Obtener todas las preguntas de la versión (asumiendo mismo test/version)
        $firstAssignment = $assignments->first();
        $questions = $firstAssignment->test->templateVersion->questions->sortBy('order');

        // Crear spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resultados');

        // Cabeceras fijas
        $headers = ['DNI', 'Nombre', 'Email', 'Programa', 'Periodo', 'Fecha'];
        
        // Agregar preguntas como cabeceras
        foreach ($questions as $question) {
            $headers[] = $question->text;
        }

        // Escribir cabeceras
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col, 1, $header);
            $col++;
        }

        // Estilo de cabeceras
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0']
            ]
        ];
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);

        // Escribir datos
        $row = 2;
        foreach ($assignments as $assignment) {
            $col = 1;
            
            // Datos del estudiante
            $sheet->setCellValueByColumnAndRow($col++, $row, $assignment->student->dni);
            $sheet->setCellValueByColumnAndRow($col++, $row, $assignment->student->name);
            $sheet->setCellValueByColumnAndRow($col++, $row, $assignment->student->email ?? '');
            $sheet->setCellValueByColumnAndRow($col++, $row, $assignment->student->program ?? '');
            $sheet->setCellValueByColumnAndRow($col++, $row, $assignment->test->period);
            $sheet->setCellValueByColumnAndRow($col++, $row, $assignment->completed_at ? 
                $assignment->completed_at->format('Y-m-d H:i') : '');

            // Respuestas por pregunta
            foreach ($questions as $question) {
                $answer = $assignment->answers->where('question_id', $question->id)->first();
                $value = '';
                
                if ($answer) {
                    if ($answer->text_value) {
                        $value = $answer->text_value;
                    } elseif ($answer->option) {
                        $value = $answer->option->label ?? $answer->option->text ?? '';
                    }
                }
                
                // Para múltiples respuestas a la misma pregunta (checkbox)
                $multipleAnswers = $assignment->answers->where('question_id', $question->id);
                if ($multipleAnswers->count() > 1) {
                    $values = $multipleAnswers->map(function ($a) {
                        return $a->option->label ?? $a->option->text ?? $a->text_value ?? '';
                    })->filter()->join(', ');
                    $value = $values;
                }
                
                $sheet->setCellValueByColumnAndRow($col++, $row, $value);
            }
            
            $row++;
        }

        // Auto-ajustar columnas
        foreach (range('A', $sheet->getHighestColumn()) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Generar archivo
        $filename = 'resultados_' . date('Y-m-d_His') . '.xlsx';
        
        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
