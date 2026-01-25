<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTemplateRequest;
use App\Models\TestTemplate;
use Illuminate\Http\Request;

class TestTemplateController extends Controller
{
    public function index(Request $request){
        $q = TestTemplate::query()->orderByDesc('id');

        //filtro simple
        if ($request->filled('search')) {
            $search = $request->get('search');
            $q->where('name', 'like', "%{$search}%");
        }

        // paginado opcional (default 10)
        $perPage = (int) ($request->get('per_page', 10));
        return response()->json($q->paginate($perPage));
    }

    public function show($id, Request $request){
        $includeVersions = filter_var($request->get('include_versions', false), FILTER_VALIDATE_BOOLEAN);

        $q = TestTemplate::query()->whereKey($id);

        if ($includeVersions) {
            $q->with(['versions' => function ($q) {
                $q->orderByDesc('id');
            }]);
        }

        $template = $q->firstOrFail();
        return response()->json($template);
    }

    public function update(UpdateTemplateRequest $request, $id){
        $template = TestTemplate::findOrFail($id);

        $template->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json($template);
    }

    public function destroy($id){
        // MVP: deshabilitar borrado
        return response()->json(['message' => 'Metodo no permitido MVP'], 405);

        // $template = TestTemplate::findOrFail($id);
        // $template->delete();
        // return response()->json(['message' => 'Deleted']);
    }
}
