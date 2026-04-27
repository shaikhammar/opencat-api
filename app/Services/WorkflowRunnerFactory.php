<?php

namespace App\Services;

use App\Models\Project;
use CatFramework\FilterDocx\DocxFilter;
use CatFramework\FilterHtml\HtmlFilter;
use CatFramework\FilterPlaintext\PlainTextFilter;
use CatFramework\Qa\Check\DoubleSpaceCheck;
use CatFramework\Qa\Check\EmptyTranslationCheck;
use CatFramework\Qa\Check\NumberConsistencyCheck;
use CatFramework\Qa\Check\TagConsistencyCheck;
use CatFramework\Qa\Check\WhitespaceCheck;
use CatFramework\Qa\QualityRunner;
use CatFramework\Segmentation\SrxSegmentationEngine;
use CatFramework\TranslationMemory\SqliteTranslationMemory;
use CatFramework\Workflow\FileFilterRegistry;
use CatFramework\Workflow\WorkflowOptions;
use CatFramework\Workflow\WorkflowRunner;
use CatFramework\Xliff\XliffWriter;

class WorkflowRunnerFactory
{
    public function __construct(private readonly ProjectService $projectService) {}

    public function build(
        Project $project,
        string $sourceLang,
        string $targetLang,
        WorkflowOptions $options = new WorkflowOptions(),
    ): WorkflowRunner {
        $this->projectService->ensureDirectoryExists($project);

        $registry = new FileFilterRegistry();
        $registry->register(new PlainTextFilter());
        $registry->register(new HtmlFilter());
        $registry->register(new DocxFilter());

        $tmPath = $this->projectService->tmPath($project, $targetLang);
        $tm     = new SqliteTranslationMemory(new \PDO("sqlite:{$tmPath}"));

        $qa = new QualityRunner();
        $qa->register(new EmptyTranslationCheck());
        $qa->register(new WhitespaceCheck());
        $qa->register(new DoubleSpaceCheck());
        $qa->register(new NumberConsistencyCheck());
        $qa->register(new TagConsistencyCheck());

        return new WorkflowRunner(
            fileFilterRegistry: $registry,
            segmentationEngine: new SrxSegmentationEngine(),
            xliffWriter:        new XliffWriter(),
            sourceLang:         $sourceLang,
            translationMemory:  $tm,
            qaRunner:           $qa,
            options:            $options,
        );
    }
}
