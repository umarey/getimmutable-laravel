<?php

namespace GetImmutable;

use GetImmutable\Jobs\SendAuditLogEvent;
use Illuminate\Contracts\Auth\Authenticatable;

class AuditLogManager
{
    /**
     * @param  bool  $autoSession  Automatically attach session ID from the current HTTP session.
     *                             Silently skipped in queue/console contexts where no session is available.
     */
    public function __construct(
        private AuditLogClient $client,
        private bool $async = true,
        private bool $autoSession = false,
    ) {}

    /**
     * Send a raw event payload to the Immutable API.
     *
     * @param  array<string, mixed>  $payload
     */
    public function track(array $payload): void
    {
        if ($this->autoSession && ! isset($payload['session_id'])) {
            try {
                $payload['session_id'] = session()->getId();
            } catch (\Throwable) {
                // Session may not be available (console, queue, etc.)
            }
        }

        if ($this->async) {
            SendAuditLogEvent::dispatch($payload);
        } else {
            $this->client->track($payload);
        }
    }

    /**
     * Send a batch of raw event payloads.
     *
     * @param  array<int, array<string, mixed>>  $events
     */
    public function trackBatch(array $events): void
    {
        if ($this->async) {
            SendAuditLogEvent::dispatch($events, true);
        } else {
            $this->client->trackBatch($events);
        }
    }

    /**
     * Start a pending event pre-filled with the currently authenticated user.
     */
    public function fromAuth(): PendingEvent
    {
        $user = auth()->user();

        return new PendingEvent($this, [
            'id' => $user?->getAuthIdentifier() ?? 'system',
            'name' => $user?->name ?? null,
        ]);
    }

    /**
     * Start a pending event with an explicit actor.
     *
     * @param  Authenticatable|array{id: string|int, name?: string, type?: string}  $actor
     */
    public function actor(Authenticatable|array $actor): PendingEvent
    {
        if ($actor instanceof Authenticatable) {
            return new PendingEvent($this, [
                'id' => $actor->getAuthIdentifier(),
                'name' => $actor->name ?? null,
            ]);
        }

        return new PendingEvent($this, [
            'id' => $actor['id'],
            'name' => $actor['name'] ?? null,
            'type' => $actor['type'] ?? null,
        ]);
    }

    /**
     * Get the underlying HTTP client for direct API calls.
     */
    public function client(): AuditLogClient
    {
        return $this->client;
    }
}
