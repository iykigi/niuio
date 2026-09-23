<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DomainResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_id' => $this->site_id,
            'hostname' => $this->hostname,
            'type' => $this->type,
            'status' => $this->status->value,
            'verified_at' => $this->verified_at?->toIso8601String(),
            'force_https' => $this->force_https,
            'ssl_status' => $this->sslCertificate?->status->value,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
