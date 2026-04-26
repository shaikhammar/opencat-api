<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessFileJob;
use App\Models\ProcessingJob;
use App\Models\Project;
use App\Models\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProcessController extends Controller
{
    /**
     * Run the full workflow on a file.
     *
     * Files under ASYNC_THRESHOLD_MB are processed synchronously (HTTP 200).
     * Larger files return HTTP 202 with a jobId to poll.
     *
     * @group Processing
     * @authenticated
     * @urlParam project integer required Project ID. Example: 1
     * @bodyParam fileId integer required ID of the uploaded file. Example: 5
     * @bodyParam targetLang string required Target language code. Example: fr
     * @bodyParam options object optional Workflow option overrides.
     */
    public function process(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $data = $request->validate([
            'fileId'     => 'required|integer|exists:uploaded_files,id',
            'targetLang' => 'required|string|max:10',
            'options'    => 'sometimes|array',
        ]);

        $file        = UploadedFile::findOrFail($data['fileId']);
        $thresholdMb = config('catframework.async_threshold_mb', 5);
        $isAsync     = $file->size_bytes > ($thresholdMb * 1024 * 1024);

        $processingJob = ProcessingJob::create([
            'user_id' => $request->user()->id,
            'status'  => 'pending',
            'progress'=> 0,
        ]);

        if ($isAsync) {
            ProcessFileJob::dispatch(
                $processingJob->id,
                $project->id,
                $file->id,
                $data['targetLang'],
                $data['options'] ?? [],
            );

            return response()->json([
                'data' => [
                    'jobId'     => $processingJob->id,
                    'statusUrl' => "/api/jobs/{$processingJob->id}",
                ],
            ], 202);
        }

        // Synchronous path — dispatch synchronously
        ProcessFileJob::dispatchSync(
            $processingJob->id,
            $project->id,
            $file->id,
            $data['targetLang'],
            $data['options'] ?? [],
        );

        $processingJob->refresh();

        return response()->json(['data' => $processingJob->result]);
    }
}
