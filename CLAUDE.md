# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

- **Symfony 8.0** (PHP 8.4+) — skeleton (no webapp/Twig)
- **symfony/ai-bundle v0.5** — AI Platform abstraction (provider-agnostic)
- **symfony/ai-open-ai-platform** — OpenAI bridge (active); `symfony/ai-gemini-platform` also installed
- **google/apiclient v2** — Gmail API (OAuth2)
- Telegram Bot API — notifications via `symfony/http-client`

## Workflow
- After making changes: stage relevant files with `git add`, but never push.

## Code style
1. Always simplify FQN, e.g. use RuntimeException with the corresponding use instead of \RuntimeException.
2. Use SymfonyStyle in commands for input and output.

## Architecture: Hexagonal

```
src/
  Domain/          Pure business logic — no framework dependencies
    Email/         Email entity + EmailAnalysis value object
    Port/          Three interfaces: EmailFetcher, EmailAnalyzer, NotificationSender

  Application/     Orchestration only — depends on Domain ports
    DTO/           AnalyzeEmailsResult (processed/important/notified counts + per-email errors)
    UseCase/       AnalyzeEmailsUseCase: fetch → analyze → notify, collects errors per email

  Adapters/
    Console/       AnalyzeEmailsCommand (slim), GmailAuthCommand (one-time OAuth)
    Gmail/         GmailEmailFetcher + GoogleClientFactory
    AI/            AiEmailAnalyzer — provider-agnostic, uses PlatformInterface
    Telegram/      TelegramNotificationSender
```

Port-to-adapter bindings are in `config/services.yaml`. Switching AI provider = 2 lines there + `AI_MODEL` in `.env.local`.

## Commands

```bash
# One-time Gmail OAuth setup (creates var/gmail_token.json)
php bin/console app:gmail-auth

# Run the email analyzer
php bin/console app:analyze-emails

# Run tests
php bin/phpunit

# Debug service wiring
php bin/console debug:container AiEmailAnalyzer
php bin/console debug:container ai.platform.openai
```

## Setup Requirements

1. **Gmail credentials** — Download OAuth2 Desktop credentials from Google Cloud Console (Gmail API enabled), save to `config/gmail_credentials.json`. Run `app:gmail-auth` once to authorize and save `var/gmail_token.json`.

2. **Environment variables** in `.env.local`:
   ```
   OPENAI_API_KEY=...
   AI_MODEL=gpt-4o-mini
   TELEGRAM_BOT_TOKEN=...
   TELEGRAM_CHAT_ID=...   # your own Telegram user ID, not the bot's
   GMAIL_MAX_RESULTS=10
   ```

3. **Telegram Bot** — Create via @BotFather. Send `/start` to your bot, then call `getUpdates` to find your chat ID.

## Switching AI Provider

In `config/services.yaml`, change the `AiEmailAnalyzer` arguments:
```yaml
App\Adapters\AI\AiEmailAnalyzer:
    arguments:
        $platform: '@ai.platform.openai'   # or gemini, anthropic, ollama, …
        $model: '%env(AI_MODEL)%'
        # $requestDelayUs: 4_100_000       # uncomment for Gemini free tier (15 RPM)
```
Install the corresponding bridge: `composer require symfony/ai-{provider}-platform`.

## Symfony AI Platform (v0.5)

```php
$text = $platform->invoke($modelName, new MessageBag(
    Message::forSystem('...'),
    Message::ofUser('...'),
))->asText();
```

Provider services registered by the bundle: `ai.platform.openai`, `ai.platform.gemini`, etc.
