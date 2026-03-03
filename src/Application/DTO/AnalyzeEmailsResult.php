<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class AnalyzeEmailsResult
{
    public int $total = 0;
    public int $processed = 0;
    public int $important = 0;
    public int $notified = 0;

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
