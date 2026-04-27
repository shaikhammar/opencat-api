<?php

namespace App\Http\Controllers;

use App\Models\Project;
use CatFramework\Core\Enum\SegmentStatus;
use CatFramework\Project\Store\PostgresSegmentStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectSegmentController extends Controller
{
    /**
     * List segments for a processed file.
     *
     * @group Segments
     * @authenticated
     * @urlParam project integer required Project ID. Example: 1
     * @queryParam fileId string required The storeFileId returned by the processing job. Example: 550e8400-e29b-41d4-a716-446655440000
     * @queryParam status string Filter by status (untranslated|draft|translated|reviewed|approved|rejected).
     * @queryParam limit integer Page size (default 100, max 500). Example: 50
     * @queryParam offset integer Offset for pagination. Example: 0
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $data = $request->validate([
            'fileId' => 'required|string',
            'status' => 'sometimes|string|in:untranslated,draft,translated,reviewed,approved,rejected',
            'limit'  => 'sometimes|integer|min:1|max:500',
            'offset' => 'sometimes|integer|min:0',
        ]);

        $store    = $this->makeStore($project);
        $status   = isset($data['status']) ? SegmentStatus::from($data['status']) : null;
        $segments = $store->getSegments($data['fileId'], $status, $data['limit'] ?? 100, $data['offset'] ?? 0);
        $total    = $store->countSegments($data['fileId'], $status);

        return response()->json([
            'data' => array_map([$this, 'segmentToArray'], $segments),
            'meta' => [
                'total'  => $total,
                'limit'  => $data['limit'] ?? 100,
                'offset' => $data['offset'] ?? 0,
            ],
        ]);
    }

    /**
     * Get a single segment.
     *
     * @group Segments
     * @authenticated
     * @urlParam project integer required Project ID. Example: 1
     * @urlParam segmentId string required Segment UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     */
    public function show(Project $project, string $segmentId): JsonResponse
    {
        $this->authorize('view', $project);

        $segment = $this->makeStore($project)->getSegment($segmentId);

        return response()->json(['data' => $this->segmentToArray($segment)]);
    }

    /**
     * Update a segment's translation or status.
     *
     * @group Segments
     * @authenticated
     * @urlParam project integer required Project ID. Example: 1
     * @urlParam segmentId string required Segment UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     * @bodyParam targetText string New target text (with tag placeholders preserved). Example: Bonjour le monde.
     * @bodyParam status string New status (untranslated|draft|translated|reviewed|approved|rejected). Example: translated
     */
    public function update(Request $request, Project $project, string $segmentId): JsonResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'targetText' => 'sometimes|nullable|string',
            'status'     => 'sometimes|string|in:untranslated,draft,translated,reviewed,approved,rejected',
        ]);

        $store  = $this->makeStore($project);
        $status = isset($data['status']) ? SegmentStatus::from($data['status']) : null;

        $store->updateSegment($segmentId, $data['targetText'] ?? null, $status);

        return response()->json(['data' => $this->segmentToArray($store->getSegment($segmentId))]);
    }

    private function makeStore(Project $project): PostgresSegmentStore
    {
        abort_unless(
            config('database.default') === 'pgsql',
            503,
            'Segment storage requires a PostgreSQL database connection (DB_CONNECTION=pgsql).',
        );

        return new PostgresSegmentStore(DB::getPdo(), (string) $project->id);
    }

    private function segmentToArray(mixed $segment): array
    {
        return [
            'id'             => $segment->id,
            'fileId'         => $segment->fileId,
            'segmentNumber'  => $segment->segmentNumber,
            'sourceText'     => $segment->sourceText,
            'targetText'     => $segment->targetText,
            'status'         => $segment->status->value,
            'wordCount'      => $segment->wordCount,
            'tmMatchPercent' => $segment->tmMatchPercent,
            'tmMatchOrigin'  => $segment->tmMatchOrigin,
            'note'           => $segment->note,
            'createdAt'      => $segment->createdAt->format(\DateTimeInterface::ATOM),
            'updatedAt'      => $segment->updatedAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
