<?php

namespace GetImmutable;

use Illuminate\Support\Facades\Http;

class AuditLogClient
{
    public function __construct(
        private string $apiKey,
        private string $baseUrl,
        private int $timeout = 5,
    ) {}

    /**
     * Send a single event to the Immutable API.
     *
     * @param  array<string, mixed>  $payload
     * @return array{id: string, status: string}
     */
    public function track(array $payload): array
    {
        return $this->request('POST', '/api/v1/events', $payload);
    }

    /**
     * Send a batch of events to the Immutable API.
     *
     * @param  array<int, array<string, mixed>>  $events
     * @return array{events: array<int, array{id: string, status: string}>}
     */
    public function trackBatch(array $events): array
    {
        return $this->request('POST', '/api/v1/events/batch', ['events' => $events]);
    }

    /**
     * Query events with filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array{data: array, pagination: array{has_more: bool, next_cursor: ?string, total: int}}
     */
    public function getEvents(array $filters = []): array
    {
        return $this->get('/api/v1/events', $filters);
    }

    /**
     * Get a single event by ID.
     *
     * @return array{data: array<string, mixed>}
     */
    public function getEvent(string $id): array
    {
        return $this->get("/api/v1/events/{$id}");
    }

    /**
     * Verify the hash chain integrity for a time range.
     *
     * @return array{valid: bool, events_checked: int, breaks: array}
     */
    public function verify(?string $from = null, ?string $to = null, ?int $limit = null): array
    {
        return $this->get('/api/v1/verify', array_filter(['from' => $from, 'to' => $to, 'limit' => $limit]));
    }

    /**
     * Create a scoped viewer token for the embeddable component.
     *
     * @param  array{tenant_id?: string, actor_id?: string, ttl?: int}  $options
     * @return array{token: string, expires_at: int}
     */
    public function createViewerToken(array $options = []): array
    {
        return $this->request('POST', '/api/v1/viewer-token', $options);
    }

    /**
     * Query triggered alerts.
     *
     * @param  array<string, mixed>  $filters
     * @return array{data: array}
     */
    public function getAlerts(array $filters = []): array
    {
        return $this->get('/api/v1/alerts', $filters);
    }

    /**
     * Trigger a CSV export.
     *
     * @param  array<string, mixed>  $filters
     * @return array{id: string, status: string}
     */
    public function createExport(array $filters = []): array
    {
        return $this->request('POST', '/api/v1/exports', $filters);
    }

    /**
     * Check the status of an export.
     *
     * @return array{data: array<string, mixed>}
     */
    public function getExport(string $id): array
    {
        return $this->get("/api/v1/exports/{$id}");
    }

    /**
     * List blockchain anchors for the workspace.
     *
     * @return array{data: array}
     */
    public function getAnchors(?int $limit = null): array
    {
        return $this->get('/api/v1/anchors', array_filter(['limit' => $limit]));
    }

    /**
     * Get details of a specific anchor.
     *
     * @return array{data: array<string, mixed>}
     */
    public function getAnchor(string $id): array
    {
        return $this->get("/api/v1/anchors/{$id}");
    }

    /**
     * Verify an anchor by recomputing the Merkle root.
     *
     * @return array{data: array{anchor_id: string, valid: bool, chain_valid: bool, events_count: int}}
     */
    public function verifyAnchor(string $id): array
    {
        return $this->get("/api/v1/anchors/{$id}/verify");
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $data): array
    {
        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->timeout($this->timeout)
            ->send($method, rtrim($this->baseUrl, '/').$path, ['json' => $data]);

        if ($response->failed()) {
            throw new GetImmutableException(
                "Immutable API request failed: {$response->status()} {$response->body()}",
                $response->status(),
                $response->json() ?? [],
            );
        }

        return $response->json();
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $path, array $query = []): array
    {
        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->timeout($this->timeout)
            ->get(rtrim($this->baseUrl, '/').$path, $query);

        if ($response->failed()) {
            throw new GetImmutableException(
                "Immutable API request failed: {$response->status()} {$response->body()}",
                $response->status(),
                $response->json() ?? [],
            );
        }

        return $response->json();
    }
}
