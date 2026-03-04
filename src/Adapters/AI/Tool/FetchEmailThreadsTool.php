<?php
declare(strict_types=1);
namespace App\Adapters\AI\Tool;

use App\Domain\Email\Email;
use App\Domain\Email\EmailThread;
use App\Domain\Port\MailboxInterface;
use DateTimeImmutable;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

#[AsTool(
    name: 'fetch_email_threads',
    description: 'Fetches email threads from the past N days. Returns JSON array of threads, each with id, subject, gmail_link, and messages (sender, recipients, date, body truncated to 500 chars).',
)]
final readonly class FetchEmailThreadsTool
{
    public function __construct(private MailboxInterface $mailbox) {}

    /**
     * @param int $days How many days back to look (default: 7)
     */
    public function __invoke(int $days = 7): string
    {
        $since = new DateTimeImmutable("-{$days} days");
        $threads = $this->mailbox->fetchThreadsSince($since);

        return json_encode(array_map(fn (EmailThread $thread) => [
            'id'         => $thread->id,
            'subject'    => $thread->subject(),
            'gmail_link' => sprintf('https://mail.google.com/mail/u/0/#all/%s', $thread->id),
            'messages'   => array_map(fn (Email $message, int $i) => [
                'sender'     => $message->sender,
                'recipients' => $message->recipients,
                'date'       => $message->receivedAt->format('Y-m-d H:i'),
                'body'       => ($i === 0 || $i === array_key_last($thread->messages))
                    ? $message->body
                    : mb_substr($message->body, 0, 2000),
            ], $thread->messages, array_keys($thread->messages)),
        ], $threads), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
