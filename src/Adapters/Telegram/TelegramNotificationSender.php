<?php

declare(strict_types=1);

namespace App\Adapters\Telegram;

use App\Domain\Agent\ActionItem;
use App\Domain\Port\NotificationSenderInterface;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramOptions;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Twig\Environment;

final class TelegramNotificationSender implements NotificationSenderInterface
{
    public function __construct(
        private readonly ChatterInterface $chatter,
        private readonly Environment $twig,
    ) {
    }

    public function send(ActionItem $item): void
    {
        $text = $this->twig->render('telegram/action-item.md.twig', ['item' => $item]);

        $message = new ChatMessage(trim($text));
        $message->options((new TelegramOptions())->parseMode('MarkdownV2'));

        $this->chatter->send($message);
    }
}
