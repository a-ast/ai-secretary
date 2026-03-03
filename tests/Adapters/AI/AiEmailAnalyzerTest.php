<?php

declare(strict_types=1);

namespace App\Tests\Adapters\AI;

use App\Adapters\AI\AiEmailAnalyzer;
use App\Domain\Email\Email;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Exception\RateLimitExceededException;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\AI\Platform\Result\DeferredResult;
use Symfony\AI\Platform\Result\RawResultInterface;
use Symfony\AI\Platform\Result\TextResult;
use Symfony\AI\Platform\ResultConverterInterface;

final class AiEmailAnalyzerTest extends TestCase
{
    private PlatformInterface&MockObject $platform;
    private AiEmailAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->platform = $this->createMock(PlatformInterface::class);
        $this->analyzer = new AiEmailAnalyzer($this->platform, 'gpt-4o-mini');
    }

    public function testMarksEmailAsImportant(): void
    {
        $this->platform->method('invoke')
            ->willReturn($this->deferredText('{"important": true, "reason": "Requires urgent action"}'));

        $result = $this->analyzer->analyze($this->makeEmail());

        self::assertTrue($result->isImportant);
        self::assertSame('Requires urgent action', $result->reason);
    }

    public function testMarksEmailAsNotImportant(): void
    {
        $this->platform->method('invoke')
            ->willReturn($this->deferredText('{"important": false, "reason": "Newsletter"}'));

        $result = $this->analyzer->analyze($this->makeEmail());

        self::assertFalse($result->isImportant);
        self::assertSame('Newsletter', $result->reason);
    }

    public function testThrowsOnUnparsableResponse(): void
    {
        $this->platform->method('invoke')
            ->willReturn($this->deferredText('not valid json'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/unparsable response/');

        $this->analyzer->analyze($this->makeEmail());
    }

    public function testRetriesOnRateLimitAndSucceeds(): void
    {
        $successDeferred = $this->deferredText('{"important": true, "reason": "Retried successfully"}');
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

        $result = $this->analyzer->analyze($this->makeEmail());

        self::assertTrue($result->isImportant);
        self::assertSame('Retried successfully', $result->reason);
    }

    public function testThrowsAfterMaxRateLimitAttempts(): void
    {
        $this->platform
            ->method('invoke')
            ->willThrowException(new RateLimitExceededException());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Rate limit exceeded.*failed after 4 attempts/');

        $this->analyzer->analyze($this->makeEmail());
    }

    public function testWrapsGenericPlatformError(): void
    {
        $this->platform
            ->expects(self::once())
            ->method('invoke')
            ->willThrowException(new \RuntimeException('Invalid API key'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/AI platform error.*Invalid API key/');

        $this->analyzer->analyze($this->makeEmail());
    }

    public function testRateLimitingAddsDelayBetweenCalls(): void
    {
        $analyzer = new AiEmailAnalyzer($this->platform, 'gpt-4o-mini', requestDelayUs: 500_000); // 0.5s
        $this->platform
            ->method('invoke')
            ->willReturnCallback(fn () => $this->deferredText('{"important": false, "reason": "ok"}'));

        $email = $this->makeEmail();

        $start = microtime(true);
        $analyzer->analyze($email);
        $analyzer->analyze($email);
        $elapsed = microtime(true) - $start;

        self::assertGreaterThan(0.4, $elapsed, 'Rate limiter should enforce configured delay between calls');
    }

    // --- helpers ---

    private function makeEmail(): Email
    {
        return new Email(
            id: '123',
            subject: 'Test subject',
            body: 'Test body content',
            sender: 'sender@example.com',
            receivedAt: new \DateTimeImmutable(),
        );
    }

    private function deferredText(string $json): DeferredResult
    {
        $converter = $this->createMock(ResultConverterInterface::class);
        $converter->method('convert')->willReturn(new TextResult($json));
        $converter->method('getTokenUsageExtractor')->willReturn(null);

        return new DeferredResult($converter, $this->createMock(RawResultInterface::class));
    }
}
