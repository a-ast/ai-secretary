<?php

declare(strict_types=1);

namespace App\Adapters\Telegram;

use App\Domain\Email\Email;
use App\Domain\Email\EmailAnalysis;
use App\Domain\Port\NotificationSenderInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TelegramNotificationSender implements NotificationSenderInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $botToken,
        private readonly string $chatId,
    ) {}

    public function sendImportantEmailAlert(Email $email, EmailAnalysis $analysis): void
    {
        if ('' === $this->botToken) {
            throw new \RuntimeException('TELEGRAM_BOT_TOKEN is not configured.');
        }

        if ('' === $this->chatId) {
            throw new \RuntimeException('TELEGRAM_CHAT_ID is not configured.');
        }

        $text = sprintf(
            "📧 *Important Email*\n\n*From:* %s\n*Subject:* %s\n\n*Why:* %s",
            $this->escape($email->sender),
            $this->escape($email->subject),
            $this->escape($analysis->reason),
        );

        try {
            $response = $this->httpClient->request('POST', sprintf(
                'https://api.telegram.org/bot%s/sendMessage',
                $this->botToken,
            ), [
                'json' => [
                    'chat_id' => $this->chatId,
                    'text' => $text,
                    'parse_mode' => 'MarkdownV2',
                ],
            ]);

            $data = $response->toArray(false);

            if (!($data['ok'] ?? false)) {
                throw new \RuntimeException(sprintf(
                    'Telegram API error %d: %s',
                    $data['error_code'] ?? 0,
                    $data['description'] ?? 'unknown error',
                ));
            }
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException('Could not reach Telegram API: '.$e->getMessage(), 0, $e);
        } catch (ClientExceptionInterface $e) {
            throw new \RuntimeException(sprintf(
                'Telegram request failed (HTTP %d). Check TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID.',
                $e->getResponse()->getStatusCode(),
            ), 0, $e);
        }
    }

    private function escape(string $text): string
    {
        return preg_replace('/([_*\[\]()~`>#+\-=|{}.!])/', '\\\\$1', $text);
    }
}
