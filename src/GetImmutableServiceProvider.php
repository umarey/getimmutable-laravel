<?php

namespace GetImmutable;

use GetImmutable\Middleware\CaptureAuditContext;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;

class GetImmutableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/getimmutable.php', 'getimmutable');

        $this->app->singleton(AuditLogClient::class, function ($app) {
            $config = $app['config']['getimmutable'];

            return new AuditLogClient(
                apiKey: $config['api_key'] ?? '',
                baseUrl: $config['base_url'],
                timeout: $config['timeout'],
            );
        });

        $this->app->singleton('auditlog', function ($app) {
            return new AuditLogManager(
                client: $app->make(AuditLogClient::class),
                async: $app['config']['getimmutable']['async'],
                autoSession: $app['config']['getimmutable']['auto_session'] ?? false,
            );
        });

        $this->app->alias('auditlog', AuditLogManager::class);

        $this->app->scoped(RequestContext::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/getimmutable.php' => config_path('getimmutable.php'),
        ], 'getimmutable-config');

        if ($this->app['config']['getimmutable']['auto_context'] ?? false) {
            $this->app->make(Kernel::class)->pushMiddleware(CaptureAuditContext::class);
        }
    }
}
