<?php

namespace JeffersonGoncalves\SettingsDiscordLogger\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JeffersonGoncalves\DiscordLogger\DiscordLoggerServiceProvider;
use JeffersonGoncalves\SettingsDiscordLogger\SettingsDiscordLoggerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelSettings\LaravelSettingsServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group');
            $table->string('name');
            $table->boolean('locked')->default(false);
            $table->json('payload');
            $table->timestamps();
            $table->unique(['group', 'name']);
        });

        $this->runSettingsMigration();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelSettingsServiceProvider::class,
            DiscordLoggerServiceProvider::class,
            SettingsDiscordLoggerServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('discord-logger.queue.enabled', false);
        $app['config']->set('discord-logger.deduplication.enabled', false);
        $app['config']->set('discord-logger.rate_limit.enabled', false);
    }

    /** Runs the package's settings migration, seeding from the current config like an app would. */
    protected function runSettingsMigration(): void
    {
        $migration = include __DIR__.'/../database/settings/2026_10_07_000000_create_discord_logger_settings.php';
        $migration->up();
    }
}
