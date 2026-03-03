# AI Secretary

A Symfony CLI application that fetches unread Gmail emails, 
analyzes them with AI to determine importance, 
and sends Telegram notifications for emails that require attention.

## How it works

1. Fetches unread emails from Gmail inbox (last 2 months, up to `GMAIL_MAX_RESULTS`)
2. Sends each email to an AI model with a classification prompt
3. If the AI marks an email as important, sends a Telegram message with the sender, subject, and reason

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
4. Download the credentials JSON and save it to:
   ```
   config/gmail_credentials.json
   ```
5. Authorize your account (one-time):
   ```bash
   php bin/console app:gmail-auth
   ```
   This opens a browser URL — paste the authorization code back into the terminal. The token is saved to `var/gmail_token.json` and auto-refreshed on subsequent runs.

### 3. Environment variables

Copy your values into `.env.local`:

```dotenv
OPENAI_API_KEY=your-openai-api-key
AI_MODEL=gpt-4o-mini

TELEGRAM_BOT_TOKEN=your-bot-token
TELEGRAM_CHAT_ID=your-personal-chat-id   # not the bot's ID — yours

GMAIL_MAX_RESULTS=10
```

**Telegram setup:** Create a bot via [@BotFather](https://t.me/BotFather), send `/start` to your bot, then find your chat ID at:
```
https://api.telegram.org/bot<YOUR_TOKEN>/getUpdates
```

### 4. Run

```bash
php bin/console app:analyze-emails
```

## Switching AI Provider

Only two lines to change in `config/services.yaml`:

```yaml
App\Adapters\AI\AiEmailAnalyzer:
    arguments:
        $platform: '@ai.platform.openai'   # change to: gemini, anthropic, ollama, …
        $model: '%env(AI_MODEL)%'
        # $requestDelayUs: 4_100_000       # uncomment for Gemini free tier (15 RPM)
```

Then install the corresponding bridge and set the API key:

| Provider  | Package                           | Service ID               | Example model        |
|-----------|-----------------------------------|--------------------------|----------------------|
| OpenAI    | `symfony/ai-open-ai-platform`     | `ai.platform.openai`     | `gpt-4o-mini`        |
| Gemini    | `symfony/ai-gemini-platform`      | `ai.platform.gemini`     | `gemini-2.0-flash`   |
| Anthropic | `symfony/ai-anthropic-platform`   | `ai.platform.anthropic`  | `claude-haiku-4-5-*` |
| Ollama    | `symfony/ai-ollama-platform`      | `ai.platform.ollama`     | `llama3.2`           |
