<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SegmentController extends Controller
{
    /**
     * Segment plain text into sentences.
     *
     * @group Processing
     * @authenticated
     * @bodyParam text string required Plain text to segment. Example: Hello world. How are you?
     * @bodyParam lang string required BCP-47 language code. Example: en
     */
    public function segment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'text' => 'required|string',
            'lang' => 'required|string|max:10',
        ]);

        // catframework/segmentation integration point:
        // $engine    = app(SrxSegmentationEngine::class);
        // $sentences = $engine->segment($data['text'], $data['lang']);

        return response()->json(['data' => ['sentences' => []]]);
    }
}
