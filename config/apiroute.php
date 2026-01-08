<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | API Versions
    |--------------------------------------------------------------------------
    |
    | Define your API versions here. Each version has its own route file,
    | middleware, and lifecycle status.
    |
    */
    'versions' => [
        'v1' => [
            'routes' => base_path('routes/api/v1.php'),
            'middleware' => [],
            'status' => 'active',
            'deprecated_at' => null,
            'sunset_at' => null,
            'successor' => null,
            'documentation' => null,
            'rate_limit' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Detection Strategy
    |--------------------------------------------------------------------------
    |
    | How the API version should be detected from incoming requests.
    | Supported: "uri", "header", "query", "accept"
    |
    */
    'strategy' => env('API_VERSION_STRATEGY', 'uri'),

    /*
    |--------------------------------------------------------------------------
    | Strategy Configuration
    |--------------------------------------------------------------------------
    */
    'strategies' => [
        'uri' => [
            'prefix' => 'api',           // API prefix for versioned routes
            'pattern' => 'v{version}',   // v1, v2, etc.
        ],
        'header' => [
            'name' => 'X-API-Version',   // X-API-Version: 1
        ],
        'query' => [
            'parameter' => 'api_version', // ?api_version=1
        ],
        'accept' => [
            'pattern' => 'application/vnd.{vendor}.{version}+json',
            'vendor' => env('API_VENDOR', 'api'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Version
    |--------------------------------------------------------------------------
    |
    | Version to use when none is specified in the request.
    | Set to 'latest' to always use the most recent non-beta version.
    |
    */
    'default_version' => env('API_DEFAULT_VERSION', 'latest'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Behavior
    |--------------------------------------------------------------------------
    |
    | When a route doesn't exist in the requested version, should we
    | fallback to a previous version?
    |
    */
    'fallback' => [
        'enabled' => true,
        'strategy' => 'previous',  // 'previous', 'latest', 'none'
        'add_header' => true,      // Add X-API-Version-Fallback header
    ],

    /*
    |--------------------------------------------------------------------------
    | Sunset Behavior
    |--------------------------------------------------------------------------
    |
    | How to handle requests to sunset (end-of-life) versions.
    |
    */
    'sunset' => [
        'action' => 'reject',      // 'reject', 'warn', 'allow'
        'status_code' => 410,      // HTTP Gone
        'include_migration_url' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Headers
    |--------------------------------------------------------------------------
    |
    | Automatically add version-related headers to responses.
    |
    */
    'headers' => [
        'enabled' => true,
        'include' => [
            'version' => true,           // X-API-Version
            'status' => true,            // X-API-Version-Status
            'deprecation' => true,       // Deprecation (RFC 8594)
            'sunset' => true,            // Sunset (RFC 7231)
            'successor_link' => true,    // Link rel="successor-version"
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Usage Tracking
    |--------------------------------------------------------------------------
    |
    | Track API version usage for analytics and monitoring.
    |
    */
    'tracking' => [
        'enabled' => env('API_VERSION_TRACKING', false),
        'driver' => 'database',      // 'database', 'redis', 'null'
        'table' => 'api_version_stats',
        'aggregate' => 'hourly',     // 'realtime', 'hourly', 'daily'
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Get notified about version lifecycle events.
    |
    */
    'notifications' => [
        'enabled' => false,
        'channels' => ['mail'],
        'recipients' => [],
        'events' => [
            'approaching_deprecation' => [7, 1],  // days before
            'approaching_sunset' => [30, 7, 1],
            'high_deprecated_usage' => 50,        // percentage threshold
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentation
    |--------------------------------------------------------------------------
    |
    | URLs for API documentation (used in error responses).
    |
    */
    'documentation' => [
        'base_url' => env('API_DOCS_URL'),
        'migration_guides' => [
            // 'v1' => 'https://docs.example.com/api/migration/v1-to-v2',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware configuration for API version handling.
    |
    */
    'middleware' => [
        'group' => 'api',            // Apply to this middleware group
        'alias' => 'api.version',    // Middleware alias
    ],
];
