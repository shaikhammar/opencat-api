<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->text('url');
            $table->string('secret');   // HMAC-SHA256 signing key, set at registration
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
