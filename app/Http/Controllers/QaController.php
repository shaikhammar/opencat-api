<?php

namespace App\Http\Controllers;

use App\Models\UploadedFile;
use App\Services\FileStorageService;
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
            'fileId' => 'required|integer|exists:uploaded_files,id',
            'checks' => 'sometimes|array',
            'checks.*' => 'string',
        ]);

        $file = UploadedFile::findOrFail($data['fileId']);
        $this->authorize('view', $file);

        $path = $this->storage->absolutePath($file);

        // catframework/qa integration point:
        // $xliff    = (new XliffReader)->read($path);
        // $runner   = new QualityRunner($data['checks'] ?? null);
        // $issues   = $runner->run($xliff);

        return response()->json(['data' => ['issues' => []]]);
    }
}
