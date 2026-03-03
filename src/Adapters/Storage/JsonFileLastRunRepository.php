<?php

declare(strict_types=1);

namespace App\Adapters\Storage;

use App\Domain\Port\LastRunRepositoryInterface;

final class JsonFileLastRunRepository implements LastRunRepositoryInterface
{
    public function __construct(private readonly string $filePath) {}

    public function getLastRun(): ?\DateTimeImmutable
    {
        if (!file_exists($this->filePath)) {
            return null;
        }

        $data = json_decode(file_get_contents($this->filePath), true);
        $value = $data['last_run'] ?? null;

        if (null === $value) {
            return null;
        }

        return new \DateTimeImmutable($value);
    }

    public function saveLastRun(\DateTimeImmutable $runAt): void
    {
        $dir = \dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        file_put_contents($this->filePath, json_encode(
            ['last_run' => $runAt->format(\DateTimeInterface::ATOM)],
            \JSON_PRETTY_PRINT,
        ));
    }
}
