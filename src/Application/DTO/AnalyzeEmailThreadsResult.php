<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Email\EmailThread;
use App\Domain\Email\EmailThreadAnalysis;

final class AnalyzeEmailThreadsResult
{
    public int $total = 0;
    public int $analyzed = 0;
    public int $actionable = 0;

    /**
     * @var array<array{thread: EmailThread, analysis: EmailThreadAnalysis}>
     */
    public array $actionableThreads = [];

    /** @var string[] */
    public array $errors = [];

    public function addError(string $subject, string $message): void
    {
        $this->errors[] = sprintf('["%s"] %s', $subject, $message);
    }

    public function hasErrors(): bool
    {
        return [] !== $this->errors;
    }
}
