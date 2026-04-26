<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectService $projects) {}

    /**
     * List all projects.
     *
     * @group Projects
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()->projects()->paginate(20);

        return response()->json([
            'data' => ProjectResource::collection($items->items()),
            'meta' => [
                'total'   => $items->total(),
                'page'    => $items->currentPage(),
                'perPage' => $items->perPage(),
            ],
        ]);
    }

    /**
     * Create a project.
     *
     * @group Projects
     * @authenticated
     * @bodyParam name string required Project name. Example: My DOCX Project
     * @bodyParam sourceLang string required BCP-47 source language code. Example: en
     * @bodyParam targetLangs string[] required Target language codes. Example: ["fr","de"]
     * @bodyParam manifest object optional Additional manifest overrides (MT config, QA config, etc.).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'sourceLang'   => 'required|string|max:10',
            'targetLangs'  => 'required|array|min:1',
            'targetLangs.*'=> 'string|max:10',
            'manifest'     => 'sometimes|array',
        ]);

        $project = $request->user()->projects()->create([
            'name'        => $data['name'],
            'source_lang' => $data['sourceLang'],
            'target_langs'=> $data['targetLangs'],
            'manifest'    => $data['manifest'] ?? null,
        ]);

        $this->projects->ensureDirectoryExists($project);

        return response()->json(['data' => new ProjectResource($project)], 201);
    }

    /**
     * Get a project.
     *
     * @group Projects
     * @authenticated
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return response()->json(['data' => new ProjectResource($project)]);
    }

    /**
     * Update a project.
     *
     * @group Projects
     * @authenticated
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'targetLangs'  => 'sometimes|array|min:1',
            'targetLangs.*'=> 'string|max:10',
            'manifest'     => 'sometimes|array',
        ]);

        $project->update(array_filter([
            'name'         => $data['name'] ?? null,
            'target_langs' => $data['targetLangs'] ?? null,
            'manifest'     => $data['manifest'] ?? null,
        ], fn($v) => $v !== null));

        return response()->json(['data' => new ProjectResource($project)]);
    }

    /**
     * Delete a project.
     *
     * @group Projects
     * @authenticated
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorize('delete', $project);
        $project->delete();

        return response()->json(null, 204);
    }
}
