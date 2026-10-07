<div class="filament-hidden">

![Laravel Settings Discord Logger](https://raw.githubusercontent.com/jeffersongoncalves/laravel-settings-discord-logger/main/art/jeffersongoncalves-laravel-settings-discord-logger.png)

</div>

# Laravel Settings Discord Logger

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/laravel-settings-discord-logger.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-settings-discord-logger)
[![Tests](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/laravel-settings-discord-logger/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/jeffersongoncalves/laravel-settings-discord-logger/actions?query=workflow%3ATests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/laravel-settings-discord-logger.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-settings-discord-logger)

Manage [jeffersongoncalves/laravel-discord-logger](https://github.com/jeffersongoncalves/laravel-discord-logger) from the database instead of `.env`. The webhook URL, minimum level, mentions, per-level webhooks, deduplication, rate limits and the rest of the day-to-day options are stored with [spatie/laravel-settings](https://github.com/spatie/laravel-settings) and injected into the logger at runtime — change them without a deploy.

`laravel-discord-logger` itself is untouched: this package only layers the stored values on top of its config. For an admin UI, use [jeffersongoncalves/filament-discord-logger](https://github.com/jeffersongoncalves/filament-discord-logger).

## Installation

```bash
composer require jeffersongoncalves/laravel-settings-discord-logger
php artisan migrate
```

The settings migration seeds every value from your current config (`config/discord-logger.php` and the `discord` logging channel), so an app already configured through `.env` keeps working exactly as before.

## How it works

- A `discord` logging channel is registered automatically. If your `config/logging.php` already defines `discord` with `JeffersonGoncalves\DiscordLogger\Logger`, it is switched to `JeffersonGoncalves\SettingsDiscordLogger\SettingsLogger`; a channel using any other driver is left alone.
- When the channel is first used in a request or job, the stored settings are merged over the config and handed to laravel-discord-logger. Requests that never log to Discord never read the settings.
- An empty stored webhook keeps the URL from `config/logging.php` (`.env`), so you can keep secrets in the environment and only tune behaviour from the database.
- If the settings can't be loaded (table not migrated yet, database down) the plain config is used — logging never breaks the app.
- `php artisan discord-logger:test` uses the stored webhook too.
- Long-running processes (queue workers, Octane) keep the channel they already resolved — run `php artisan queue:restart` (or reload Octane) after changing the settings.

Add `discord` to your stack as usual:

```php
// config/logging.php
'stack' => [
    'driver' => 'stack',
    'channels' => ['daily', 'discord'],
],
```

## Settings

```php
use JeffersonGoncalves\SettingsDiscordLogger\Settings\DiscordLoggerSettings;

$settings = app(DiscordLoggerSettings::class);
$settings->webhook_url = 'https://discord.com/api/webhooks/...';
$settings->level = 'error';
$settings->mentions = ['CRITICAL' => '@here'];
$settings->save();
```

| Setting | Maps to |
|---|---|
| `enabled` | `discord-logger.enabled` |
| `webhook_url` *(encrypted)* | channel `url` (empty = keep `.env`) |
| `level` | channel `level` |
| `from_name`, `from_avatar_url` | `discord-logger.from.*` |
| `level_webhooks` *(encrypted)* | `discord-logger.webhooks` (`LEVEL => url`) |
| `mentions` | `discord-logger.mentions` (`LEVEL => @here / <@&role> / <@user>`) |
| `runtime_context` | `discord-logger.runtime_context` |
| `stacktrace`, `attach_stacktrace` | `discord-logger.stacktrace`, `discord-logger.attach_stacktrace` |
| `grouping_strategy`, `grouping_normalize` | `discord-logger.grouping.*` |
| `deduplication_enabled`, `deduplication_window`, `deduplication_summary` | `discord-logger.deduplication.*` |
| `rate_limit_enabled`, `rate_limit_global_max`, `rate_limit_global_per_seconds`, `rate_limit_fingerprint_max`, `rate_limit_fingerprint_per_seconds` | `discord-logger.rate_limit.*` |
| `queue_enabled` | `discord-logger.queue.enabled` |

Code-level options — converter, redaction keys and patterns, context resolver, cache store, queue connection, colors and emojis — stay in `config/discord-logger.php`.

Publish the settings migration if you want to customise it:

```bash
php artisan vendor:publish --tag=discord-logger-settings-migrations
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Jefferson Gonçalves](https://github.com/jeffersongoncalves)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
