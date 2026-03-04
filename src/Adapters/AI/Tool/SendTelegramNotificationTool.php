<?php
declare(strict_types=1);
namespace App\Adapters\AI\Tool;

use App\Domain\Agent\ActionItem;
use App\Domain\Port\NotificationSenderInterface;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

#[AsTool(
    name: 'send_telegram_notification',
    description: 'Sends a Telegram notification for an important email or thread that requires attention.',
)]
final class SendTelegramNotificationTool
{
    public function __construct(private readonly NotificationSenderInterface $notifier) {}

    /**
     * @param string $title   Email subject or thread title (include [AWAITING REPLY] or [NEEDS YOUR REPLY] prefix for threads)
     * @param string $summary One-sentence summary of the email or thread
     * @param string $reason  Brief explanation of why this requires attention
     * @param string $link    Gmail link to the email or thread
     */
    public function __invoke(string $title, string $summary, string $reason, string $link): string
    {
        $this->notifier->send(new ActionItem($title, $summary, $reason, $link));

        return sprintf('Notification sent for: %s', $title);
    }
}
