# CI Pulse

CodeIgniter 4 package for monitoring application health and performance. This is a port of [Laravel Pulse](https://pulse.laravel.com/) to the CodeIgniter 4 framework.

## Requirements

- PHP 7.4+
- CodeIgniter 4.0+

## Installation

1.  **Install via Composer:**

    ```bash
    composer require iqlearning/pulse
    ```

2.  **Run Migrations:**

    Run the migrations to create the necessary tables (`pulse_entries`, `pulse_values`, `pulse_aggregates`).

    ```bash
    php spark migrate -n Iqlearning\Pulse
    ```

## Configuration

### 1. Routes

Add the Pulse routes to your `app/Config/Routes.php` file (or a specific route group).

```php
// app/Config/Routes.php

$routes->group('pulse', ['namespace' => 'CodeIgniter\Controllers'], function($routes) {
    $routes->get('/', 'PulseController::index');
    $routes->get('check', 'PulseController::check'); // For Scheduled Tasks / Cron
    $routes->get('stats', 'PulseController::stats'); // Dashboard Data
});
```

_Note: Make sure the `namespace` matches where `PulseController` is located (currently `CodeIgniter\Controllers`)._

### 2. Filters (Middleware)

To record request times and system stats during requests, enable the filter. The package registers the `pulse` alias automatically.

Add it to your `app/Config/Filters.php`:

```php
public $globals = [
    'before' => [
        // ...
    ],
    'after' => [
        'pulse' => ['except' => ['pulse/*']], // Exclude pulse own routes
        // ...
    ],
];
```

### 3. Environment Variables

You can configure the data retention policy in your `.env` file:

```dotenv
# Data retention in days (default: 1)
pulse.metricsTTL = 1
```

## Usage

### Dashboard

Visit `/pulse` in your browser to view the dashboard.

### Scheduled Recording

To capture system stats (CPU, Memory) and perform cleanup of old data, you should set up a cron job or scheduled task to call the `check` endpoint periodically (e.g., every minute).

**Via crontab:**

```bash
* * * * * /path/to/php /path/to/project/public/index.php pulse/check
```

**Or via URL:**

```bash
* * * * * curl http://your-domain.com/pulse/check
```

## Monitoring Features

- **System Stats:** CPU and Memory usage monitoring (Server support: Windows via `wmic`, limited Linux support).
- **Slow Requests:** Tracks requests taking longer than 1 second.
- **Slow Queries:** Tracks database queries taking longer than 50ms.
- **Exceptions:** Logs application exceptions with stack traces.
- **Request Duration:** detailed breakdown of request performance.

## License

MIT License.
