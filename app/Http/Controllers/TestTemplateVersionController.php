<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTemplateVersionRequest;
use App\Http\Requests\UpdateTemplateVersionRequest;
use App\Models\TestTemplate;
use App\Models\TestTemplateVersion;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class TestTemplateVersionController extends Controller
{
    public function indexByTemplate($templateId,Request $request){
        $template = TestTemplate::findOrFail($templateId);

        $perPage = (int) ($request->get('per_page', 10));

        $versions = $template->versions()
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json($versions);
    }

    public function storeForTemplate(StoreTemplateVersionRequest $request, $templateId){
        $template = TestTemplate::findOrFail($templateId);

        try {
            $version = TestTemplateVersion::create([
                'template_id' => $template->id,
                'version' => $request->version,
                'status' => 'draft',
                'published_at' => null,
                'created_by' => $request->user()->id,
            ]);
        } catch (QueryException $e) {
            // por unique(template_id, version)
            return response()->json([
                'message' => 'La versión ya existe para esta plantilla.'
            ], 422);
        }

        return response()->json($version, 201);
    }

    public function update(UpdateTemplateVersionRequest $request, $versionId){
        $version = TestTemplateVersion::findOrFail($versionId);

        if ($version->status !== TestTemplateVersion::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Solo se puede editar una versión en estado draft.'
            ], 409);
        }

        try {
            $version->update([
                'version' => $request->version,
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'La versión ya existe para esta plantilla.'
            ], 422);
        }

        return response()->json($version);
    }

    public function publish($versionId)
    {
        $version = TestTemplateVersion::findOrFail($versionId);

        if ($version->status !== TestTemplateVersion::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Solo se puede publicar una versión en estado draft.'
            ], 409);
        }

        $version->status = 'published';
        $version->published_at = now();
        $version->save();

        return response()->json([
            'message' => 'Versión publicada correctamente.',
            'version' => $version
        ]);
    }
}
