## Laravel Settings Discord Logger

Stores jeffersongoncalves/laravel-discord-logger options in the database (spatie/laravel-settings) and injects them at runtime instead of reading them from `.env`.

### Installation

@verbatim
<code-snippet name="Install" lang="bash">
composer require jeffersongoncalves/laravel-settings-discord-logger
php artisan migrate
</code-snippet>
@endverbatim

### Usage

@verbatim
<code-snippet name="Change settings" lang="php">
use JeffersonGoncalves\SettingsDiscordLogger\Settings\DiscordLoggerSettings;

$settings = app(DiscordLoggerSettings::class);
$settings->webhook_url = 'https://discord.com/api/webhooks/...';
$settings->level = 'error';
$settings->mentions = ['CRITICAL' => '@here'];
$settings->save();
</code-snippet>
@endverbatim

### Rules
- The `discord` logging channel is registered automatically and uses `JeffersonGoncalves\SettingsDiscordLogger\SettingsLogger`; keep adding `discord` to the stack in `config/logging.php`.
- An empty `webhook_url` keeps the channel URL from `.env`.
- Code-level options (converter, redaction, colors, resolvers) stay in `config/discord-logger.php`; don't add them to the settings class.
- `webhook_url` and `level_webhooks` are encrypted; `level_webhooks` and `mentions` are keyed by UPPERCASE Monolog level.
