<?php
declare(strict_types=1);
namespace App\Tests\Adapters\Console;

use App\Adapters\Console\NewEmailsCommand;
use App\Domain\Port\LastRunRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Agent\MockAgent;
use Symfony\Component\Console\Tester\CommandTester;

final class NewEmailsCommandTest extends TestCase
{
    public function testOutputsAgentResponse(): void
    {
        $agent = new MockAgent([
            'Process my unread emails: fetch them, classify importance, notify me of the important ones, and summarize.' => 'Reviewed 5 emails, 2 were important and notifications sent.',
        ]);

        $lastRun = $this->createMock(LastRunRepositoryInterface::class);
        $lastRun->expects(self::once())->method('saveLastRun');

        $command = new NewEmailsCommand($agent, $lastRun);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('Reviewed 5 emails', $tester->getDisplay());
        $agent->assertCallCount(1);
    }

    public function testReturnsFailureOnException(): void
    {
        $agent = new MockAgent(); // no responses configured → will throw

        $lastRun = $this->createMock(LastRunRepositoryInterface::class);
        $lastRun->expects(self::never())->method('saveLastRun');

        $command = new NewEmailsCommand($agent, $lastRun);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(1, $tester->getStatusCode());
    }
}
