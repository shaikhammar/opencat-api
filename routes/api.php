<?php

use App\Http\Controllers\Auth\TokenController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\ExtractController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\MtController;
use App\Http\Controllers\ProcessController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectFileController;
use App\Http\Controllers\ProjectSegmentController;
use App\Http\Controllers\ProjectTmController;
use App\Http\Controllers\QaController;
use App\Http\Controllers\SegmentController;
use App\Http\Controllers\TerminologyController;
use App\Http\Controllers\TmController;
use Illuminate\Support\Facades\Route;

// --- Auth (public) ---
Route::prefix('auth')->group(function () {
    Route::post('tokens', [TokenController::class, 'store']);
});

// --- Authenticated routes ---
Route::middleware('auth:sanctum')->group(function () {

    // Auth token management
    Route::prefix('auth')->group(function () {
        Route::get('tokens', [TokenController::class, 'index']);
        Route::delete('tokens/{id}', [TokenController::class, 'destroy']);
    });

    // Files
    Route::prefix('files')->group(function () {
        Route::post('/', [FileController::class, 'store']);
        Route::get('{uploadedFile}', [FileController::class, 'show']);
        Route::get('{uploadedFile}/download', [FileController::class, 'download']);
        Route::delete('{uploadedFile}', [FileController::class, 'destroy']);
    });

    // Projects
    Route::apiResource('projects', ProjectController::class);

    // Processing — full workflow per project
    Route::post('projects/{project}/process', [ProcessController::class, 'process']);

    // Processed file listing + export (Postgres only)
    Route::get('projects/{project}/files', [ProjectFileController::class, 'index']);
    Route::get('projects/{project}/files/{fileId}/export', [ExportController::class, 'export']);

    // Project-scoped Postgres TM (replaces global /tm/* for Postgres deployments)
    Route::get('projects/{project}/tm', [ProjectTmController::class, 'show']);
    Route::post('projects/{project}/tm/lookup', [ProjectTmController::class, 'lookup']);
    Route::post('projects/{project}/tm/import', [ProjectTmController::class, 'import']);
    Route::post('projects/{project}/tm/segments', [ProjectTmController::class, 'store']);

    // Webhooks
    Route::get('projects/{project}/webhooks', [WebhookController::class, 'index']);
    Route::post('projects/{project}/webhooks', [WebhookController::class, 'store']);
    Route::delete('projects/{project}/webhooks/{webhook}', [WebhookController::class, 'destroy']);

    // Segment CRUD (Postgres only — requires DB_CONNECTION=pgsql)
    Route::get('projects/{project}/segments', [ProjectSegmentController::class, 'index']);
    Route::patch('projects/{project}/segments', [ProjectSegmentController::class, 'bulkUpdate']);
    Route::get('projects/{project}/segments/{segmentId}', [ProjectSegmentController::class, 'show']);
    Route::patch('projects/{project}/segments/{segmentId}', [ProjectSegmentController::class, 'update']);

    // Fine-grained pipeline steps
    Route::post('extract', [ExtractController::class, 'extract']);
    Route::post('segment', [SegmentController::class, 'segment']);

    // Translation Memory
    Route::prefix('tm')->group(function () {
        Route::post('lookup', [TmController::class, 'lookup']);
        Route::post('import', [TmController::class, 'import']);
        Route::post('segments', [TmController::class, 'addSegment']);
    });

    // Machine Translation
    Route::post('mt/translate', [MtController::class, 'translate']);

    // QA
    Route::post('qa/run', [QaController::class, 'run']);

    // Terminology
    Route::prefix('terminology')->group(function () {
        Route::post('recognize', [TerminologyController::class, 'recognize']);
        Route::post('import', [TerminologyController::class, 'import']);
    });

    // Async jobs
    Route::get('jobs/{processingJob}', [JobController::class, 'show']);
    Route::delete('jobs/{processingJob}', [JobController::class, 'destroy']);
});
