<?php

declare(strict_types=1);

namespace App\Domain\Email;

final readonly class EmailThread
{
    /**
     * @param Email[] $messages Ordered oldest → newest
     */
    public function __construct(
        public string $id,
        public array $messages,
    ) {}

    public function lastMessage(): Email
    {
        return $this->messages[array_key_last($this->messages)];
    }

    public function subject(): string
    {
        return $this->messages[0]->subject ?? '(no subject)';
    }
}
