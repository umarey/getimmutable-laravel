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
    | HTTP Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout in seconds for API requests.
    |
    */

    'timeout' => 5,

];
