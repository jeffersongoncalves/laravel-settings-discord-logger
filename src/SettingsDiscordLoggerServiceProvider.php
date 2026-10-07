<?php

namespace JeffersonGoncalves\SettingsDiscordLogger;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use JeffersonGoncalves\DiscordLogger\Logger;
use JeffersonGoncalves\SettingsDiscordLogger\Settings\DiscordLoggerSettings;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SettingsDiscordLoggerServiceProvider extends PackageServiceProvider
{
    public const CHANNEL = 'discord';

    public function configurePackage(Package $package): void
    {
        $package->name('laravel-settings-discord-logger');
    }

    public function packageRegistered(): void
    {
        Config::set('settings.settings', array_merge(
            Config::get('settings.settings', []),
            [DiscordLoggerSettings::class]
        ));
    }

    public function packageBooted(): void
    {
        $migrationsPath = __DIR__.'/../database/settings';

        Config::set('settings.migrations_paths', array_merge(
            [$migrationsPath],
            Config::get('settings.migrations_paths', [])
        ));

        $this->publishes([
            $migrationsPath => database_path('settings'),
        ], 'discord-logger-settings-migrations');

        self::registerChannel();

        // discord-logger:test reads the webhook straight from config/logging.php.
        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            if ($event->command === 'discord-logger:test') {
                DiscordLoggerConfig::apply($event->input->hasParameterOption('--channel')
                    ? (string) $event->input->getParameterOption('--channel')
                    : self::CHANNEL);
            }
        });
    }

    /**
     * Defines the `discord` channel when the app doesn't, and upgrades a channel that points at the plain
     * laravel-discord-logger factory so it reads the database settings too.
     */
    public static function registerChannel(): void
    {
        $key = 'logging.channels.'.self::CHANNEL;
        $channel = Config::get($key);

        if ($channel === null) {
            Config::set($key, ['driver' => 'custom', 'via' => SettingsLogger::class, 'level' => 'error']);
        } elseif (is_array($channel) && ($channel['via'] ?? null) === Logger::class) {
            Config::set("{$key}.via", SettingsLogger::class);
        }
    }
}
