<?php

namespace App\Jobs;

use App\Models\ProcessingJob;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\UploadedFile;
use App\Models\Webhook;
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
            $this->dispatchWebhooks($project, $job, $result['storeFileId'] ?? null, 'completed', $result['matchStats'] ?? []);
        } catch (\Throwable $e) {
            $progress->fail($job, $e->getMessage());
            $this->dispatchWebhooks($project, $job, null, 'failed', []);
        }
    }

    private function dispatchWebhooks(
        Project $project,
        ProcessingJob $job,
        ?string $storeFileId,
        string $status,
        array $matchStats,
    ): void {
        $webhooks = Webhook::where('project_id', $project->id)->get();

        if ($webhooks->isEmpty()) {
            return;
        }

        $payload = [
            'event'       => "job.{$status}",
            'jobId'       => $job->id,
            'projectId'   => $project->id,
            'fileId'      => $storeFileId,
            'status'      => $status,
            'matchStats'  => $matchStats,
            'completedAt' => now()->toAtomString(),
        ];

        foreach ($webhooks as $webhook) {
            DeliverWebhookJob::dispatch($webhook->id, $payload);
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

        if ($workflowResult->storeFileId !== null) {
            $total = $workflowResult->matchStats->exact
                   + $workflowResult->matchStats->fuzzy
                   + $workflowResult->matchStats->mt
                   + $workflowResult->matchStats->unmatched;

            ProjectFile::updateOrCreate(
                ['id' => $workflowResult->storeFileId],
                [
                    'project_id'       => $project->id,
                    'uploaded_file_id' => $file->id,
                    'target_lang'      => $this->targetLang,
                    'original_name'    => $file->original_name,
                    'mime_type'        => $file->mime_type ?? 'application/octet-stream',
                    'segment_count'    => $total,
                ],
            );
        }

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
