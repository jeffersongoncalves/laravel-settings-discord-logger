<?php

use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Seeds every setting from the current config (config/discord-logger.php + the `discord` logging channel),
 * so apps already configured through .env keep working unchanged after migrating.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $config = (array) config('discord-logger', []);
        $channel = (array) config('logging.channels.discord', []);

        $this->migrator->inGroup('discord-logger', function (SettingsBlueprint $blueprint) use ($config, $channel): void {
            $blueprint->add('enabled', (bool) ($config['enabled'] ?? true));
            $blueprint->addEncrypted('webhook_url', (string) ($channel['url'] ?? ''));
            $blueprint->add('level', (string) ($channel['level'] ?? 'error'));
            $blueprint->add('from_name', $config['from']['name'] ?? null);
            $blueprint->add('from_avatar_url', $config['from']['avatar_url'] ?? null);
            $blueprint->addEncrypted('level_webhooks', array_filter((array) ($config['webhooks'] ?? [])));
            $blueprint->add('mentions', array_filter((array) ($config['mentions'] ?? [])));
            $blueprint->add('runtime_context', (bool) ($config['runtime_context'] ?? true));
            $blueprint->add('stacktrace', (string) ($config['stacktrace'] ?? 'smart'));
            $blueprint->add('attach_stacktrace', (bool) ($config['attach_stacktrace'] ?? true));
            $blueprint->add('grouping_strategy', (string) ($config['grouping']['strategy'] ?? 'exception'));
            $blueprint->add('grouping_normalize', (bool) ($config['grouping']['normalize'] ?? true));
            $blueprint->add('deduplication_enabled', (bool) ($config['deduplication']['enabled'] ?? true));
            $blueprint->add('deduplication_window', (int) ($config['deduplication']['window'] ?? 300));
            $blueprint->add('deduplication_summary', (bool) ($config['deduplication']['summary'] ?? true));
            $blueprint->add('rate_limit_enabled', (bool) ($config['rate_limit']['enabled'] ?? true));
            $blueprint->add('rate_limit_global_max', (int) ($config['rate_limit']['global']['max'] ?? 30));
            $blueprint->add('rate_limit_global_per_seconds', (int) ($config['rate_limit']['global']['per_seconds'] ?? 60));
            $blueprint->add('rate_limit_fingerprint_max', (int) ($config['rate_limit']['per_fingerprint']['max'] ?? 1));
            $blueprint->add('rate_limit_fingerprint_per_seconds', (int) ($config['rate_limit']['per_fingerprint']['per_seconds'] ?? 300));
            $blueprint->add('queue_enabled', (bool) ($config['queue']['enabled'] ?? true));
        });
    }
};
