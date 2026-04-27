<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    /**
     * Register a webhook URL for a project.
     *
     * @group Webhooks
     * @authenticated
     * @urlParam project integer required Project ID. Example: 1
     * @bodyParam url string required HTTPS URL to receive webhook POSTs. Example: https://example.com/hooks/cat
     * @bodyParam secret string Signing secret for HMAC-SHA256 verification (auto-generated if omitted).
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'url'    => 'required|url|max:2048',
            'secret' => 'sometimes|string|min:16|max:255',
        ]);

        $webhook = Webhook::create([
            'project_id' => $project->id,
            'url'        => $data['url'],
            'secret'     => $data['secret'] ?? Str::random(32),
        ]);

        return response()->json([
            'data' => [
                'id'     => $webhook->id,
                'url'    => $webhook->url,
                'secret' => $webhook->secret,   // only returned at creation; store it now
            ],
        ], 201);
    }

    /**
     * List webhooks for a project.
     *
     * @group Webhooks
     * @authenticated
     * @urlParam project integer required Project ID. Example: 1
     */
    public function index(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return response()->json([
            'data' => Webhook::where('project_id', $project->id)
                ->get()
                ->map(fn(Webhook $w) => ['id' => $w->id, 'url' => $w->url, 'createdAt' => $w->created_at?->toAtomString()]),
        ]);
    }

    /**
     * Remove a webhook.
     *
     * @group Webhooks
     * @authenticated
     * @urlParam project integer required Project ID. Example: 1
     * @urlParam webhook integer required Webhook ID. Example: 3
     */
    public function destroy(Project $project, Webhook $webhook): JsonResponse
    {
        $this->authorize('update', $project);

        abort_if($webhook->project_id !== $project->id, 404);

        $webhook->delete();

        return response()->json(null, 204);
    }
}
