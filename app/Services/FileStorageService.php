<?php

namespace App\Services;

use App\Models\UploadedFile;
use Illuminate\Http\UploadedFile as HttpUploadedFile;
use Illuminate\Support\Facades\Storage;

class FileStorageService
{
    public function store(HttpUploadedFile $file, int $userId): UploadedFile
    {
        $path = $file->store("uploads/{$userId}", 'local');
        $retentionHours = config('catframework.file_retention_hours', 24);

        return UploadedFile::create([
            'user_id'       => $userId,
            'original_name' => $file->getClientOriginalName(),
            'storage_path'  => $path,
            'mime_type'     => $file->getMimeType(),
            'size_bytes'    => $file->getSize(),
            'expires_at'    => now()->addHours($retentionHours),
        ]);
    }

    public function absolutePath(UploadedFile $file): string
    {
        return Storage::disk('local')->path($file->storage_path);
    }

    public function delete(UploadedFile $file): void
    {
        Storage::disk('local')->delete($file->storage_path);
        $file->delete();
    }
}
