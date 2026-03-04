<?php
declare(strict_types=1);
namespace App\Tests\Adapters\AI\Tool;

use App\Adapters\AI\Tool\FetchEmailThreadsTool;
use App\Domain\Email\Email;
use App\Domain\Email\EmailThread;
use App\Domain\Port\MailboxInterface;
use PHPUnit\Framework\TestCase;

final class FetchEmailThreadsToolTest extends TestCase
{
    public function testReturnsJsonArrayOfThreads(): void
    {
        $email1 = new Email('msg1', 'Hello', 'First message body', 'alice@example.com', new \DateTimeImmutable('2024-01-01 10:00'), 'bob@example.com');
        $email2 = new Email('msg2', 'Hello', 'Second message body', 'bob@example.com', new \DateTimeImmutable('2024-01-02 11:00'), 'alice@example.com');
        $thread = new EmailThread('thread1', [$email1, $email2]);

        $mailbox = $this->createMock(MailboxInterface::class);
        $mailbox->expects(self::once())->method('fetchThreadsSince')->willReturn([$thread]);

        $tool = new FetchEmailThreadsTool($mailbox);
        $result = ($tool)();
        $data = json_decode($result, true);

        self::assertIsArray($data);
        self::assertCount(1, $data);
        self::assertSame('thread1', $data[0]['id']);
        self::assertSame('Hello', $data[0]['subject']);
        self::assertStringContainsString('thread1', $data[0]['gmail_link']);
        self::assertCount(2, $data[0]['messages']);
        self::assertSame('alice@example.com', $data[0]['messages'][0]['sender']);
        self::assertSame('bob@example.com', $data[0]['messages'][0]['recipients']);
        self::assertSame('2024-01-01 10:00', $data[0]['messages'][0]['date']);
        self::assertSame('First message body', $data[0]['messages'][0]['body']);
    }

    public function testDoesNotTruncateFirstAndLastMessage(): void
    {
        $longBody = str_repeat('x', 3000);
        $first  = new Email('msg1', 'Subject', $longBody, 'a@example.com', new \DateTimeImmutable());
        $middle = new Email('msg2', 'Subject', $longBody, 'b@example.com', new \DateTimeImmutable());
        $last   = new Email('msg3', 'Subject', $longBody, 'c@example.com', new \DateTimeImmutable());
        $thread = new EmailThread('thread1', [$first, $middle, $last]);

        $mailbox = $this->createMock(MailboxInterface::class);
        $mailbox->expects(self::once())->method('fetchThreadsSince')->willReturn([$thread]);

        $tool = new FetchEmailThreadsTool($mailbox);
        $data = json_decode(($tool)(), true);

        self::assertSame(3000, mb_strlen($data[0]['messages'][0]['body']), 'first not truncated');
        self::assertSame(2000, mb_strlen($data[0]['messages'][1]['body']), 'middle truncated to 2000');
        self::assertSame(3000, mb_strlen($data[0]['messages'][2]['body']), 'last not truncated');
    }

    public function testSingleMessageThreadIsNotTruncated(): void
    {
        $longBody = str_repeat('x', 3000);
        $email  = new Email('msg1', 'Subject', $longBody, 'a@example.com', new \DateTimeImmutable());
        $thread = new EmailThread('thread1', [$email]);

        $mailbox = $this->createMock(MailboxInterface::class);
        $mailbox->expects(self::once())->method('fetchThreadsSince')->willReturn([$thread]);

        $tool = new FetchEmailThreadsTool($mailbox);
        $data = json_decode(($tool)(), true);

        self::assertSame(3000, mb_strlen($data[0]['messages'][0]['body']));
    }
}
