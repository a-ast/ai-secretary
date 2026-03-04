<?php
declare(strict_types=1);
namespace App\Tests\Adapters\AI\Tool;

use App\Adapters\AI\Tool\SendTelegramNotificationTool;
use App\Domain\Agent\ActionItem;
use App\Domain\Port\NotificationSenderInterface;
use PHPUnit\Framework\TestCase;

final class SendTelegramNotificationToolTest extends TestCase
{
    public function testSendsNotificationAndReturnsConfirmation(): void
    {
        $notifier = $this->createMock(NotificationSenderInterface::class);
        $notifier->expects(self::once())
            ->method('send')
            ->with(self::callback(fn (ActionItem $item) =>
                $item->title === 'Important Email'
                && $item->summary === 'A short summary'
                && $item->reason === 'Needs your reply'
                && $item->link === 'https://mail.google.com/mail/u/0/#all/abc123'
            ));

        $tool = new SendTelegramNotificationTool($notifier);
        $result = ($tool)('Important Email', 'A short summary', 'Needs your reply', 'https://mail.google.com/mail/u/0/#all/abc123');

        self::assertStringContainsString('Important Email', $result);
    }
}
