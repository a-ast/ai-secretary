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
            new TwigFilter('telegram_escape', $this->escape(...), ['is_safe' => ['html']]),
        ];
    }

    private function escape(string $text): string
    {
        return preg_replace('/([_*\[\]()~`>#+\-=|{}.!])/', '\\\\$1', $text);
    }
}
