<?php

namespace GetImmutable\Concerns;

use GetImmutable\AuditLog;

trait TracksEvents
{
    public static function bootTracksEvents(): void
    {
        $events = static::$trackedEvents ?? ['created', 'updated', 'deleted', 'restored', 'forceDeleted'];

        $categoryMap = [
            'created' => 'create',
            'updated' => 'update',
            'deleted' => 'delete',
            'restored' => 'update',
            'forceDeleted' => 'delete',
        ];

        foreach ($events as $event) {
            static::$event(function ($model) use ($event, $categoryMap) {
                $actorId = auth()->id() ?? 'system';
                $resource = class_basename(static::class);

                $payload = [
                    'actor_id' => (string) $actorId,
                    'action' => strtolower($resource).'.'.$event,
                    'resource' => $resource,
                    'resource_id' => (string) $model->getKey(),
                ];

                if (method_exists($model, 'getAuditName')) {
                    $payload['resource_name'] = $model->getAuditName();
                }

                if (isset($categoryMap[$event])) {
                    $payload['action_category'] = $categoryMap[$event];
                }

                AuditLog::track($payload);
            });
        }
    }
}
