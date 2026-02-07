<?php

namespace App\Http\Controllers;

use App\Models\Test;
use Illuminate\Http\Request;

class TestController extends Controller
{
    /**
     * Listar todos los tests (instancias).
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $status = $request->get('status');

        $query = Test::with(['templateVersion.template', 'creator'])
            ->orderByDesc('id');

        if ($status) {
            $query->where('status', $status);
        }

        return response()->json($query->paginate($perPage));
    }

    /**
     * Crear un nuevo test (instancia/periodo).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'template_version_id' => 'required|exists:test_template_versions,id',
            'title' => 'required|string|max:200',
            'period_id' => 'required|exists:periods,id',
            // 'status' => 'in:active,closed',
        ]);

        $test = Test::create([
            'template_version_id' => $data['template_version_id'],
            'title' => $data['title'],
            'period_id' => $data['period_id'],
            'status' => Test::STATUS_ACTIVE,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($test, 201);
    }

    /**
     * Mostrar detalles de un test.
     */
    public function show($id)
    {
        $test = Test::with(['templateVersion.template', 'creator'])->findOrFail($id);
        
        // Agregar URL pública generada (útil para el frontend)
        // Suponiendo que el frontend corre en el dominio principal y el path es /t/{id}
        // Ojo: Esto es una URL ejemplo, se ajustará según tu routing frontend real.
        $test->public_link = url("/#/public/test/{$test->id}"); 

        return response()->json($test);
    }

    /**
     * Actualizar test.
     */
    public function update(Request $request, $id)
    {
        $test = Test::findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|string|max:200',
            'status' => 'in:active,closed',
        ]);

        $test->update($data);

        return response()->json($test);
    }

    /**
     * Eliminar test (si no tiene respuestas asociadas podría ser soft delete o restrict).
     */
    public function destroy($id)
    {
        $test = Test::findOrFail($id);
        
        // La restricción de FK ('restrict') en la migración evitará borrar
        // si ya tiene assignments, lo cual es correcto.
        try {
            $test->delete();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'No se puede eliminar el test porque ya tiene alumnos o respuestas asociadas.'
            ], 409);
        }

        return response()->json(['message' => 'Test eliminado']);
    }
}
