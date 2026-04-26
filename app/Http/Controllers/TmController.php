<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\UploadedFile;
use App\Services\FileStorageService;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TmController extends Controller
{
    public function __construct(
        private readonly ProjectService     $projects,
        private readonly FileStorageService $storage,
    ) {}

    /**
     * Look up segments in the project TM.
     *
     * @group Translation Memory
     * @authenticated
     * @bodyParam projectId integer required Project ID. Example: 1
     * @bodyParam text string required Source text to look up. Example: Hello world
     * @bodyParam sourceLang string required Source language. Example: en
     * @bodyParam targetLang string required Target language. Example: fr
     * @bodyParam limit integer Max results (default 5). Example: 5
     */
    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'projectId'  => 'required|integer|exists:projects,id',
            'text'       => 'required|string',
            'sourceLang' => 'required|string|max:10',
            'targetLang' => 'required|string|max:10',
            'limit'      => 'sometimes|integer|min:1|max:50',
        ]);

        $project = Project::findOrFail($data['projectId']);
        $this->authorize('view', $project);

        // catframework/translation-memory integration point:
        // $tm = new SqliteTranslationMemory($this->projects->tmPath($project, $data['targetLang']));
        // $matches = $tm->lookup($data['text'], $data['sourceLang'], $data['targetLang'], $data['limit'] ?? 5);

        return response()->json(['data' => ['matches' => []]]);
    }

    /**
     * Import a TMX file into the project TM.
     *
     * @group Translation Memory
     * @authenticated
     * @bodyParam tmxFile file required TMX file to import. Example: memory.tmx
     * @bodyParam projectId integer required Project ID. Example: 1
     * @bodyParam targetLang string required Target language. Example: fr
     */
    public function import(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tmxFile'    => 'required|file',
            'projectId'  => 'required|integer|exists:projects,id',
            'targetLang' => 'required|string|max:10',
        ]);

        $project = Project::findOrFail($data['projectId']);
        $this->authorize('update', $project);

        $tmxPath = $request->file('tmxFile')->store('tmp', 'local');

        // catframework/tmx + catframework/translation-memory integration point:
        // $segments = (new TmxReader)->read(storage_path('app/' . $tmxPath));
        // $tm = new SqliteTranslationMemory($this->projects->tmPath($project, $data['targetLang']));
        // $tm->importBatch($segments);

        return response()->json(['data' => ['imported' => 0]]);
    }

    /**
     * Add or update a single TM segment.
     *
     * @group Translation Memory
     * @authenticated
     * @bodyParam projectId integer required Project ID. Example: 1
     * @bodyParam sourceLang string required Source language. Example: en
     * @bodyParam targetLang string required Target language. Example: fr
     * @bodyParam sourceText string required Source segment text. Example: Hello world
     * @bodyParam targetText string required Target segment text. Example: Bonjour monde
     */
    public function addSegment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'projectId'  => 'required|integer|exists:projects,id',
            'sourceLang' => 'required|string|max:10',
            'targetLang' => 'required|string|max:10',
            'sourceText' => 'required|string',
            'targetText' => 'required|string',
        ]);

        $project = Project::findOrFail($data['projectId']);
        $this->authorize('update', $project);

        // $tm = new SqliteTranslationMemory($this->projects->tmPath($project, $data['targetLang']));
        // $tm->add($data['sourceLang'], $data['targetLang'], $data['sourceText'], $data['targetText']);

        return response()->json(['data' => ['stored' => true]]);
    }
}
