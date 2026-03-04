<?php

declare(strict_types=1);

namespace App\Adapters\Telegram;

use App\Domain\Agent\ActionItem;
use App\Domain\Port\NotificationSenderInterface;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramOptions;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;

final readonly class TelegramNotificationSender implements NotificationSenderInterface
{
    public function __construct(private ChatterInterface $chatter) {}

    public function send(ActionItem $item): void
    {
        $e = fn (string $s) => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $text = sprintf(
            "📩 <b>New Email</b>\n\n<b>Subject:</b> <a href=\"%s\">%s</a>\n\n<b>Summary:</b> %s\n\n<b>Reason:</b> %s",
            $e($item->link),
            $e($item->title),
            $e($item->summary),
            $e($item->reason),
        );

        $message = new ChatMessage($text);
        $message->options(new TelegramOptions()->parseMode('HTML'));

        $this->chatter->send($message);
    }
}
