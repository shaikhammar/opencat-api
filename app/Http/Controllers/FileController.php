<?php

namespace App\Http\Controllers;

use App\Http\Resources\UploadedFileResource;
use App\Models\UploadedFile;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function __construct(private readonly FileStorageService $storage) {}

    /**
     * Upload a file.
     *
     * @group Files
     * @authenticated
     * @bodyParam file file required The file to upload (multipart/form-data). Example: document.docx
     */
    public function store(Request $request): JsonResponse
    {
        $maxMb = config('catframework.max_upload_mb', 50);

        $request->validate([
            'file' => "required|file|max:" . ($maxMb * 1024),
        ]);

        $uploaded = $this->storage->store($request->file('file'), $request->user()->id);

        return response()->json(['data' => new UploadedFileResource($uploaded)], 201);
    }

    /**
     * Get file metadata.
     *
     * @group Files
     * @authenticated
     */
    public function show(Request $request, UploadedFile $uploadedFile): JsonResponse
    {
        $this->authorize('view', $uploadedFile);

        return response()->json(['data' => new UploadedFileResource($uploadedFile)]);
    }

    /**
     * Download a file.
     *
     * @group Files
     * @authenticated
     */
    public function download(Request $request, UploadedFile $uploadedFile): Response
    {
        $this->authorize('view', $uploadedFile);

        $path = $this->storage->absolutePath($uploadedFile);

        return response()->download($path, $uploadedFile->original_name);
    }

    /**
     * Delete a file.
     *
     * @group Files
     * @authenticated
     */
    public function destroy(Request $request, UploadedFile $uploadedFile): JsonResponse
    {
        $this->authorize('delete', $uploadedFile);
        $this->storage->delete($uploadedFile);

        return response()->json(null, 204);
    }
}
