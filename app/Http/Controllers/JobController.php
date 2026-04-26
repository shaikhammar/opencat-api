<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProcessingJobResource;
use App\Models\ProcessingJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{
    /**
     * Poll async job status.
     *
     * @group Jobs
     * @authenticated
     */
    public function show(Request $request, ProcessingJob $processingJob): JsonResponse
    {
        $this->authorize('view', $processingJob);

        return response()->json(['data' => new ProcessingJobResource($processingJob)]);
    }

    /**
     * Cancel a pending or processing job.
     *
     * @group Jobs
     * @authenticated
     */
    public function destroy(Request $request, ProcessingJob $processingJob): JsonResponse
    {
        $this->authorize('delete', $processingJob);

        if (! $processingJob->isCancellable()) {
            return response()->json([
                'type'   => 'https://catframework-api.dev/errors/not-cancellable',
                'title'  => 'Job cannot be cancelled',
                'status' => 422,
                'detail' => "Job is already in status '{$processingJob->status}'.",
            ], 422);
        }

        $processingJob->update(['status' => 'cancelled']);

        return response()->json(null, 204);
    }
}
