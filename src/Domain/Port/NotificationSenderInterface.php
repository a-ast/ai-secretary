<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Agent\ActionItem;

interface NotificationSenderInterface
{
    public function send(ActionItem $item, string $template = 'telegram/action-item.html.twig'): void;
}
