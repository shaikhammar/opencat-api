<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MtController extends Controller
{
    /**
     * Machine-translate one or more segments.
     *
     * @group Machine Translation
     * @authenticated
     * @bodyParam projectId integer required Project ID. Example: 1
     * @bodyParam segments object[] required Segments to translate. Example: [{"sourceText":"Hello"}]
     * @bodyParam sourceLang string required Source language. Example: en
     * @bodyParam targetLang string required Target language. Example: fr
     */
    public function translate(Request $request): JsonResponse
    {
        $request->validate([
            'projectId'             => 'required|integer|exists:projects,id',
            'segments'              => 'required|array|min:1',
            'segments.*.sourceText' => 'required|string',
            'sourceLang'            => 'required|string|max:10',
            'targetLang'            => 'required|string|max:10',
        ]);

        // MT adapter requires a PSR-18 HTTP client (not yet wired).
        // Configure DEEPL_API_KEY and install an HTTP client (e.g. guzzlehttp/guzzle
        // with php-http/guzzle7-adapter) to enable this endpoint.
        return response()->json(
            ['message' => 'MT adapter not configured. Set DEEPL_API_KEY and add a PSR-18 HTTP client.'],
            501,
        );
    }
}
