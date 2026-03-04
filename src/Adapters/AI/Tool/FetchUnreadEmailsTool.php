<?php
declare(strict_types=1);
namespace App\Adapters\AI\Tool;

use App\Domain\Port\LastRunRepositoryInterface;
use App\Domain\Port\MailboxInterface;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

#[AsTool(
    name: 'fetch_unread_emails',
    description: 'Fetches unread emails from inbox since the last run. Returns JSON array of emails with id, subject, sender, body (max 2000 chars), and gmail_link.',
)]
final class FetchUnreadEmailsTool
{
    public function __construct(
        private readonly MailboxInterface $mailbox,
        private readonly LastRunRepositoryInterface $lastRun,
    ) {}

    public function __invoke(): string
    {
        $since = $this->lastRun->getLastRun() ?? new \DateTimeImmutable('-1 month');
        $emails = $this->mailbox->fetchUnread($since);
        $this->lastRun->saveLastRun(new \DateTimeImmutable());

        return json_encode(array_map(fn ($e) => [
            'id'         => $e->id,
            'subject'    => $e->subject,
            'sender'     => $e->sender,
            'body'       => mb_substr($e->body, 0, 2000),
            'gmail_link' => sprintf('https://mail.google.com/mail/u/0/#inbox/%s', $e->id),
        ], $emails), \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);
    }
}
