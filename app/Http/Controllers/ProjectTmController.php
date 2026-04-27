<?php

namespace App\Http\Controllers;

use App\Models\Project;
use CatFramework\Core\Model\Segment;
use CatFramework\Core\Model\TranslationUnit;
use CatFramework\TranslationMemory\PostgresTranslationMemory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectTmController extends Controller
{
    /**
     * TM statistics for this project.
     *
     * @group Translation Memory
     * @authenticated
     * @urlParam project integer required Project ID. Example: 1
     * @queryParam targetLang string required Target language. Example: fr
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $data = $request->validate(['targetLang' => 'required|string|max:10']);

        abort_unless(config('database.default') === 'pgsql', 503, 'Postgres TM requires DB_CONNECTION=pgsql.');

        $count = DB::scalar(
            'SELECT COUNT(*) FROM tm_units WHERE tm_id = ?',
            [$this->tmId($project, $data['targetLang'])],
        );

        return response()->json([
            'data' => [
                'tmId'       => $this->tmId($project, $data['targetLang']),
                'entryCount' => (int) $count,
            ],
        ]);
    }

    /**
     * Fuzzy TM lookup using pg_trgm.
     *
     * @group Translation Memory
     * @authenticated
     * @urlParam project integer required Project ID. Example: 1
     * @bodyParam text string required Source text to look up. Example: Hello world
     * @bodyParam sourceLang string required Source language. Example: en
     * @bodyParam targetLang string required Target language. Example: fr
     * @bodyParam minScore float Minimum match score 0–1 (default 0.5). Example: 0.7
     * @bodyParam limit integer Max results (default 5, max 50). Example: 5
     */
    public function lookup(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $data = $request->validate([
            'text'       => 'required|string',
            'sourceLang' => 'required|string|max:10',
            'targetLang' => 'required|string|max:10',
            'minScore'   => 'sometimes|numeric|min:0|max:1',
            'limit'      => 'sometimes|integer|min:1|max:50',
        ]);

        abort_unless(config('database.default') === 'pgsql', 503, 'Postgres TM requires DB_CONNECTION=pgsql.');

        $tm      = $this->makeTm($project, $data['targetLang'], $data['minScore'] ?? 0.5);
        $source  = new Segment('query', [$data['text']]);
        $matches = $tm->lookup(
            $source,
            $data['sourceLang'],
            $data['targetLang'],
            $data['minScore'] ?? 0.5,
            $data['limit'] ?? 5,
        );

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
     * Import a TMX file into the project's Postgres TM.
     *
     * @group Translation Memory
     * @authenticated
     * @urlParam project integer required Project ID. Example: 1
     * @bodyParam tmxFile file required TMX file. Example: memory.tmx
     * @bodyParam targetLang string required Target language. Example: fr
     */
    public function import(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'tmxFile'    => 'required|file',
            'targetLang' => 'required|string|max:10',
        ]);

        abort_unless(config('database.default') === 'pgsql', 503, 'Postgres TM requires DB_CONNECTION=pgsql.');

        $tm       = $this->makeTm($project, $data['targetLang']);
        $imported = $tm->import($request->file('tmxFile')->getRealPath());

        return response()->json(['data' => ['imported' => $imported]]);
    }

    /**
     * Add or update a single TM entry.
     *
     * @group Translation Memory
     * @authenticated
     * @urlParam project integer required Project ID. Example: 1
     * @bodyParam sourceLang string required. Example: en
     * @bodyParam targetLang string required. Example: fr
     * @bodyParam sourceText string required. Example: Hello world
     * @bodyParam targetText string required. Example: Bonjour monde
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'sourceLang' => 'required|string|max:10',
            'targetLang' => 'required|string|max:10',
            'sourceText' => 'required|string',
            'targetText' => 'required|string',
        ]);

        abort_unless(config('database.default') === 'pgsql', 503, 'Postgres TM requires DB_CONNECTION=pgsql.');

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

    private function makeTm(Project $project, string $targetLang, float $minSimilarity = 0.5): PostgresTranslationMemory
    {
        return new PostgresTranslationMemory(
            pdo:           DB::getPdo(),
            tmId:          $this->tmId($project, $targetLang),
            minSimilarity: $minSimilarity,
        );
    }

    private function tmId(Project $project, string $targetLang): string
    {
        return "project-{$project->id}-{$targetLang}";
    }
}
