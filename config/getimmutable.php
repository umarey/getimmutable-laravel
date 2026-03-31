<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | Your Immutable API key, found in the dashboard under API Keys.
    |
    */

    'api_key' => env('GETIMMUTABLE_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL of the Immutable API. Override for self-hosted or local dev.
    |
    */

    'base_url' => env('GETIMMUTABLE_BASE_URL', 'https://api.getimmutable.com'),

    /*
    |--------------------------------------------------------------------------
    | Async Mode
    |--------------------------------------------------------------------------
    |
    | When true, events are dispatched to a queue job instead of sent inline.
    | Make sure your queue worker processes the "getimmutable" queue.
    |
    */

    'async' => env('GETIMMUTABLE_ASYNC', true),

    /*
    |--------------------------------------------------------------------------
    | Auto Session
    |--------------------------------------------------------------------------
    |
    | When true, the SDK automatically reads session()->getId() and attaches
    | it to every tracked event. Opt-in because it couples to Laravel's
    | session system. Only works in HTTP request context (not console/queue).
    |
    */

    'auto_session' => env('GETIMMUTABLE_AUTO_SESSION', false),

    /*
    |--------------------------------------------------------------------------
    | Auto Context
    |--------------------------------------------------------------------------
    |
    | When true, the CaptureAuditContext middleware is automatically registered
    | in the web middleware group. It captures the authenticated user, IP
    | address, user agent, and session ID so you can use the one-liner:
    |
    |     AuditLog::log('action.name', $resource, ['key' => 'value']);
    |
    | Set to false (default) to register the middleware manually on specific
    | route groups, or to skip it entirely and use fromAuth()->track() instead.
    |
    */

    'auto_context' => env('GETIMMUTABLE_AUTO_CONTEXT', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout in seconds for API requests.
    |
    */

    'timeout' => 5,

];
