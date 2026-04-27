<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectService;
use CatFramework\Core\Model\Segment;
use CatFramework\Core\Model\TranslationUnit;
use CatFramework\TranslationMemory\SqliteTranslationMemory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TmController extends Controller
{
    public function __construct(private readonly ProjectService $projects) {}

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

        $this->projects->ensureDirectoryExists($project);
        $tm      = $this->makeTm($project, $data['targetLang']);
        $source  = new Segment('query', [$data['text']]);
        $matches = $tm->lookup($source, $data['sourceLang'], $data['targetLang'], 0.5, $data['limit'] ?? 5);

        return response()->json([
            'data' => [
                'matches' => array_map(fn($m) => [
                    'score'      => round($m->score * 100),
                    'type'       => $m->type->value,
                    'sourceText' => $m->translationUnit->source->getPlainText(),
                    'targetText' => $m->translationUnit->target->getPlainText(),
                ], $matches),
            ],
        ]);
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

        $this->projects->ensureDirectoryExists($project);
        $tmxPath  = $request->file('tmxFile')->getRealPath();
        $tm       = $this->makeTm($project, $data['targetLang']);
        $imported = $tm->import($tmxPath);

        return response()->json(['data' => ['imported' => $imported]]);
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

        $this->projects->ensureDirectoryExists($project);

        $unit = new TranslationUnit(
            source:         new Segment('src', [$data['sourceText']]),
            target:         new Segment('tgt', [$data['targetText']]),
            sourceLanguage: $data['sourceLang'],
            targetLanguage: $data['targetLang'],
            createdAt:      new \DateTimeImmutable(),
        );

        $this->makeTm($project, $data['targetLang'])->store($unit);

        return response()->json(['data' => ['stored' => true]]);
    }

    private function makeTm(Project $project, string $targetLang): SqliteTranslationMemory
    {
        $path = $this->projects->tmPath($project, $targetLang);

        return new SqliteTranslationMemory(new \PDO("sqlite:{$path}"));
    }
}
