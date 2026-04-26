<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    protected $fillable = ['user_id', 'name', 'source_lang', 'target_langs', 'manifest', 'storage_path'];

    protected function casts(): array
    {
        return [
            'target_langs' => 'array',
            'manifest'     => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
