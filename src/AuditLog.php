<?php

namespace GetImmutable;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void track(array $payload)
 * @method static void trackBatch(array $events)
 * @method static PendingEvent fromAuth()
 * @method static PendingEvent actor(\Illuminate\Contracts\Auth\Authenticatable|array $actor)
 * @method static AuditLogClient client()
 *
 * @see AuditLogManager
 */
class AuditLog extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'auditlog';
    }
}
