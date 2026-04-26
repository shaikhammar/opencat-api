<?php

namespace App\Services;

use App\Models\ProcessingJob;

class JobProgressService
{
    public function update(ProcessingJob $job, int $progress): void
    {
        $job->update(['progress' => min(100, max(0, $progress))]);
    }

    public function complete(ProcessingJob $job, array $result): void
    {
        $job->update(['status' => 'completed', 'progress' => 100, 'result' => $result]);
    }

    public function fail(ProcessingJob $job, string $error): void
    {
        $job->update(['status' => 'failed', 'error' => $error]);
    }
}
