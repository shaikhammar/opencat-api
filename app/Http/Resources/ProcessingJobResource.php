<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcessingJobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'jobId'    => $this->id,
            'status'   => $this->status,
            'progress' => $this->progress,
            'result'   => $this->result,
            'error'    => $this->error,
        ];
    }
}
