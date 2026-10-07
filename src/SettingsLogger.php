<?php

namespace JeffersonGoncalves\SettingsDiscordLogger;

use Illuminate\Support\Facades\Config;
use JeffersonGoncalves\DiscordLogger\Logger;
use Monolog\Logger as Monolog;

/**
 * Logging channel factory: same as laravel-discord-logger's Logger, with the database settings layered on
 * top. Settings are read when the channel is first resolved, so requests that never log to Discord never
 * touch them.
 *
 * config/logging.php (registered automatically when the app doesn't define a `discord` channel):
 *
 *   'discord' => [
 *       'driver' => 'custom',
 *       'via'    => \JeffersonGoncalves\SettingsDiscordLogger\SettingsLogger::class,
 *       'level'  => 'error',
 *       'url'    => env('LOG_DISCORD_WEBHOOK_URL'),
 *   ],
 */
class SettingsLogger
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __invoke(array $config): Monolog
    {
        $merged = DiscordLoggerConfig::merge(array_replace((array) Config::get('discord-logger', []), $config));

        DiscordLoggerConfig::syncPackageConfig($merged);

        return (new Logger)($merged);
    }
}
