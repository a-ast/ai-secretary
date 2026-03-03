<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\AnalyzeEmailsResult;
use App\Domain\Port\EmailAnalyzerInterface;
use App\Domain\Port\EmailFetcherInterface;
use App\Domain\Port\LastRunRepositoryInterface;
use App\Domain\Port\NotificationSenderInterface;

final class AnalyzeEmailsUseCase
{
    public function __construct(
        private readonly EmailFetcherInterface $emailFetcher,
        private readonly EmailAnalyzerInterface $emailAnalyzer,
        private readonly NotificationSenderInterface $notificationSender,
        private readonly LastRunRepositoryInterface $lastRunRepository,
    ) {}

    public function execute(): AnalyzeEmailsResult
    {
        $result = new AnalyzeEmailsResult();

        $since = $this->lastRunRepository->getLastRun() ?? new \DateTimeImmutable('-1 month');

        try {
            $emails = $this->emailFetcher->fetchUnread($since);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to fetch emails from Gmail: '.$e->getMessage(), 0, $e);
        }

        $this->lastRunRepository->saveLastRun(new \DateTimeImmutable());

        $result->total = count($emails);

        foreach ($emails as $email) {
            try {
                $analysis = $this->emailAnalyzer->analyze($email);
                ++$result->processed;

                if ($analysis->isImportant) {
                    ++$result->important;

                    try {
                        $this->notificationSender->send($email, $analysis);
                        ++$result->notified;
                    } catch (\Throwable $e) {
                        $result->addError($email->subject, 'Telegram notification failed: '.$e->getMessage());
                    }
                }
            } catch (\Throwable $e) {
                $result->addError($email->subject, $e->getMessage());
            }
        }

        return $result;
    }
}
