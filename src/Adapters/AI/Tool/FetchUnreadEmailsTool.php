<?php
declare(strict_types=1);
namespace App\Adapters\AI\Tool;

use App\Domain\Email\Email;
use App\Domain\Port\LastRunRepositoryInterface;
use App\Domain\Port\MailboxInterface;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

#[AsTool(
    name: 'fetch_unread_emails',
    description: 'Fetches unread emails from inbox since the last run. Returns JSON array of emails with id, subject, sender, body (max 2000 chars), and gmail_link.',
)]
final readonly class FetchUnreadEmailsTool
{
    public function __construct(
        private MailboxInterface $mailbox,
        private LastRunRepositoryInterface $lastRun,
    ) {}

    public function __invoke(): string
    {
        $since = $this->lastRun->getLastRun() ?? new \DateTimeImmutable('-1 month');
        $emails = $this->mailbox->fetchUnread($since);

        return json_encode(array_map(fn (Email $email) => [
            'id'         => $email->id,
            'subject'    => $email->subject,
            'sender'     => $email->sender,
            'body'       => mb_substr($email->body, 0, 2000),
            'gmail_link' => sprintf('https://mail.google.com/mail/u/0/#inbox/%s', $email->id),
        ], $emails), \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);
    }
}
