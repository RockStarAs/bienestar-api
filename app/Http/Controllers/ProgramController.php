<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index(Request $request){
        $perPage = (int) $request->get('per_page', 10);
        $search = $request->get('search', '');

        $query = Program::with('faculty')->select()->orderBy('created_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%") 
                ->orWhereHas('faculty', function ($fq) use ($search) {
                    $fq->where('name', 'like', "%{$search}%")
                    ->orWhere('abrev','like',"%{$search}%");
                });
            });
        }

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request){
        $data = $request->validate([
            'faculty_id' => 'required|integer|exists:faculties,id',
            'name' => 'required|string|max:250',
        ]);

        $program = Program::create([
            'name' => $data['name'],
            'faculty_id' => $data['faculty_id'],
        ]);

        return response()->json($program, 201);
    }

    public function update(Request $request,$id){
        $program = Program::findOrFail($id);

        $data = $request->validate([
            'faculty_id' => 'sometimes|required|integer|exists:faculties,id',
            'name' => 'sometimes|required|string|max:250',
        ]);

        $arrToUpdate = [];

        if (array_key_exists('faculty_id', $data)) {
            $arrToUpdate['faculty_id'] = $data['faculty_id'];
        }

        if (array_key_exists('name', $data)) {
            $arrToUpdate['name'] = $data['name'];
        }

        if (empty($arrToUpdate)) {
            return response()->json([
                'message' => 'No hay campos para actualizar'
            ], 422);
        }

        $program->update($arrToUpdate);
        return response()->json($program);
    }
    
}
