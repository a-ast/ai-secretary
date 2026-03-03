<?php

declare(strict_types=1);

namespace App\Adapters\Telegram;

use App\Domain\Email\Email;
use App\Domain\Email\EmailAnalysis;
use App\Domain\Port\NotificationSenderInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Environment;

final class TelegramNotificationSender implements NotificationSenderInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Environment $twig,
        private readonly string $botToken,
        private readonly string $chatId,
    ) {
        if ('' === $this->botToken) {
            throw new \RuntimeException('TELEGRAM_BOT_TOKEN is not configured.');
        }

        if ('' === $this->chatId) {
            throw new \RuntimeException('TELEGRAM_CHAT_ID is not configured.');
        }
    }

    public function send(Email $email, EmailAnalysis $analysis): void
    {
        $text = $this->twig->render('telegram/new-email.md.twig', [
            'email' => $email,
            'analysis' => $analysis,
            'gmail_url' => sprintf('https://mail.google.com/mail/u/0/#inbox/%s', $email->id),
        ]);

        try {
            $response = $this->httpClient->request('POST', sprintf(
                'https://api.telegram.org/bot%s/sendMessage',
                $this->botToken,
            ), [
                'json' => [
                    'chat_id' => $this->chatId,
                    'text' => trim($text),
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
}
