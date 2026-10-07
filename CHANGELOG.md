# Changelog

All notable changes to `laravel-settings-discord-logger` will be documented in this file.

## 1.0.0 - 2026-10-07

First release.

Database-backed settings for [jeffersongoncalves/laravel-discord-logger](https://github.com/jeffersongoncalves/laravel-discord-logger) via spatie/laravel-settings.

- `DiscordLoggerSettings`: enabled, webhook URL (encrypted), level, sender, per-level webhooks (encrypted) and mentions, runtime context, stacktrace, grouping, deduplication, rate limits, queue
- `SettingsLogger` channel factory merges the stored values over the config when the `discord` channel is first used
- Registers the `discord` channel automatically and upgrades a channel that used the plain `Logger`
- Empty stored webhook keeps `LOG_DISCORD_WEBHOOK_URL`; falls back to the config when the settings table is missing
- Settings migration seeded from the current config, so `.env` setups keep working
- `discord-logger:test` uses the stored webhook

Requires PHP 8.2+ and Laravel 12.61+ or 13.
