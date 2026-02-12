<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use Illuminate\Http\Request;

class FacultyController extends Controller
{
    public function index(Request $request){
        $perPage = (int) $request->get('per_page', 10);
        $search = $request->get('search', '');


        $query = Faculty::select()->orderBy('created_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%") 
                ->orWhere('abrev','like', "%{$search}%");
            });
        }

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request){
        $data = $request->validate([
            'name' => 'required|string|max:250',
            'abrev' => 'sometimes|required|string|max:10',
        ]);

        $faculty = Faculty::create([
            'name' => $data['name'],
            'abrev' => $data['abrev'],
        ]);

        return response()->json($faculty, 201);
    }

    public function update(Request $request, $id){
        $user = Faculty::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:250',
            'abrev' => 'sometimes|required|string|max:10',
        ]);

        $arrToUpdate = [];

        if (array_key_exists('name', $data)) {
            $arrToUpdate['name'] = $data['name'];
        }

        if (array_key_exists('abrev', $data)) {
            $arrToUpdate['abrev'] = $data['abrev'];
        }

        if (empty($arrToUpdate)) {
            return response()->json([
                'message' => 'No hay campos para actualizar'
            ], 422);
        }

        $user->update($arrToUpdate);
        return response()->json($user);
    }
}
