<?php
declare(strict_types=1);
namespace App\Tests\Adapters\Console;

use App\Adapters\Console\ThreadReviewCommand;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Agent\MockAgent;
use Symfony\Component\Console\Tester\CommandTester;

final class ThreadReviewCommandTest extends TestCase
{
    public function testPassesDaysInUserMessage(): void
    {
        $expectedInput = 'Review my email threads from the past 14 day(s). Find any requiring action, notify me, and summarize.';

        $agent = new MockAgent([
            $expectedInput => 'Reviewed 20 threads, 1 requires action.',
        ]);

        $command = new ThreadReviewCommand($agent);
        $tester = new CommandTester($command);
        $tester->execute(['--days' => 14]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('Reviewed 20 threads', $tester->getDisplay());
        $agent->assertCalledWith($expectedInput);
    }

    public function testDefaultsToSevenDays(): void
    {
        $expectedInput = 'Review my email threads from the past 7 day(s). Find any requiring action, notify me, and summarize.';

        $agent = new MockAgent([
            $expectedInput => 'No threads require action.',
        ]);

        $command = new ThreadReviewCommand($agent);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        $agent->assertCalledWith($expectedInput);
    }
}
