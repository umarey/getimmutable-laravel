<?php

namespace GetImmutable;

use GetImmutable\Jobs\SendAuditLogEvent;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

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
     * Log an event using auto-captured request context.
     *
     * Requires the CaptureAuditContext middleware to be active and an authenticated user.
     *
     * @param  string  $action  The action name (e.g. "project.exported").
     * @param  Model|string|null  $resource  An Eloquent model, a resource type string, or null.
     * @param  array<string, mixed>  $metadata  Additional key-value metadata.
     *
     * @throws \RuntimeException If the middleware is not active or the user is not authenticated.
     */
    public function log(string $action, Model|string|null $resource = null, array $metadata = []): void
    {
        if (! app()->bound(RequestContext::class)) {
            throw new \RuntimeException(
                'AuditLog::log() requires the CaptureAuditContext middleware. Use AuditLog::fromAuth()->track() in non-HTTP contexts.'
            );
        }

        $context = app(RequestContext::class);

        if (! $context->actorId) {
            throw new \RuntimeException(
                'AuditLog::log() requires an authenticated user. Use AuditLog::actor()->track() for unauthenticated contexts.'
            );
        }

        $payload = [
            'actor_id' => $context->actorId,
            'action' => $action,
        ];

        if ($context->actorName) {
            $payload['actor_name'] = $context->actorName;
        }

        if ($context->actorType) {
            $payload['actor_type'] = $context->actorType;
        }

        if ($context->ipAddress) {
            $payload['ip_address'] = $context->ipAddress;
        }

        if ($context->userAgent) {
            $payload['user_agent'] = $context->userAgent;
        }

        if ($context->sessionId) {
            $payload['session_id'] = $context->sessionId;
        }

        if ($resource instanceof Model) {
            $payload['resource'] = class_basename($resource);
            $payload['resource_id'] = (string) $resource->getKey();

            if (method_exists($resource, 'getAuditName')) {
                $payload['resource_name'] = $resource->getAuditName();
            }
        } elseif (is_string($resource)) {
            $payload['resource'] = $resource;
        }

        if ($metadata) {
            $payload['metadata'] = $metadata;
        }

        $this->track($payload);
    }

    /**
     * Get the underlying HTTP client for direct API calls.
     */
    public function client(): AuditLogClient
    {
        return $this->client;
    }
}
