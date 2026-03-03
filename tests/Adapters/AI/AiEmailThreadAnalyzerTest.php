<?php

declare(strict_types=1);

namespace App\Tests\Adapters\AI;

use App\Adapters\AI\AiEmailThreadAnalyzer;
use App\Domain\Email\Email;
use App\Domain\Email\EmailThread;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Exception\RateLimitExceededException;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\AI\Platform\Result\DeferredResult;
use Symfony\AI\Platform\Result\RawResultInterface;
use Symfony\AI\Platform\Result\TextResult;
use Symfony\AI\Platform\ResultConverterInterface;

final class AiEmailThreadAnalyzerTest extends TestCase
{
    private PlatformInterface&MockObject $platform;
    private AiEmailThreadAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->platform = $this->createMock(PlatformInterface::class);
        $this->analyzer = new AiEmailThreadAnalyzer($this->platform, 'gpt-4o-mini');
    }

    public function testMarksThreadAsRequiringAction(): void
    {
        $this->platform->method('invoke')
            ->willReturn($this->deferredText('{"requiresAction": true, "awaitingReply": true, "needsUserReply": false, "summary": "Waiting for approval", "reason": "You sent a request 3 days ago with no response."}'));

        $result = $this->analyzer->analyze($this->makeThread(), 'user@example.com');

        self::assertTrue($result->requiresAction);
        self::assertTrue($result->awaitingReply);
        self::assertFalse($result->needsUserReply);
        self::assertSame('Waiting for approval', $result->summary);
        self::assertSame('You sent a request 3 days ago with no response.', $result->reason);
    }

    public function testMarksThreadAsNotRequiringAction(): void
    {
        $this->platform->method('invoke')
            ->willReturn($this->deferredText('{"requiresAction": false, "awaitingReply": false, "needsUserReply": false, "summary": "Newsletter digest", "reason": "Automated newsletter, no action needed."}'));

        $result = $this->analyzer->analyze($this->makeThread(), 'user@example.com');

        self::assertFalse($result->requiresAction);
        self::assertSame('Newsletter digest', $result->summary);
    }

    public function testThrowsOnUnparsableResponse(): void
    {
        $this->platform->method('invoke')
            ->willReturn($this->deferredText('not valid json'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/unparsable response/');

        $this->analyzer->analyze($this->makeThread(), 'user@example.com');
    }

    public function testThrowsWhenRequiresActionKeyMissing(): void
    {
        $this->platform->method('invoke')
            ->willReturn($this->deferredText('{"summary": "ok"}'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/unparsable response/');

        $this->analyzer->analyze($this->makeThread(), 'user@example.com');
    }

    public function testRetriesOnRateLimitAndSucceeds(): void
    {
        $successDeferred = $this->deferredText('{"requiresAction": true, "awaitingReply": true, "needsUserReply": false, "summary": "ok", "reason": "Retried successfully"}');
        $callCount = 0;

        $this->platform
            ->expects(self::exactly(2))
            ->method('invoke')
            ->willReturnCallback(function () use (&$callCount, $successDeferred): DeferredResult {
                if (0 === $callCount++) {
                    throw new RateLimitExceededException();
                }

                return $successDeferred;
            });

        $result = $this->analyzer->analyze($this->makeThread(), 'user@example.com');

        self::assertTrue($result->requiresAction);
        self::assertSame('Retried successfully', $result->reason);
    }

    public function testThrowsAfterMaxRateLimitAttempts(): void
    {
        $this->platform
            ->method('invoke')
            ->willThrowException(new RateLimitExceededException());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Rate limit exceeded.*failed after 4 attempts/');

        $this->analyzer->analyze($this->makeThread(), 'user@example.com');
    }

    public function testWrapsGenericPlatformError(): void
    {
        $this->platform
            ->expects(self::once())
            ->method('invoke')
            ->willThrowException(new \RuntimeException('Invalid API key'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/AI platform error.*Invalid API key/');

        $this->analyzer->analyze($this->makeThread(), 'user@example.com');
    }

    public function testRateLimitingAddsDelayBetweenCalls(): void
    {
        $analyzer = new AiEmailThreadAnalyzer($this->platform, 'gpt-4o-mini', requestDelayUs: 500_000); // 0.5s
        $this->platform
            ->method('invoke')
            ->willReturnCallback(fn () => $this->deferredText('{"requiresAction": false, "awaitingReply": false, "needsUserReply": false, "summary": "ok", "reason": "ok"}'));

        $thread = $this->makeThread();

        $start = microtime(true);
        $analyzer->analyze($thread, 'user@example.com');
        $analyzer->analyze($thread, 'user@example.com');
        $elapsed = microtime(true) - $start;

        self::assertGreaterThan(0.4, $elapsed, 'Rate limiter should enforce configured delay between calls');
    }

    // --- helpers ---

    private function makeThread(): EmailThread
    {
        $email = new Email(
            id: 'msg1',
            subject: 'Re: Q1 Report',
            body: 'Please review the attached report.',
            sender: 'colleague@example.com',
            receivedAt: new \DateTimeImmutable(),
        );

        return new EmailThread('thread1', [$email]);
    }

    private function deferredText(string $json): DeferredResult
    {
        $converter = $this->createMock(ResultConverterInterface::class);
        $converter->method('convert')->willReturn(new TextResult($json));
        $converter->method('getTokenUsageExtractor')->willReturn(null);

        return new DeferredResult($converter, $this->createMock(RawResultInterface::class));
    }
}
