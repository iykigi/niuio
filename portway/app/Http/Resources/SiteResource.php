<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'project_type' => $this->project_type,
            'runtime' => $this->runtime,
            'php_version' => $this->php_version,
            'node_version' => $this->node_version,
            'status' => $this->status->value,
            'status_message' => $this->status_message,
            'provisioning_progress' => $this->provisioning_progress,
            'disk_usage_bytes' => $this->disk_usage_bytes,
            'primary_domain' => $this->domains->firstWhere('type', 'primary')?->hostname
                ?? $this->temporaryHostname(),
            'last_deployed_at' => $this->last_deployed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
