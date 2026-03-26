<?php

namespace GetImmutable;

use Illuminate\Database\Eloquent\Model;

class PendingEvent
{
    private ?string $idempotencyKey = null;

    private ?int $version = null;

    /** @var array<int, array<string, mixed>> */
    private array $targets = [];

    private ?string $actionCategory = null;

    private ?string $sessionId = null;

    public function __construct(
        private AuditLogManager $manager,
        private array $actor,
    ) {}

    /**
     * Set an idempotency key to prevent duplicate events.
     */
    public function idempotencyKey(string $key): static
    {
        $this->idempotencyKey = $key;

        return $this;
    }

    /**
     * Set a schema version for the event.
     */
    public function version(int $version): static
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Set the action category for this event.
     */
    public function actionCategory(string $category): static
    {
        $this->actionCategory = $category;

        return $this;
    }

    /**
     * Add a target resource to this event.
     *
     * @param  array<string, mixed>|null  $metadata  Optional metadata for the target.
     */
    public function target(mixed $resource, ?string $id = null, ?string $name = null, ?array $metadata = null): static
    {
        if ($resource instanceof Model) {
            $target = array_filter([
                'type' => class_basename($resource),
                'id' => (string) $resource->getKey(),
                'name' => method_exists($resource, 'getAuditName') ? $resource->getAuditName() : null,
            ]);
        } else {
            $target = array_filter([
                'type' => $resource,
                'id' => $id,
                'name' => $name,
            ]);
        }

        if ($metadata) {
            $target['metadata'] = $metadata;
        }

        $this->targets[] = $target;

        return $this;
    }

    /**
     * Set all targets for this event.
     *
     * @param  array<int, array<string, mixed>>  $targets
     */
    public function targets(array $targets): static
    {
        $this->targets = $targets;

        return $this;
    }

    /**
     * Set the session ID for this event.
     */
    public function session(string $sessionId): static
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Track an audit event.
     *
     * @param  string  $action  The action name (e.g. "user.created", "invoice.paid").
     * @param  Model|string|null  $resource  An Eloquent model, a resource type string, or null.
     * @param  array  $metadata  Additional key-value metadata.
     */
    public function track(string $action, mixed $resource = null, array $metadata = []): void
    {
        $payload = [
            'actor_id' => (string) $this->actor['id'],
            'action' => $action,
        ];

        if (isset($this->actor['name'])) {
            $payload['actor_name'] = $this->actor['name'];
        }

        if (isset($this->actor['type'])) {
            $payload['actor_type'] = $this->actor['type'];
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

        if ($this->actionCategory) {
            $payload['action_category'] = $this->actionCategory;
        }

        if ($this->idempotencyKey) {
            $payload['idempotency_key'] = $this->idempotencyKey;
        }

        if ($this->version) {
            $payload['version'] = $this->version;
        }

        if ($this->targets) {
            $payload['targets'] = $this->targets;
        }

        if ($this->sessionId) {
            $payload['session_id'] = $this->sessionId;
        }

        $this->manager->track($payload);
    }
}
