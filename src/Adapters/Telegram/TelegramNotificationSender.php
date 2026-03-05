<?php

declare(strict_types=1);

namespace App\Adapters\Telegram;

use App\Domain\Agent\ActionItem;
use App\Domain\Port\NotificationSenderInterface;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramOptions;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Twig\Environment;

final readonly class TelegramNotificationSender implements NotificationSenderInterface
{
    public function __construct(
        private ChatterInterface $chatter,
        private Environment $twig,
    ) {}

    public function send(ActionItem $item, string $template = 'telegram/action-item.html.twig'): void
    {
        $text = $this->twig->render($template, ['item' => $item]);

        $message = new ChatMessage(trim($text));
        $message->options(new TelegramOptions()->parseMode('HTML'));

        $this->chatter->send($message);
    }
}
