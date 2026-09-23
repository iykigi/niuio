<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DatabaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'engine' => $this->engine,
            'host' => $this->host,
            'port' => $this->port,
            'size_bytes' => $this->size_bytes,
            'status' => $this->status,
            'site_id' => $this->site_id,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
