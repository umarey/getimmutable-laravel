# Immutable Laravel SDK

Laravel SDK for the [Immutable](https://getimmutable.com) audit log API.

## Installation

```bash
composer require getimmutable/laravel
```

The package auto-discovers its service provider. Publish the config file:

```bash
php artisan vendor:publish --tag=getimmutable-config
```

## Configuration

Add the following to your `.env` file:

```env
GETIMMUTABLE_API_KEY=your-api-key
GETIMMUTABLE_BASE_URL=https://api.getimmutable.com
GETIMMUTABLE_ASYNC=true
```

| Variable | Default | Description |
|---|---|---|
| `GETIMMUTABLE_API_KEY` | — | Your Immutable API key |
| `GETIMMUTABLE_BASE_URL` | `https://api.getimmutable.com` | API base URL |
| `GETIMMUTABLE_ASYNC` | `true` | Dispatch events via queue job |

## Queue Configuration

> **Important:** Async mode (the default) dispatches events to the `getimmutable` queue. If your queue worker is not processing this queue, events will silently sit in the queue and never be sent.

Make sure your queue worker includes the `getimmutable` queue:

```bash
php artisan queue:work --queue=getimmutable,default
```

Or if you use Laravel Horizon, add a supervisor entry in `config/horizon.php`:

```php
'environments' => [
    'production' => [
        'supervisor-getimmutable' => [
            'connection' => 'redis',
            'queue' => ['getimmutable'],
            'minProcesses' => 1,
            'maxProcesses' => 3,
            'balanceMaxShift' => 1,
            'balanceCooldown' => 3,
            'tries' => 3,
        ],
    ],
],
```

For local development or testing, set `GETIMMUTABLE_ASYNC=false` to send events synchronously (no queue required).

## Usage

### Raw Payload

```php
use GetImmutable\AuditLog;

AuditLog::track([
    'actor_id' => 'usr_123',
    'action' => 'project.created',
    'resource' => 'Project',
    'resource_id' => 'prj_456',
    'metadata' => ['ip' => request()->ip()],
]);
```

### Fluent Builder (from Auth)

```php
use GetImmutable\AuditLog;

// Uses the currently authenticated user as the actor
AuditLog::fromAuth()->track('project.created', $project, [
    'ip' => request()->ip(),
]);
```

### Fluent Builder (explicit Actor)

```php
use GetImmutable\AuditLog;

AuditLog::actor($user)->track('invoice.paid', $invoice, [
    'amount' => 9900,
]);
```

### Resource Handling

The `track()` method on the builder accepts three resource types:

- **Eloquent Model** — auto-extracts `resource`, `resource_id`, and `resource_name` (via optional `getAuditName()` method)
- **String** — treated as the resource type name (e.g. `'Project'`)
- **null** — no resource fields are included

```php
// Eloquent model
AuditLog::fromAuth()->track('project.deleted', $project);

// Plain string
AuditLog::fromAuth()->track('report.exported', 'Report');

// No resource
AuditLog::fromAuth()->track('user.logged_in');
```

### Batch Events

```php
use GetImmutable\AuditLog;

AuditLog::trackBatch([
    ['actor_id' => 'usr_1', 'action' => 'item.created'],
    ['actor_id' => 'usr_2', 'action' => 'item.updated'],
]);
```

## Automatic Eloquent Tracking

Add the `TracksEvents` trait to any Eloquent model to automatically track `created`, `updated`, and `deleted` events:

```php
use GetImmutable\Concerns\TracksEvents;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use TracksEvents;
}
```

Customize which events to track:

```php
class Project extends Model
{
    use TracksEvents;

    protected static array $trackedEvents = ['created', 'deleted'];
}
```

Events are recorded as `project.created`, `project.updated`, `project.deleted` (lowercased model name + event).

## Testing

In your test suite, you can either:

1. Set `GETIMMUTABLE_ASYNC=false` and use `Http::fake()` to intercept API calls
2. Set `GETIMMUTABLE_ASYNC=true` and use `Queue::fake()` to assert jobs were dispatched

```php
use GetImmutable\AuditLog;
use GetImmutable\Jobs\SendAuditLogEvent;
use Illuminate\Support\Facades\Queue;

Queue::fake();

AuditLog::track(['actor_id' => 'test', 'action' => 'test.action']);

Queue::assertPushed(SendAuditLogEvent::class);
```
