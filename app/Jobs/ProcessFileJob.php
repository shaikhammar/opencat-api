<?php

namespace App\Jobs;

use App\Models\ProcessingJob;
use App\Models\Project;
use App\Models\UploadedFile;
use App\Services\FileStorageService;
use App\Services\JobProgressService;
use App\Services\ProjectService;
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
        JobProgressService  $progress,
        ProjectService      $projectService,
        FileStorageService  $fileStorage,
    ): void {
        $job     = ProcessingJob::findOrFail($this->processingJobId);
        $project = Project::findOrFail($this->projectId);
        $file    = UploadedFile::findOrFail($this->uploadedFileId);

        $job->update(['status' => 'processing']);

        try {
            // catframework/workflow is a framework package — integration is wired here
            // when the packages are available via path repositories.
            // For now this is a stub that records the result shape.
            $result = $this->runWorkflow($job, $project, $file, $projectService, $fileStorage, $progress);
            $progress->complete($job, $result);
        } catch (\Throwable $e) {
            $progress->fail($job, $e->getMessage());
        }
    }

    private function runWorkflow(
        ProcessingJob      $job,
        Project            $project,
        UploadedFile       $file,
        ProjectService     $projectService,
        FileStorageService $fileStorage,
        JobProgressService $progressService,
    ): array {
        // WorkflowRunner integration point — hydrate from ProjectService paths.
        // When catframework packages resolve, replace this with:
        //   $runner = app(WorkflowRunner::class);
        //   $runner->onSegmentProcessed(fn($i, $total) => $progressService->update($job, (int)(($i/$total)*100)));
        //   $workflowResult = $runner->process($manifest, $fileStorage->absolutePath($file));

        return [
            'xliffFileId' => null,
            'matchStats'  => ['exact' => 0, 'fuzzy' => 0, 'mt' => 0, 'unmatched' => 0],
            'qaIssues'    => [],
            'timings'     => [],
        ];
    }
}
