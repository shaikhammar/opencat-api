<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TerminologyController extends Controller
{
    public function __construct(private readonly ProjectService $projects) {}

    /**
     * Recognize terms in text.
     *
     * @group Terminology
     * @authenticated
     * @bodyParam projectId integer required Project ID. Example: 1
     * @bodyParam text string required Text to scan for known terms. Example: This document uses a specific term.
     * @bodyParam sourceLang string required Source language. Example: en
     * @bodyParam targetLang string required Target language. Example: fr
     */
    public function recognize(Request $request): JsonResponse
    {
        $data = $request->validate([
            'projectId'  => 'required|integer|exists:projects,id',
            'text'       => 'required|string',
            'sourceLang' => 'required|string|max:10',
            'targetLang' => 'required|string|max:10',
        ]);

        $project = Project::findOrFail($data['projectId']);
        $this->authorize('view', $project);

        // catframework/terminology integration point:
        // $glossary = new SqliteTerminologyProvider($this->projects->glossaryPath($project));
        // $matches  = $glossary->recognize($data['text'], $data['sourceLang'], $data['targetLang']);

        return response()->json(['data' => ['matches' => []]]);
    }

    /**
     * Import a TBX file into the project glossary.
     *
     * @group Terminology
     * @authenticated
     * @bodyParam tbxFile file required TBX file to import. Example: glossary.tbx
     * @bodyParam projectId integer required Project ID. Example: 1
     */
    public function import(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tbxFile'   => 'required|file',
            'projectId' => 'required|integer|exists:projects,id',
        ]);

        $project = Project::findOrFail($data['projectId']);
        $this->authorize('update', $project);

        $tbxPath = $request->file('tbxFile')->store('tmp', 'local');

        // catframework/terminology integration point:
        // $terms    = (new TbxReader)->read(storage_path('app/' . $tbxPath));
        // $glossary = new SqliteTerminologyProvider($this->projects->glossaryPath($project));
        // $glossary->importBatch($terms);

        return response()->json(['data' => ['imported' => 0]]);
    }
}
