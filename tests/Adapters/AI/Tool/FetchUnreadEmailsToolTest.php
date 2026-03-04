<?php
declare(strict_types=1);
namespace App\Tests\Adapters\AI\Tool;

use App\Adapters\AI\Tool\FetchUnreadEmailsTool;
use App\Domain\Email\Email;
use App\Domain\Port\LastRunRepositoryInterface;
use App\Domain\Port\MailboxInterface;
use PHPUnit\Framework\TestCase;

final class FetchUnreadEmailsToolTest extends TestCase
{
    public function testReturnsJsonArrayOfEmails(): void
    {
        $lastRun = $this->createStub(LastRunRepositoryInterface::class);
        $lastRun->method('getLastRun')->willReturn(new \DateTimeImmutable('-1 day'));

        $mailbox = $this->createMock(MailboxInterface::class);
        $email = new Email('msg1', 'Hello', 'Body content', 'sender@example.com', new \DateTimeImmutable());
        $mailbox->expects(self::once())->method('fetchUnread')->willReturn([$email]);

        $tool = new FetchUnreadEmailsTool($mailbox, $lastRun);
        $result = ($tool)();
        $data = json_decode($result, true);

        self::assertIsArray($data);
        self::assertCount(1, $data);
        self::assertSame('msg1', $data[0]['id']);
        self::assertSame('Hello', $data[0]['subject']);
        self::assertSame('sender@example.com', $data[0]['sender']);
        self::assertStringContainsString('msg1', $data[0]['gmail_link']);
    }

    public function testUsesDefaultSinceWhenNoLastRun(): void
    {
        $lastRun = $this->createStub(LastRunRepositoryInterface::class);
        $lastRun->method('getLastRun')->willReturn(null);

        $mailbox = $this->createStub(MailboxInterface::class);
        $mailbox->method('fetchUnread')->willReturn([]);

        $tool = new FetchUnreadEmailsTool($mailbox, $lastRun);
        $result = ($tool)();
        self::assertSame('[]', $result);
    }

    public function testTruncatesBodyTo2000Chars(): void
    {
        $lastRun = $this->createStub(LastRunRepositoryInterface::class);
        $lastRun->method('getLastRun')->willReturn(new \DateTimeImmutable('-1 day'));

        $longBody = str_repeat('x', 3000);
        $email = new Email('msg1', 'Subject', $longBody, 'sender@example.com', new \DateTimeImmutable());

        $mailbox = $this->createMock(MailboxInterface::class);
        $mailbox->expects(self::once())->method('fetchUnread')->willReturn([$email]);

        $tool = new FetchUnreadEmailsTool($mailbox, $lastRun);
        $result = ($tool)();
        $data = json_decode($result, true);

        self::assertSame(2000, mb_strlen($data[0]['body']));
    }
}
