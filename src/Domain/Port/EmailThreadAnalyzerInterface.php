<?php

declare(strict_types=1);

namespace App\Domain\Port;

use App\Domain\Email\EmailThread;
use App\Domain\Email\EmailThreadAnalysis;

interface EmailThreadAnalyzerInterface
{
    public function analyze(EmailThread $thread, string $userEmail): EmailThreadAnalysis;
}
