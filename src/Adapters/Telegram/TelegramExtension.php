<?php

declare(strict_types=1);

namespace App\Adapters\Telegram;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TelegramExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                name: 'telegram_escape',
                callable: $this->escape(...),
                options: ['is_safe' => ['html']]
            ),
        ];
    }

    private function escape(string $text): string
    {
        return preg_replace(
            pattern: '/([_*\[\]()~`>#+\-=|{}.!])/',
            replacement: '\\\\$1',
            subject: $text
        );
    }
}
