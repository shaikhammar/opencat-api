<?php

namespace App\Jobs;

use App\Models\ProcessingJob;
use App\Models\Project;
use App\Models\UploadedFile;
use App\Services\FileStorageService;
use App\Services\JobProgressService;
use App\Services\ProjectService;
use App\Services\WorkflowRunnerFactory;
use CatFramework\Workflow\WorkflowOptions;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessFileJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;
    public int $tries   = 1;

    public function __construct(
        public readonly int    $processingJobId,
        public readonly int    $projectId,
        public readonly int    $uploadedFileId,
        public readonly string $targetLang,
        public readonly array  $options = [],
    ) {}

    public function handle(
        JobProgressService    $progress,
        ProjectService        $projectService,
        FileStorageService    $fileStorage,
        WorkflowRunnerFactory $runnerFactory,
    ): void {
        $job     = ProcessingJob::findOrFail($this->processingJobId);
        $project = Project::findOrFail($this->projectId);
        $file    = UploadedFile::findOrFail($this->uploadedFileId);

        $job->update(['status' => 'processing']);

        try {
            $result = $this->runWorkflow($job, $project, $file, $projectService, $fileStorage, $progress, $runnerFactory);
            $progress->complete($job, $result);
        } catch (\Throwable $e) {
            $progress->fail($job, $e->getMessage());
        }
    }

    private function runWorkflow(
        ProcessingJob         $job,
        Project               $project,
        UploadedFile          $file,
        ProjectService        $projectService,
        FileStorageService    $fileStorage,
        JobProgressService    $progressService,
        WorkflowRunnerFactory $runnerFactory,
    ): array {
        $sourceLang = $project->source_lang ?? 'en';
        $filePath   = $fileStorage->absolutePath($file);

        $options            = WorkflowOptions::defaults();
        $options->writeXliff = true;
        $options->outputDir  = $projectService->storagePath($project);

        $runner = $runnerFactory->build($project, $sourceLang, $this->targetLang, $options);

        $total = 0;
        $runner->onSegmentProcessed(function ($pair, int $index, int $runTotal) use ($progressService, $job, &$total): void {
            $total = $runTotal;
            if ($runTotal > 0) {
                $progressService->update($job, (int) (($index + 1) / $runTotal * 99));
            }
        });

        $workflowResult = $runner->process($filePath, $this->targetLang);

        return [
            'xliffPath'  => $workflowResult->xliffPath,
            'storeFileId' => $workflowResult->storeFileId,
            'matchStats'  => [
                'exact'     => $workflowResult->matchStats->exact,
                'fuzzy'     => $workflowResult->matchStats->fuzzy,
                'mt'        => $workflowResult->matchStats->mt,
                'unmatched' => $workflowResult->matchStats->unmatched,
            ],
            'qaIssues'   => array_map(
                fn($issue) => [
                    'checkId'  => $issue->checkId,
                    'severity' => $issue->severity->value,
                    'message'  => $issue->message,
                    'segmentId' => $issue->segmentId,
                ],
                $workflowResult->qaIssues,
            ),
            'timings'    => $workflowResult->timings,
        ];
    }
}
