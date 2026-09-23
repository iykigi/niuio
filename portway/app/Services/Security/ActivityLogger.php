<?php

namespace App\Services\Security;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

/**
 * Appends tamper-resistant activity log rows: every row's hash covers
 * its own contents plus the previous row's hash, so a row deleted or
 * edited after the fact breaks the chain — verify() detects exactly
 * that. Rows are never updated or deleted by application code.
 */
class ActivityLogger
{
    public function log(
        string $action,
        ?Model $target = null,
        ?string $description = null,
        ?int $userId = null,
        array $metadata = [],
    ): ActivityLog {
        $previous = ActivityLog::query()->orderByDesc('id')->first();
        $previousHash = $previous?->hash;

        $payload = [
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'target_type' => $target ? $target::class : null,
            'target_id' => $target?->getKey(),
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => (string) Request::userAgent(),
            'metadata' => $metadata,
            'previous_hash' => $previousHash,
            'created_at' => now(),
        ];

        $payload['hash'] = $this->computeHash($payload, $previousHash);

        return ActivityLog::create($payload);
    }

    private function computeHash(array $payload, ?string $previousHash): string
    {
        $canonical = json_encode([
            $payload['user_id'], $payload['action'], $payload['target_type'], $payload['target_id'],
            $payload['description'], $payload['ip_address'], $payload['metadata'],
            $previousHash, (string) $payload['created_at'],
        ]);

        return hash('sha256', $canonical);
    }

    /**
     * Walk the entire chain and confirm every row's stored hash still
     * matches what it should be. Used by the admin Security section's
     * "Verify activity log integrity" action.
     */
    public function verifyChainIntegrity(): bool
    {
        $previousHash = null;

        foreach (ActivityLog::query()->orderBy('id')->cursor() as $log) {
            $expected = $this->computeHash([
                'user_id' => $log->user_id,
                'action' => $log->action,
                'target_type' => $log->target_type,
                'target_id' => $log->target_id,
                'description' => $log->description,
                'ip_address' => $log->ip_address,
                'metadata' => $log->metadata ?? [],
                'created_at' => $log->created_at,
            ], $previousHash);

            if ($expected !== $log->hash || $log->previous_hash !== $previousHash) {
                return false;
            }

            $previousHash = $log->hash;
        }

        return true;
    }
}
