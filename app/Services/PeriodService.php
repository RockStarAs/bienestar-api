<?php

namespace App\Services;

use App\Models\Period;
use App\Models\Test;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PeriodService
{
    public function getAllPeriods(array $filters = []): Collection
    {
        Log::info('PeriodService: Buscando todos los periodos', ['filters' => $filters]);

        $query = Period::query()->withCount('tests');

        // Apply search filter if provided
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        $query->orderBy($sortBy, $sortDirection);

        $periods = $query->get();

        Log::info('PeriodService: Periodos obtenidos', ['count' => $periods->count()]);

        return $periods;
    }

    public function createPeriod(array $data): Period
    {
        Log::info('PeriodService: Creating new period', ['data' => $data]);

        try {
            DB::beginTransaction();

            // Check for duplicate name (case-insensitive)
            $existingPeriod = Period::whereRaw('LOWER(name) = ?', [strtolower($data['name'])])->first();
            
            if ($existingPeriod) {
                Log::warning('PeriodService: Intento de crear un periodo duplicado', [
                    'attempted_name' => $data['name'],
                    'existing_id' => $existingPeriod->id
                ]);
                throw new Exception("Ya existe un período con el nombre" . $data['name']);
            }

            // Validate date range if both dates are provided
            if (!empty($data['start_date']) && !empty($data['end_date'])) {
                $startDate = new \DateTime($data['start_date']);
                $endDate = new \DateTime($data['end_date']);
                
                if ($startDate >= $endDate) {
                    Log::warning('PeriodService: Fechas de rango del periodo inválidas', [
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date']
                    ]);
                    throw new Exception('La fecha de inicio debe ser anterior a la fecha de fin.');
                }
            }

            $period = Period::create($data);
            $period->loadCount('tests');

            DB::commit();

            Log::info('PeriodService: Periodo creado satisfactoriamente', [
                'period_id' => $period->id,
                'name' => $period->name
            ]);

            return $period;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('PeriodService: Falló en crear el periodo', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function updatePeriod(Period $period, array $data): Period
    {
        Log::info('PeriodService: Actualizando periodo', [
            'period_id' => $period->id,
            'data' => $data
        ]);

        try {
            DB::beginTransaction();

            // Check for duplicate name (excluding current period and only if name is actually changing)
            if (isset($data['name']) && strtolower($data['name']) !== strtolower($period->name)) {
                $existingPeriod = Period::whereRaw('LOWER(name) = ?', [strtolower($data['name'])])
                    ->where('id', '!=', $period->id)
                    ->first();
                
                if ($existingPeriod) {
                    Log::warning('PeriodService: Intento de duplicar un periodo existente', [
                        'period_id' => $period->id,
                        'attempted_name' => $data['name'],
                        'existing_id' => $existingPeriod->id
                    ]);
                    throw new Exception("Ya existe un período con el nombre" . $data['name']);
                }
            }

            // Validate date range only if dates are being updated
            if (isset($data['start_date']) || isset($data['end_date'])) {
                $startDate = $data['start_date'] ?? $period->start_date;
                $endDate = $data['end_date'] ?? $period->end_date;
                
                if ($startDate && $endDate) {
                    try {
                        $start = new \DateTime($startDate);
                        $end = new \DateTime($endDate);
                        
                        if ($start >= $end) {
                            Log::warning('PeriodService: Rango inválido en periodos', [
                                'period_id' => $period->id,
                                'start_date' => $startDate,
                                'end_date' => $endDate
                            ]);
                            throw new Exception('La fecha de inicio debe ser anterior a la fecha de fin.');
                        }
                    } catch (\Exception $dateException) {
                        // If it's not our validation exception, it's a date parsing error
                        if (strpos($dateException->getMessage(), 'fecha de inicio debe ser anterior') === false) {
                            Log::warning('PeriodService: Rango inválido en periodos', [
                                'period_id' => $period->id,
                                'start_date' => $startDate,
                                'end_date' => $endDate,
                                'error' => $dateException->getMessage()
                            ]);
                            // Don't throw for date parsing errors, let Laravel handle it
                        } else {
                            throw $dateException;
                        }
                    }
                }
            }

            $period->update($data);
            $period->loadCount('tests');

            DB::commit();

            Log::info('PeriodService: actualizado correctamente', [
                'period_id' => $period->id,
                'name' => $period->name
            ]);

            return $period;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('PeriodService: Falló en actualizar el periodo', [
                'period_id' => $period->id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deletePeriod(Period $period): bool
    {
        Log::info('PeriodService: Intento de borrar el periodo', ['period_id' => $period->id]);

        try {
            DB::beginTransaction();

            // Check for dependencies
            $dependencies = $this->checkPeriodDependencies($period);
            
            if ($dependencies['tests_count'] > 0) {
                $testsCount = $dependencies['tests_count'];
                Log::warning('PeriodService: No se puede borrar el periodo porque hay tests con este periodo', [
                    'period_id' => $period->id,
                    'tests_count' => $testsCount
                ]);
                throw new Exception("No se puede eliminar el período porque tiene {$testsCount} test(s) asociado(s).");
            }

            $periodName = $period->name;
            $period->delete();

            DB::commit();

            Log::info('PeriodService: Periodo borrado satisfactoriamente', [
                'period_id' => $period->id,
                'name' => $periodName
            ]);

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('PeriodService: Falló en borrar el periodo', [
                'period_id' => $period->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function checkPeriodDependencies(Period $period): array
    {
        Log::info('PeriodService: Verificando si el periodo tienes tests', ['period_id' => $period->id]);

        $testsCount = $period->tests()->count();

        $testNames = [];
        if ($testsCount > 0) {
            $testNames = $period->tests()
                ->limit(5) // Limit to first 5 for display
                ->pluck('title') // Use 'title' instead of 'name'
                ->toArray();
        }

        $dependencies = [
            'tests_count' => $testsCount,
            'test_names' => $testNames
        ];

        Log::info('PeriodService: Period dependencies checked', [
            'period_id' => $period->id,
            'tests_count' => $testsCount
        ]);

        return $dependencies;
    }

    public function getPeriodById(int $id): ?Period
    {
        Log::info('PeriodService: Siguiendo el periodo por ID', ['period_id' => $id]);

        $period = Period::withCount('tests')->find($id);

        if ($period) {
            Log::info('PeriodService: Periodo encontrado', ['period_id' => $id]);
        } else {
            Log::warning('PeriodService: Period no encontrado', ['period_id' => $id]);
        }

        return $period;
    }

    public function getPeriodsForSelect(): \Illuminate\Support\Collection
    {
        Log::info('PeriodService: Siguiendo periodo para el select');

        $periods = Period::select('id', 'name', 'description', 'start_date', 'end_date')
            ->orderBy('name')
            ->get()
            ->map(function ($period) {
                $label = $period->name;
                if ($period->start_date && $period->end_date) {
                    $label .= " ({$period->start_date->format('d/m/Y')} - {$period->end_date->format('d/m/Y')})";
                }
                
                return [
                    'value' => $period->id,
                    'label' => $label,
                    'description' => $period->description
                ];
            });

        Log::info('PeriodService: Retornar periodos para el select', ['count' => $periods->count()]);

        return $periods;
    }
}