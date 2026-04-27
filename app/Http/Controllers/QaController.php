<?php

namespace App\Http\Controllers;

use App\Models\UploadedFile;
use App\Services\FileStorageService;
use CatFramework\Qa\Check\DoubleSpaceCheck;
use CatFramework\Qa\Check\EmptyTranslationCheck;
use CatFramework\Qa\Check\NumberConsistencyCheck;
use CatFramework\Qa\Check\TagConsistencyCheck;
use CatFramework\Qa\Check\WhitespaceCheck;
use CatFramework\Qa\QualityRunner;
use CatFramework\Xliff\XliffReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QaController extends Controller
{
    public function __construct(private readonly FileStorageService $storage) {}

    /**
     * Run QA checks on a bilingual XLIFF file.
     *
     * @group Quality Assurance
     * @authenticated
     * @bodyParam fileId integer required ID of the XLIFF file to check. Example: 7
     * @bodyParam checks string[] Optional list of check names to run. Runs all checks if omitted.
     */
    public function run(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fileId'    => 'required|integer|exists:uploaded_files,id',
            'checks'    => 'sometimes|array',
            'checks.*'  => 'string',
        ]);

        $file = UploadedFile::findOrFail($data['fileId']);
        $this->authorize('view', $file);

        $path = $this->storage->absolutePath($file);

        $doc    = (new XliffReader())->read($path);
        $runner = $this->buildRunner($data['checks'] ?? []);
        $issues = $runner->run($doc);

        return response()->json([
            'data' => [
                'issues' => array_map(fn($issue) => [
                    'checkId'   => $issue->checkId,
                    'severity'  => $issue->severity->value,
                    'message'   => $issue->message,
                    'segmentId' => $issue->segmentId,
                ], $issues),
            ],
        ]);
    }

    private function buildRunner(array $filter): QualityRunner
    {
        $all = [
            new EmptyTranslationCheck(),
            new WhitespaceCheck(),
            new DoubleSpaceCheck(),
            new NumberConsistencyCheck(),
            new TagConsistencyCheck(),
        ];

        $runner = new QualityRunner();
        foreach ($all as $check) {
            if ($filter === [] || in_array($check->getId(), $filter, true)) {
                $runner->register($check);
            }
        }

        return $runner;
    }
}
