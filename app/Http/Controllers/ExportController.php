<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFile;
use CatFramework\Core\Model\BilingualDocument;
use CatFramework\FilterDocx\DocxFilter;
use CatFramework\FilterHtml\HtmlFilter;
use CatFramework\FilterPlaintext\PlainTextFilter;
use CatFramework\FilterPo\PoFilter;
use CatFramework\FilterXml\XmlFilter;
use CatFramework\Project\Store\DatabaseSkeletonStore;
use CatFramework\Project\Store\PostgresSegmentStore;
use CatFramework\Workflow\FileFilterRegistry;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    /**
     * Generate and download the translated target file.
     *
     * Reconstructs the original file format from stored segments and skeleton.
     * Untranslated segments fall back to source text automatically.
     *
     * @group Export
     * @authenticated
     * @urlParam project integer required Project ID. Example: 1
     * @urlParam fileId string required The storeFileId (UUID) returned after processing. Example: 550e8400-e29b-41d4-a716-446655440000
     */
    public function export(Project $project, string $fileId): BinaryFileResponse
    {
        $this->authorize('view', $project);

        $projectFile = ProjectFile::where('id', $fileId)
            ->where('project_id', $project->id)
            ->firstOrFail();

        abort_unless(
            config('database.default') === 'pgsql',
            503,
            'Export requires a PostgreSQL database connection (DB_CONNECTION=pgsql).',
        );

        $doc    = $this->buildDocumentForExport($project, $projectFile, DB::getPdo());
        $filter = $this->buildFilterRegistry()->getFilter($projectFile->original_name, $projectFile->mime_type);

        $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileId . '_' . $projectFile->original_name;
        $filter->rebuild($doc, $tmpPath);

        return response()->download($tmpPath, $projectFile->original_name, [
            'Content-Type' => $projectFile->mime_type,
        ])->deleteFileAfterSend(true);
    }

    /**
     * Build a BilingualDocument ready for filter->rebuild() from two DB sources.
     *
     * You need to combine:
     *   1. Translated segments  — PostgresSegmentStore::hydrate($fileId) gives you a
     *      BilingualDocument with the right SegmentPair objects, but with empty
     *      sourceLanguage, targetLanguage, originalFile, mimeType, and skeleton.
     *
     *   2. Skeleton bytes       — DatabaseSkeletonStore::retrieve($fileId) returns a
     *      JSON string (the serialised skeleton array from the filter's extract()).
     *      Decode it to an array with json_decode($json, true).
     *
     *   3. Project metadata     — $project->source_lang (string) and
     *      $projectFile->target_lang, ->original_name, ->mime_type.
     *
     * Combine all three into one new BilingualDocument:
     *
     *   new BilingualDocument(
     *       sourceLanguage: ...,
     *       targetLanguage: ...,
     *       originalFile:   ...,
     *       mimeType:       ...,
     *       segmentPairs:   ...,   // from hydrate()
     *       skeleton:       ...,   // decoded JSON
     *   )
     *
     * Important design choice: should this method throw if segments are still
     * Untranslated? Or silently proceed (letting the filter fall back to source text)?
     * The filter contract says "Untranslated segments fall back to source text."
     * Consider both options and pick the one that matches your workflow.
     */
    private function buildDocumentForExport(Project $project, ProjectFile $projectFile, \PDO $pdo): BilingualDocument
    {
        $hydrated = (new PostgresSegmentStore($pdo, (string) $project->id))->hydrate($projectFile->id);

        $untranslated = array_filter(
            $hydrated->getSegmentPairs(),
            fn($pair) => $pair->target === null || trim($pair->target->getPlainText()) === '',
        );

        if ($untranslated !== []) {
            throw new \RuntimeException(
                sprintf('Cannot export: %d segment(s) have no translation.', count($untranslated))
            );
        }

        $skeletonJson = (new DatabaseSkeletonStore($pdo))->retrieve($projectFile->id);

        return new BilingualDocument(
            sourceLanguage: $project->source_lang ?? 'en',
            targetLanguage: $projectFile->target_lang,
            originalFile:   $projectFile->original_name,
            mimeType:       $projectFile->mime_type,
            segmentPairs:   $hydrated->getSegmentPairs(),
            skeleton:       json_decode($skeletonJson, true, 512, JSON_THROW_ON_ERROR),
        );
    }

    private function buildFilterRegistry(): FileFilterRegistry
    {
        $registry = new FileFilterRegistry();
        $registry->register(new PlainTextFilter());
        $registry->register(new HtmlFilter());
        $registry->register(new DocxFilter());
        $registry->register(new PoFilter());
        $registry->register(new XmlFilter());

        return $registry;
    }
}
