<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MtController extends Controller
{
    public function __construct(private readonly ProjectService $projects) {}

    /**
     * Machine-translate one or more segments.
     *
     * @group Machine Translation
     * @authenticated
     * @bodyParam projectId integer required Project ID (used to pick MT adapter config). Example: 1
     * @bodyParam segments object[] required Segments to translate. Example: [{"sourceText":"Hello"}]
     * @bodyParam sourceLang string required Source language. Example: en
     * @bodyParam targetLang string required Target language. Example: fr
     */
    public function translate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'projectId'           => 'required|integer|exists:projects,id',
            'segments'            => 'required|array|min:1',
            'segments.*.sourceText' => 'required|string',
            'sourceLang'          => 'required|string|max:10',
            'targetLang'          => 'required|string|max:10',
        ]);

        $project = Project::findOrFail($data['projectId']);
        $this->authorize('view', $project);

        // catframework/mt integration point:
        // $adapter = MtAdapterFactory::fromManifest($project->manifest ?? []);
        // $translations = $adapter->translateBatch($data['segments'], $data['sourceLang'], $data['targetLang']);

        return response()->json(['data' => ['translations' => []]]);
    }
}
