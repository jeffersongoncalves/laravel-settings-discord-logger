<?php

namespace JeffersonGoncalves\SettingsDiscordLogger\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Database-backed values for jeffersongoncalves/laravel-discord-logger. Only what makes sense to change from an
 * admin panel lives here; code-level options (converter, redaction patterns, colors, resolvers) stay in
 * config/discord-logger.php.
 */
class DiscordLoggerSettings extends Settings
{
    public bool $enabled;

    /** Default channel webhook. Empty = keep the URL from config/logging.php (.env). */
    public string $webhook_url;

    /** Minimum Monolog level sent to Discord (debug ... emergency). */
    public string $level;

    public ?string $from_name;

    public ?string $from_avatar_url;

    /** @var array<string, string> UPPERCASE level => webhook URL */
    public array $level_webhooks;

    /** @var array<string, string> UPPERCASE level => mention (@here, <@&role>, <@user>) */
    public array $mentions;

    public bool $runtime_context;

    /** smart | full | none */
    public string $stacktrace;

    public bool $attach_stacktrace;

    /** message | level_message | exception */
    public string $grouping_strategy;

    public bool $grouping_normalize;

    public bool $deduplication_enabled;

    public int $deduplication_window;

    public bool $deduplication_summary;

    public bool $rate_limit_enabled;

    public int $rate_limit_global_max;

    public int $rate_limit_global_per_seconds;

    public int $rate_limit_fingerprint_max;

    public int $rate_limit_fingerprint_per_seconds;

    public bool $queue_enabled;

    public static function group(): string
    {
        return 'discord-logger';
    }

    /** @return list<string> */
    public static function encrypted(): array
    {
        return ['webhook_url', 'level_webhooks'];
    }

    /**
     * Values in the shape of config/discord-logger.php plus the channel keys (url, level).
     *
     * @return array<string, mixed>
     */
    public function toLoggerConfig(): array
    {
        return array_filter([
            'url' => trim($this->webhook_url) !== '' ? trim($this->webhook_url) : null,
        ], fn ($value) => $value !== null) + [
            'enabled' => $this->enabled,
            'level' => $this->level,
            'from' => array_filter([
                'name' => $this->from_name,
                'avatar_url' => $this->from_avatar_url,
            ], fn ($value) => $value !== null && $value !== ''),
            'webhooks' => array_filter($this->level_webhooks, fn (string $url) => trim($url) !== ''),
            'mentions' => array_filter($this->mentions, fn (string $mention) => trim($mention) !== ''),
            'runtime_context' => $this->runtime_context,
            'stacktrace' => $this->stacktrace,
            'attach_stacktrace' => $this->attach_stacktrace,
            'grouping' => [
                'strategy' => $this->grouping_strategy,
                'normalize' => $this->grouping_normalize,
            ],
            'deduplication' => [
                'enabled' => $this->deduplication_enabled,
                'window' => $this->deduplication_window,
                'summary' => $this->deduplication_summary,
            ],
            'rate_limit' => [
                'enabled' => $this->rate_limit_enabled,
                'global' => [
                    'max' => $this->rate_limit_global_max,
                    'per_seconds' => $this->rate_limit_global_per_seconds,
                ],
                'per_fingerprint' => [
                    'max' => $this->rate_limit_fingerprint_max,
                    'per_seconds' => $this->rate_limit_fingerprint_per_seconds,
                ],
            ],
            'queue' => [
                'enabled' => $this->queue_enabled,
            ],
        ];
    }
}
