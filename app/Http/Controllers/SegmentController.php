<?php

namespace App\Http\Controllers;

use CatFramework\Core\Model\Segment;
use CatFramework\Segmentation\SrxSegmentationEngine;
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

        $engine    = new SrxSegmentationEngine();
        $input     = new Segment('input', [$data['text']]);
        $sentences = $engine->segment($input, $data['lang']);

        return response()->json([
            'data' => [
                'sentences' => array_map(fn($s) => $s->getPlainText(), $sentences),
            ],
        ]);
    }
}
