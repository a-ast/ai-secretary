<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\AnalyzeEmailThreadsResult;
use App\Domain\Port\EmailThreadAnalyzerInterface;
use App\Domain\Port\EmailThreadFetcherInterface;

final class AnalyzeEmailThreadsUseCase
{
    public function __construct(
        private readonly EmailThreadFetcherInterface $threadFetcher,
        private readonly EmailThreadAnalyzerInterface $threadAnalyzer,
    ) {}

    public function execute(int $days): AnalyzeEmailThreadsResult
    {
        $result = new AnalyzeEmailThreadsResult();

        $since = new \DateTimeImmutable("-{$days} days");

        try {
            $userEmail = $this->threadFetcher->getUserEmail();
            $threads = $this->threadFetcher->fetchThreadsSince($since);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to fetch threads from Gmail: '.$e->getMessage(), 0, $e);
        }

        $result->total = count($threads);

        $userEmailNorm = strtolower(trim($userEmail));

        foreach ($threads as $thread) {
            try {
                $last = $thread->lastMessage();

                $lastIsFromUser = strtolower($this->extractEmail($last->sender)) === $userEmailNorm;

                $lastAddressedToUser = $this->recipientsContain($last->recipients, $userEmailNorm);

                $userSentEarlier = false;
                foreach ($thread->messages as $message) {
                    if ($message->id !== $last->id
                        && strtolower($this->extractEmail($message->sender)) === $userEmailNorm) {
                        $userSentEarlier = true;
                        break;
                    }
                }

                if (!$lastIsFromUser && !$lastAddressedToUser && !$userSentEarlier) {
                    continue;
                }

                $analysis = $this->threadAnalyzer->analyze($thread, $userEmail);
                ++$result->analyzed;

                if ($analysis->requiresAction) {
                    ++$result->actionable;
                    $result->actionableThreads[] = ['thread' => $thread, 'analysis' => $analysis];
                }
            } catch (\Throwable $e) {
                $result->addError($thread->subject(), $e->getMessage());
            }
        }

        return $result;
    }

    private function extractEmail(string $sender): string
    {
        if (preg_match('/<([^>]+)>/', $sender, $matches)) {
            return $matches[1];
        }

        return $sender;
    }

    private function recipientsContain(string $recipients, string $userEmailNorm): bool
    {
        foreach (explode(',', $recipients) as $recipient) {
            if (strtolower($this->extractEmail(trim($recipient))) === $userEmailNorm) {
                return true;
            }
        }

        return false;
    }
}
