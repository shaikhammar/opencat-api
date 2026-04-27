<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skeletons', function (Blueprint $table) {
            $table->string('id')->primary();   // the fileId UUID
            $table->string('format');          // mime type of the original file
            $table->binary('blob');            // JSON-encoded skeleton array
            $table->timestampTz('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skeletons');
    }
};
