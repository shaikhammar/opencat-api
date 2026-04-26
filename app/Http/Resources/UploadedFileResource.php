<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UploadedFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'fileId'      => $this->id,
            'name'        => $this->original_name,
            'size'        => $this->size_bytes,
            'mimeType'    => $this->mime_type,
            'expiresAt'   => $this->expires_at,
        ];
    }
}
