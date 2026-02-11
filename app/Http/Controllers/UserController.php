<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request){
        $perPage = (int) $request->get('per_page', 10);

        $query = User::select()->orderBy('created_at');

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request){
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'password' => 'sometimes|required|string',
            'username' => 'required|max:50', 
            'role' => ['required',Rule::in(User::TYPES)],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'username' => $data['username'],
            'role' => $data['role'],
        ]);

        return response()->json($user, 201);
    }

    public function update(Request $request, $id){
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'email' => 'sometimes|required|email|max:150',
            'password' => 'sometimes|required|string',
            'username' => 'sometimes|required|max:50', 
        ]);

        $arrToUpdate = [];

        if (array_key_exists('name', $data)) {
            $arrToUpdate['name'] = $data['name'];
        }

        if (array_key_exists('email', $data)) {
            $arrToUpdate['email'] = $data['email'];
        }

        if (array_key_exists('username', $data)) {
            $arrToUpdate['username'] = $data['username'];
        }

        if (array_key_exists('password', $data)) {
            $arrToUpdate['password'] = Hash::make($data['password']);
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
