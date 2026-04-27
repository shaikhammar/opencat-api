<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_files', function (Blueprint $table) {
            $table->uuid('id')->primary();              // = storeFileId from WorkflowResult
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_file_id')->constrained('uploaded_files')->cascadeOnDelete();
            $table->string('target_lang', 10);
            $table->text('original_name');              // e.g. "manual.docx" — used by filter selection on export
            $table->string('mime_type', 100);           // from BilingualDocument::$mimeType
            $table->unsignedInteger('segment_count')->default(0);
            $table->timestampsTz();
        });

        Schema::table('project_files', function (Blueprint $table) {
            $table->index(['project_id', 'target_lang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_files');
    }
};
