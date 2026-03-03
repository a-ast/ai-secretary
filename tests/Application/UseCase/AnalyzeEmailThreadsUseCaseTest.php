<?php

declare(strict_types=1);

namespace App\Tests\Application\UseCase;

use App\Application\UseCase\AnalyzeEmailThreadsUseCase;
use App\Domain\Email\Email;
use App\Domain\Email\EmailThread;
use App\Domain\Email\EmailThreadAnalysis;
use App\Domain\Port\EmailThreadAnalyzerInterface;
use App\Domain\Port\EmailThreadFetcherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AnalyzeEmailThreadsUseCaseTest extends TestCase
{
    private const USER_EMAIL = 'user@example.com';

    private EmailThreadFetcherInterface&MockObject $fetcher;
    private EmailThreadAnalyzerInterface&MockObject $analyzer;
    private AnalyzeEmailThreadsUseCase $useCase;

    protected function setUp(): void
    {
        $this->fetcher = $this->createMock(EmailThreadFetcherInterface::class);
        $this->fetcher->method('getUserEmail')->willReturn(self::USER_EMAIL);
        $this->analyzer = $this->createMock(EmailThreadAnalyzerInterface::class);
        $this->useCase = new AnalyzeEmailThreadsUseCase($this->fetcher, $this->analyzer);
    }

    public function testSkipsThreadsWithNoUserInvolvement(): void
    {
        $thread = $this->makeThread(lastSender: 'other@example.com', lastRecipients: 'someone-else@example.com', otherSenders: ['other2@example.com']);

        $this->fetcher->method('fetchThreadsSince')->willReturn([$thread]);
        $this->analyzer->expects(self::never())->method('analyze');

        $result = $this->useCase->execute(7);

        self::assertSame(1, $result->total);
        self::assertSame(0, $result->analyzed);
        self::assertSame(0, $result->actionable);
    }

    public function testAnalyzesThreadWhenLastMessageIsFromUser(): void
    {
        $thread = $this->makeThread(lastSender: self::USER_EMAIL, otherSenders: ['other@example.com']);
        $analysis = $this->makeAnalysis(requiresAction: true, awaitingReply: true);

        $this->fetcher->method('fetchThreadsSince')->willReturn([$thread]);
        $this->analyzer->expects(self::once())->method('analyze')->willReturn($analysis);

        $result = $this->useCase->execute(7);

        self::assertSame(1, $result->analyzed);
        self::assertSame(1, $result->actionable);
        self::assertCount(1, $result->actionableThreads);
    }

    public function testAnalyzesThreadWhenLastMessageAddressedToUser(): void
    {
        $thread = $this->makeThread(lastSender: 'other@example.com', lastRecipients: self::USER_EMAIL);
        $analysis = $this->makeAnalysis(requiresAction: true, needsUserReply: true);

        $this->fetcher->method('fetchThreadsSince')->willReturn([$thread]);
        $this->analyzer->expects(self::once())->method('analyze')->willReturn($analysis);

        $result = $this->useCase->execute(7);

        self::assertSame(1, $result->analyzed);
        self::assertSame(1, $result->actionable);
    }

    public function testAnalyzesThreadWhenUserSentEarlierAndLastIsFromOther(): void
    {
        $thread = $this->makeThread(lastSender: 'other@example.com', otherSenders: [self::USER_EMAIL]);
        $analysis = $this->makeAnalysis(requiresAction: true, needsUserReply: true);

        $this->fetcher->method('fetchThreadsSince')->willReturn([$thread]);
        $this->analyzer->expects(self::once())->method('analyze')->willReturn($analysis);

        $result = $this->useCase->execute(7);

        self::assertSame(1, $result->analyzed);
        self::assertSame(1, $result->actionable);
    }

    public function testFiltersOutNonActionableThreads(): void
    {
        $thread = $this->makeThread(lastSender: self::USER_EMAIL, otherSenders: ['other@example.com']);
        $analysis = $this->makeAnalysis(requiresAction: false);

        $this->fetcher->method('fetchThreadsSince')->willReturn([$thread]);
        $this->analyzer->method('analyze')->willReturn($analysis);

        $result = $this->useCase->execute(7);

        self::assertSame(1, $result->analyzed);
        self::assertSame(0, $result->actionable);
        self::assertCount(0, $result->actionableThreads);
    }

    public function testCollectsErrorsPerThreadWithoutStopping(): void
    {
        $goodThread = $this->makeThread(lastSender: self::USER_EMAIL, otherSenders: ['other@example.com'], id: 'thread1', subject: 'Good thread');
        $badThread = $this->makeThread(lastSender: self::USER_EMAIL, otherSenders: ['other@example.com'], id: 'thread2', subject: 'Bad thread');

        $this->fetcher->method('fetchThreadsSince')->willReturn([$goodThread, $badThread]);

        $this->analyzer->method('analyze')
            ->willReturnCallback(function (EmailThread $thread) {
                if ('thread2' === $thread->id) {
                    throw new \RuntimeException('AI exploded');
                }

                return $this->makeAnalysis(requiresAction: true);
            });

        $result = $this->useCase->execute(7);

        self::assertSame(2, $result->total);
        self::assertSame(1, $result->analyzed);
        self::assertSame(1, $result->actionable);
        self::assertTrue($result->hasErrors());
        self::assertCount(1, $result->errors);
        self::assertStringContainsString('AI exploded', $result->errors[0]);
    }

    public function testThrowsWhenFetchFails(): void
    {
        $this->fetcher->method('fetchThreadsSince')
            ->willThrowException(new \RuntimeException('Gmail is down'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Failed to fetch threads.*Gmail is down/');

        $this->useCase->execute(7);
    }

    public function testMatchesEmailCaseInsensitively(): void
    {
        $thread = $this->makeThread(lastSender: 'USER@EXAMPLE.COM', otherSenders: ['other@example.com']);
        $analysis = $this->makeAnalysis(requiresAction: true, awaitingReply: true);

        $this->fetcher->method('fetchThreadsSince')->willReturn([$thread]);
        $this->analyzer->expects(self::once())->method('analyze')->willReturn($analysis);

        $result = $this->useCase->execute(7);

        self::assertSame(1, $result->analyzed);
    }

    public function testMatchesEmailInAngleBrackets(): void
    {
        $thread = $this->makeThread(lastSender: 'User Name <user@example.com>', otherSenders: ['other@example.com']);
        $analysis = $this->makeAnalysis(requiresAction: true, awaitingReply: true);

        $this->fetcher->method('fetchThreadsSince')->willReturn([$thread]);
        $this->analyzer->expects(self::once())->method('analyze')->willReturn($analysis);

        $result = $this->useCase->execute(7);

        self::assertSame(1, $result->analyzed);
    }

    // --- helpers ---

    private function makeThread(
        string $lastSender,
        array $otherSenders = [],
        string $lastRecipients = '',
        string $id = 'thread1',
        string $subject = 'Test subject',
    ): EmailThread {
        $messages = [];

        foreach ($otherSenders as $i => $sender) {
            $messages[] = new Email(
                id: "msg-$i",
                subject: $subject,
                body: 'Some body text.',
                sender: $sender,
                receivedAt: new \DateTimeImmutable("-{$i} hours"),
            );
        }

        $messages[] = new Email(
            id: 'msg-last',
            subject: $subject,
            body: 'Last message body.',
            sender: $lastSender,
            receivedAt: new \DateTimeImmutable(),
            recipients: $lastRecipients,
        );

        return new EmailThread($id, $messages);
    }

    private function makeAnalysis(
        bool $requiresAction,
        bool $awaitingReply = false,
        bool $needsUserReply = false,
    ): EmailThreadAnalysis {
        return new EmailThreadAnalysis(
            awaitingReply: $awaitingReply,
            needsUserReply: $needsUserReply,
            requiresAction: $requiresAction,
            summary: 'Test summary.',
            reason: 'Test reason.',
        );
    }
}
