<?php

namespace App\Http\Controllers;

use App\Models\UploadedFile;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExtractController extends Controller
{
    public function __construct(private readonly FileStorageService $storage) {}

    /**
     * Extract translatable segments from a source file.
     *
     * @group Processing
     * @authenticated
     * @bodyParam fileId integer required ID of the uploaded source file. Example: 3
     * @bodyParam sourceLang string required Source language. Example: en
     * @bodyParam targetLang string required Target language. Example: fr
     */
    public function extract(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fileId'     => 'required|integer|exists:uploaded_files,id',
            'sourceLang' => 'required|string|max:10',
            'targetLang' => 'required|string|max:10',
        ]);

        $file = UploadedFile::findOrFail($data['fileId']);
        $this->authorize('view', $file);

        $path = $this->storage->absolutePath($file);

        // catframework/filter-* integration point:
        // $registry = app(FileFilterRegistry::class);
        // $filter   = $registry->for($path, $file->mime_type);
        // $document = $filter->extract($path);
        // $segments = $document->segments();

        return response()->json(['data' => ['segments' => []]]);
    }
}
