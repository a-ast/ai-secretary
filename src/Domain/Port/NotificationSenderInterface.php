<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Email\Email;
use App\Domain\Email\EmailAnalysis;

interface NotificationSenderInterface
{
    public function sendImportantEmailAlert(Email $email, EmailAnalysis $analysis): void;
}
