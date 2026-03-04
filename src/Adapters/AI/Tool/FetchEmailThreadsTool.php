<?php
declare(strict_types=1);
namespace App\Adapters\AI\Tool;

use App\Domain\Port\MailboxInterface;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

#[AsTool(
    name: 'fetch_email_threads',
    description: 'Fetches email threads from the past N days. Returns JSON array of threads, each with id, subject, gmail_link, and messages (sender, recipients, date, body truncated to 500 chars).',
)]
final class FetchEmailThreadsTool
{
    public function __construct(private readonly MailboxInterface $mailbox) {}

    /**
     * @param int $days How many days back to look (default: 7)
     */
    public function __invoke(int $days = 7): string
    {
        $since = new \DateTimeImmutable("-{$days} days");
        $threads = $this->mailbox->fetchThreadsSince($since);

        return json_encode(array_map(fn ($t) => [
            'id'         => $t->id,
            'subject'    => $t->subject(),
            'gmail_link' => sprintf('https://mail.google.com/mail/u/0/#all/%s', $t->id),
            'messages'   => array_map(fn ($m, $i) => [
                'sender'     => $m->sender,
                'recipients' => $m->recipients,
                'date'       => $m->receivedAt->format('Y-m-d H:i'),
                'body'       => ($i === 0 || $i === array_key_last($t->messages))
                    ? $m->body
                    : mb_substr($m->body, 0, 2000),
            ], $t->messages, array_keys($t->messages)),
        ], $threads), \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);
    }
}
