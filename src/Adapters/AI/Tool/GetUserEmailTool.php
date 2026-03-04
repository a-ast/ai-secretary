<?php
declare(strict_types=1);
namespace App\Adapters\AI\Tool;

use App\Domain\Port\MailboxInterface;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

#[AsTool(
    name: 'get_user_email',
    description: "Returns the authenticated user's Gmail email address.",
)]
final class GetUserEmailTool
{
    public function __construct(private readonly MailboxInterface $mailbox) {}

    public function __invoke(): string
    {
        return $this->mailbox->getUserEmail();
    }
}
