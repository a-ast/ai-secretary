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
final readonly class SendTelegramNotificationTool
{
    public function __construct(private NotificationSenderInterface $notifier) {}

    /**
     * @param string $title    Title
     * @param string $summary  One-sentence summary
     * @param string $reason   Brief explanation of why this requires attention
     * @param string $link     Link to the email, event
     * @param string $template Twig template to use for rendering the notification
     */
    public function __invoke(string $title, string $summary, string $reason, string $link, string $template = 'telegram/action-item.html.twig'): string
    {
        $this->notifier->send(new ActionItem($title, $summary, $reason, $link), $template);

        return sprintf('Notification sent for: %s', $title);
    }
}
