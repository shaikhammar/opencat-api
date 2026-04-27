<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('segments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('file_id');
            $table->string('project_id');
            $table->unsignedInteger('segment_number');
            $table->text('source_text');
            $table->text('target_text')->nullable();
            $table->jsonb('source_tags')->default('[]');
            $table->jsonb('target_tags')->default('[]');
            $table->string('status', 20)->default('untranslated');
            $table->unsignedSmallInteger('word_count')->default(0);
            $table->unsignedSmallInteger('tm_match_percent')->nullable();
            $table->string('tm_match_origin')->nullable();
            $table->text('context_before')->nullable();
            $table->text('context_after')->nullable();
            $table->text('note')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');
        });

        Schema::table('segments', function (Blueprint $table) {
            $table->unique(['file_id', 'segment_number']);
            $table->index(['file_id', 'status']);
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('segments');
    }
};
