<?php

namespace GetImmutable\Jobs;

use GetImmutable\AuditLogClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAuditLogEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public readonly array $payload,
        public readonly bool $isBatch = false,
    ) {
        $this->onQueue('getimmutable');
    }

    public function handle(AuditLogClient $client): void
    {
        if ($this->isBatch) {
            $client->trackBatch($this->payload);
        } else {
            $client->track($this->payload);
        }
    }
}
