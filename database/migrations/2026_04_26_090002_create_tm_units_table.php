<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // pg_trgm is required for similarity() queries in PostgresTranslationMemory.
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        Schema::create('tm_units', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tm_id');                     // scopes rows to a logical TM (e.g. project id + lang pair)
            $table->string('source_lang', 10);
            $table->string('target_lang', 10);
            $table->text('source_text');
            $table->text('target_text');
            $table->text('source_segment');              // JSON-encoded Segment (for round-trip)
            $table->text('target_segment');
            $table->text('source_text_normalized');      // lowercased+trimmed, used by trgm
            $table->timestampTz('created_at');
            $table->timestampTz('last_used_at')->nullable();
            $table->string('created_by')->nullable();
            $table->jsonb('metadata')->default('{}');
        });

        Schema::table('tm_units', function (Blueprint $table) {
            $table->unique(['tm_id', 'source_lang', 'target_lang', 'source_text_normalized'], 'tm_units_dedup');
            $table->index(['tm_id', 'source_lang', 'target_lang'], 'tm_units_filter');
        });

        // GIN index on source_text_normalized enables O(log n) similarity() pre-filter.
        DB::statement('CREATE INDEX tm_units_trgm ON tm_units USING GIN (source_text_normalized gin_trgm_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('tm_units');
    }
};
