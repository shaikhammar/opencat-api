<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\JsonResponse;

class ProjectFileController extends Controller
{
    /**
     * List all processed files for a project.
     *
     * @group Files
     * @authenticated
     * @urlParam project integer required Project ID. Example: 1
     */
    public function index(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $files = ProjectFile::where('project_id', $project->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $files->map(fn(ProjectFile $f) => [
                'id'             => $f->id,
                'uploadedFileId' => $f->uploaded_file_id,
                'targetLang'     => $f->target_lang,
                'originalName'   => $f->original_name,
                'mimeType'       => $f->mime_type,
                'segmentCount'   => $f->segment_count,
                'createdAt'      => $f->created_at?->toAtomString(),
            ]),
        ]);
    }
}
