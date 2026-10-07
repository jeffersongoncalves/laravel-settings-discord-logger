<?php

use Illuminate\Foundation\Testing\WithConsoleEvents;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use JeffersonGoncalves\DiscordLogger\Logger;
use JeffersonGoncalves\SettingsDiscordLogger\DiscordLoggerConfig;
use JeffersonGoncalves\SettingsDiscordLogger\Settings\DiscordLoggerSettings;
use JeffersonGoncalves\SettingsDiscordLogger\SettingsDiscordLoggerServiceProvider;
use JeffersonGoncalves\SettingsDiscordLogger\SettingsLogger;

// Console events are off in unit tests by default; discord-logger:test relies on CommandStarting.
uses(WithConsoleEvents::class);

const SETTINGS_WEBHOOK = 'https://discord.com/api/webhooks/1/settings-token';
const ENV_WEBHOOK = 'https://discord.com/api/webhooks/2/env-token';

function saveSettings(array $values): void
{
    $settings = app(DiscordLoggerSettings::class);
    foreach ($values as $key => $value) {
        $settings->{$key} = $value;
    }
    $settings->save();
}

it('registers the discord channel with the settings-aware factory', function (): void {
    expect(config('logging.channels.discord'))->toMatchArray(['driver' => 'custom', 'via' => SettingsLogger::class]);
});

it('upgrades a channel that points at the plain laravel-discord-logger factory', function (): void {
    Config::set('logging.channels.discord', ['driver' => 'custom', 'via' => Logger::class, 'level' => 'critical', 'url' => ENV_WEBHOOK]);

    SettingsDiscordLoggerServiceProvider::registerChannel();

    expect(config('logging.channels.discord'))->toBe(['driver' => 'custom', 'via' => SettingsLogger::class, 'level' => 'critical', 'url' => ENV_WEBHOOK]);
});

it('leaves a channel with another factory alone', function (): void {
    Config::set('logging.channels.discord', ['driver' => 'slack', 'url' => ENV_WEBHOOK]);

    SettingsDiscordLoggerServiceProvider::registerChannel();

    expect(config('logging.channels.discord'))->toBe(['driver' => 'slack', 'url' => ENV_WEBHOOK]);
});

it('seeds the settings from the existing config so .env setups keep working', function (): void {
    $settings = app(DiscordLoggerSettings::class);

    expect($settings->enabled)->toBeTrue()
        ->and($settings->level)->toBe('error')
        ->and($settings->stacktrace)->toBe('smart')
        ->and($settings->queue_enabled)->toBeFalse()
        ->and($settings->deduplication_window)->toBe(300)
        ->and($settings->webhook_url)->toBe('');
});

it('stores the webhooks encrypted', function (): void {
    saveSettings(['webhook_url' => SETTINGS_WEBHOOK, 'level_webhooks' => ['CRITICAL' => SETTINGS_WEBHOOK]]);

    $payloads = DB::table('settings')->whereIn('name', ['webhook_url', 'level_webhooks'])->pluck('payload')->implode(' ');

    expect($payloads)->not->toContain('settings-token');
});

it('layers the settings on top of the config, keeping the .env webhook when the stored one is empty', function (): void {
    saveSettings(['level' => 'warning', 'mentions' => ['CRITICAL' => '@here'], 'deduplication_window' => 60]);

    $merged = DiscordLoggerConfig::merge(array_replace((array) config('discord-logger'), ['url' => ENV_WEBHOOK, 'level' => 'error']));

    expect($merged['url'])->toBe(ENV_WEBHOOK)
        ->and($merged['level'])->toBe('warning')
        ->and($merged['mentions'])->toBe(['CRITICAL' => '@here'])
        ->and($merged['deduplication']['window'])->toBe(60)
        ->and($merged['redact'])->toContain('password'); // code-level options survive
});

it('replaces per-level lists instead of merging them', function (): void {
    saveSettings(['level_webhooks' => ['ERROR' => SETTINGS_WEBHOOK]]);

    $merged = DiscordLoggerConfig::merge(['webhooks' => ['CRITICAL' => ENV_WEBHOOK]]);

    expect($merged['webhooks'])->toBe(['ERROR' => SETTINGS_WEBHOOK]);
});

it('sends logs to the webhook stored in the database', function (): void {
    Http::fake(['discord.com/*' => Http::response(null, 204)]);
    saveSettings(['webhook_url' => SETTINGS_WEBHOOK, 'level' => 'warning']);

    Log::channel('discord')->warning('Disk almost full');

    Http::assertSent(fn ($request) => str_starts_with($request->url(), SETTINGS_WEBHOOK));
});

it('respects the minimum level and the enabled switch from the database', function (): void {
    Http::fake(['discord.com/*' => Http::response(null, 204)]);
    saveSettings(['webhook_url' => SETTINGS_WEBHOOK, 'level' => 'critical']);

    Log::channel('discord')->error('Not important enough');
    Http::assertNothingSent();

    app('log')->forgetChannel('discord');
    saveSettings(['level' => 'debug', 'enabled' => false]);

    Log::channel('discord')->critical('Disabled');
    Http::assertNothingSent();
});

it('falls back to the plain config when the settings table is missing', function (): void {
    Http::fake(['discord.com/*' => Http::response(null, 204)]);
    Schema::drop('settings');
    Config::set('logging.channels.discord.url', ENV_WEBHOOK);

    Log::channel('discord')->error('Still delivered');

    Http::assertSent(fn ($request) => str_starts_with($request->url(), ENV_WEBHOOK));
});

it('points discord-logger:test at the stored webhook', function (): void {
    Http::fake(['discord.com/*' => Http::response(null, 204)]);
    saveSettings(['webhook_url' => SETTINGS_WEBHOOK]);

    $this->artisan('discord-logger:test')->assertSuccessful();

    Http::assertSent(fn ($request) => str_starts_with($request->url(), SETTINGS_WEBHOOK));
});
