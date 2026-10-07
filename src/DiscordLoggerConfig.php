<?php

namespace JeffersonGoncalves\SettingsDiscordLogger;

use Illuminate\Support\Facades\Config;
use JeffersonGoncalves\SettingsDiscordLogger\Settings\DiscordLoggerSettings;
use Throwable;

/**
 * Layers the stored settings on top of config/discord-logger.php and the logging channel.
 */
class DiscordLoggerConfig
{
    /** Channel-only keys that don't belong in config/discord-logger.php. */
    private const CHANNEL_KEYS = ['url', 'level', 'driver', 'via', 'bubble', 'name'];

    /**
     * Settings merged into the given base config, or the base untouched when the settings can't be
     * loaded (settings table not migrated yet, database down...) — logging must never break the app.
     *
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    public static function merge(array $base): array
    {
        try {
            $overrides = app(DiscordLoggerSettings::class)->toLoggerConfig();
        } catch (Throwable) {
            return $base;
        }

        // Lists are replaced, not merged key by key, so a level removed in the panel really goes away.
        unset($base['webhooks'], $base['mentions']);

        return array_replace_recursive($base, $overrides);
    }

    /**
     * Keeps config('discord-logger') in sync with a merged config, for the parts of laravel-discord-logger
     * that read config() directly (HTTP timeouts, fallback reporting).
     *
     * @param  array<string, mixed>  $merged
     */
    public static function syncPackageConfig(array $merged): void
    {
        Config::set('discord-logger', array_diff_key($merged, array_flip(self::CHANNEL_KEYS)));
    }

    /**
     * Applies the settings to the runtime config: config('discord-logger') plus the channel's url and level
     * (used by the discord-logger:test command, which reads the webhook from config/logging.php).
     */
    public static function apply(string $channel = 'discord'): void
    {
        $merged = self::merge(array_replace(
            (array) Config::get('discord-logger', []),
            (array) Config::get("logging.channels.{$channel}", []),
        ));

        self::syncPackageConfig($merged);

        foreach (['url', 'level'] as $key) {
            if (isset($merged[$key])) {
                Config::set("logging.channels.{$channel}.{$key}", $merged[$key]);
            }
        }
    }
}
