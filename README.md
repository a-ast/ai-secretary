# AI Secretary

A Symfony CLI application that monitors your Gmail inbox and sends Telegram notifications for emails that need your attention. Powered by Symfony AI agent toolbox with tool calling — the AI decides what to fetch and who to notify.

## What it does

Two autonomous agents run on demand (or on a schedule):

**New emails** — fetches unread emails since the last run, classifies each one for importance, and sends a Telegram notification for anything requiring action.

**Thread review** — scans recent threads and flags conversations awaiting a reply: ones where you sent the last message with no response, or where someone is waiting on you.

## Requirements

- PHP 8.4+
- Composer
- A Gmail account with API access
- An AI provider API key (OpenAI by default)
- A Telegram bot

## Setup

### 1. Install

```bash
composer install
```

### 2. Gmail API credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Create a project, enable the **Gmail API**
3. Create an **OAuth 2.0 Client ID** (Desktop app type)
4. Download the credentials JSON and save it to `config/gmail_credentials.json`
5. Run the one-time auth flow:
   ```bash
   php bin/console app:gmail-auth
   ```
   This opens a browser URL — paste the authorization code back into the terminal. The token is saved to `var/gmail_token.json` and auto-refreshed on subsequent runs.

### 3. Environment variables

Create `.env.local`:

```dotenv
OPENAI_API_KEY=sk-...
AI_MODEL=gpt-4o-mini

TELEGRAM_DSN=telegram://BOT_TOKEN@default?channel=CHAT_ID

GMAIL_MAX_RESULTS=10
```

**Telegram setup:** create a bot via [@BotFather](https://t.me/BotFather), send `/start` to your bot, then find your chat ID at `https://api.telegram.org/bot<TOKEN>/getUpdates`. Use your own user ID as `channel`, not the bot's.

## Usage

```bash
# Classify new unread emails and notify
php bin/console ai-sec:email:new-emails

# Review threads from the last 7 days (default)
php bin/console ai-sec:email:thread

# Review threads from the last 14 days
php bin/console ai-sec:email:thread --days=14
```

## Switching AI provider

Change the platform in `config/packages/ai.yaml`:

```yaml
ai:
    agent:
        new_email:
            platform: 'ai.platform.gemini'   # or openai, ollama, …
            model: '%env(AI_MODEL)%'
        thread_review:
            platform: 'ai.platform.gemini'
            model: '%env(AI_MODEL)%'
```

Then install the corresponding bridge:

| Provider  | Package                           | Service ID               | Example model        |
|-----------|-----------------------------------|--------------------------|----------------------|
| OpenAI    | `symfony/ai-open-ai-platform`     | `ai.platform.openai`     | `gpt-4o-mini`        |
| Gemini    | `symfony/ai-gemini-platform`      | `ai.platform.gemini`     | `gemini-2.0-flash`   |
| Anthropic | `symfony/ai-anthropic-platform`   | `ai.platform.anthropic`  | `claude-haiku-4-5-*` |
| Ollama    | `symfony/ai-ollama-platform`      | `ai.platform.ollama`     | `llama3.2`           |

## Architecture

Hexagonal layout — framework and infrastructure details stay outside the domain:

```
src/
  Domain/              Pure business logic, no framework dependencies
    Email/             Email + EmailThread value objects
    Agent/             ActionItem value object
    Port/              MailboxInterface, NotificationSenderInterface,
                       LastRunRepositoryInterface

  Adapters/
    Console/           NewEmailsCommand, ThreadReviewCommand
    Gmail/             GmailMailbox — fetches emails and threads via Gmail API
    AI/Tool/           FetchUnreadEmailsTool, FetchEmailThreadsTool,
                       GetUserEmailTool, SendTelegramNotificationTool
    Telegram/          TelegramNotificationSender, TelegramExtension (Twig filter)
    Storage/           JsonFileLastRunRepository
```

Port-to-adapter bindings live in `config/services.yaml`. Switching provider or storage backend requires changes only there.

## Running tests

```bash
php bin/phpunit
```
