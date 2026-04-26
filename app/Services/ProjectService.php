<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Storage;

class ProjectService
{
    public function storagePath(Project $project): string
    {
        return Storage::disk('local')->path("projects/{$project->id}");
    }

    public function tmPath(Project $project, string $targetLang): string
    {
        return $this->storagePath($project) . "/tm_{$targetLang}.db";
    }

    public function glossaryPath(Project $project): string
    {
        return $this->storagePath($project) . '/glossary.db';
    }

    public function ensureDirectoryExists(Project $project): void
    {
        $dir = $this->storagePath($project);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
