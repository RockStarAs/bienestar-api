<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePeriodRequest;
use App\Http\Requests\UpdatePeriodRequest;
use App\Models\Period;
use App\Services\PeriodService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PeriodController extends Controller
{
    protected PeriodService $periodService;

    public function __construct(PeriodService $periodService)
    {
        $this->periodService = $periodService;
    }
    /**
     * Retornar el listado de periodos con paginación
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 15);
            $search = $request->get('search');

            $query = Period::query()
                ->withCount('tests')
                ->orderBy('name');

            // En caso exista para buscar un periodo (pero no creo)
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $periods = $query->paginate($perPage);

            return response()->json($periods);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los períodos.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo periodo
     *
     * @param StorePeriodRequest $request
     * @return JsonResponse
     */
    public function store(StorePeriodRequest $request): JsonResponse
    {
        try {
            $period = $this->periodService->createPeriod($request->validated());

            return response()->json($period, 201);
        } catch (\Exception $e) {
            // Check if it's a validation error (duplicate name or date range)
            if (strpos($e->getMessage(), 'Ya existe un período') !== false || 
                strpos($e->getMessage(), 'fecha de inicio debe ser anterior') !== false) {
                return response()->json([
                    'message' => $e->getMessage()
                ], 422);
            }
            
            return response()->json([
                'message' => 'Error al crear el período.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar un nuevo periodo
     *
     * @param Period $period
     * @return JsonResponse
     */
    public function show(Period $period): JsonResponse
    {
        try {
            // Cargar la cantidad de tests que tiene el periodo
            $period->loadCount('tests');
            
            return response()->json($period);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el período.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un periodo
     *
     * @param UpdatePeriodRequest $request
     * @param Period $period
     * @return JsonResponse
     */
    public function update(UpdatePeriodRequest $request, Period $period): JsonResponse
    {
        try {
            $updatedPeriod = $this->periodService->updatePeriod($period, $request->validated());

            return response()->json($updatedPeriod);
        } catch (\Exception $e) {
            // Par verificar si hay errores al insertar
            if (strpos($e->getMessage(), 'Ya existe otro período') !== false || 
                strpos($e->getMessage(), 'fecha de inicio debe ser anterior') !== false) {
                return response()->json([
                    'message' => $e->getMessage()
                ], 422);
            }
            
            return response()->json([
                'message' => 'Error al actualizar el período.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Antes de eliminar, verificar si tiene tests para eliminar
     *
     * @param Period $period
     * @return JsonResponse
     */
    public function dependencies(Period $period): JsonResponse
    {
        try {
            $dependencies = $this->periodService->checkPeriodDependencies($period);

            return response()->json([
                'can_delete' => $dependencies['tests_count'] === 0,
                'dependencies' => $dependencies,
                'message' => $dependencies['tests_count'] > 0 
                    ? "Este período tiene " . $dependencies['tests_count'] . " test(s) asociado(s)."
                    : 'Este período puede eliminarse de forma segura.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al verificar dependencias del período.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un periodo (solo aplica softdelete)
     *
     * @param Period $period
     * @return JsonResponse
     */
    public function destroy(Period $period): JsonResponse
    {
        try {
            $this->periodService->deletePeriod($period);

            return response()->json([
                'message' => 'Período eliminado correctamente.'
            ]);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'test(s) asociado(s)') !== false) {
                return response()->json([
                    'message' => $e->getMessage()
                ], 409);
            }
            
            return response()->json([
                'message' => 'Error al eliminar el período.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}