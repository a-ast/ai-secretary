<?php

declare(strict_types=1);

namespace App\Adapters\Gmail;

use App\Domain\Email\Email;
use App\Domain\Email\EmailThread;
use App\Domain\Port\MailboxInterface;
use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\MessagePart;

final class GmailMailbox implements MailboxInterface
{
    public function __construct(
        private readonly Client $googleClient,
        private readonly int $maxResults = 10,
    ) {}

    public function getUserEmail(): string
    {
        return $this->gmail()->users->getProfile('me')->getEmailAddress();
    }

    public function fetchUnread(\DateTimeImmutable $since): array
    {
        $service = $this->gmail();

        $after = $since->format('Y/m/d');

        $listResponse = $service->users_messages->listUsersMessages('me', [
            'q' => "is:unread in:inbox after:$after",
            'maxResults' => $this->maxResults,
        ]);

        $emails = [];
        foreach ($listResponse->getMessages() ?? [] as $messageRef) {
            $message = $service->users_messages->get('me', $messageRef->getId(), ['format' => 'full']);
            $emails[] = $this->mapToEmail($message);
        }

        return $emails;
    }

    public function fetchThreadsSince(\DateTimeImmutable $since): array
    {
        $service = $this->gmail();

        $after = $since->format('Y/m/d');
        $threads = [];
        $pageToken = null;

        do {
            $params = ['q' => "in:anywhere after:$after", 'maxResults' => 100];
            if ($pageToken !== null) {
                $params['pageToken'] = $pageToken;
            }

            $listResponse = $service->users_threads->listUsersThreads('me', $params);

            foreach ($listResponse->getThreads() ?? [] as $threadRef) {
                $thread = $service->users_threads->get('me', $threadRef->getId(), ['format' => 'full']);

                $messages = [];
                foreach ($thread->getMessages() ?? [] as $message) {
                    $messages[] = $this->mapToEmail($message);
                }

                if ([] !== $messages) {
                    $threads[] = new EmailThread($thread->getId(), $messages);
                }
            }

            $pageToken = $listResponse->getNextPageToken();
        } while ($pageToken !== null);

        return $threads;
    }

    private function mapToEmail(Gmail\Message $message): Email
    {
        $headers = [];
        foreach ($message->getPayload()->getHeaders() as $header) {
            $headers[$header->getName()] = $header->getValue();
        }

        return new Email(
            id: $message->getId(),
            subject: $headers['Subject'] ?? '(no subject)',
            body: $this->extractBody($message->getPayload()),
            sender: $headers['From'] ?? 'unknown',
            receivedAt: new \DateTimeImmutable('@' . intdiv((int) $message->getInternalDate(), 1000)),
            recipients: $headers['To'] ?? '',
        );
    }

    private function extractBody(MessagePart $payload): string
    {
        if ('text/plain' === $payload->getMimeType()) {
            return $this->decodeBase64Url((string) $payload->getBody()->getData());
        }

        $htmlFallback = null;
        foreach ($payload->getParts() ?? [] as $part) {
            if ('text/plain' === $part->getMimeType()) {
                return $this->decodeBase64Url((string) $part->getBody()->getData());
            }
            if ('text/html' === $part->getMimeType()) {
                $htmlFallback = $part;
            }
        }
        if ($htmlFallback !== null) {
            return strip_tags($this->decodeBase64Url((string) $htmlFallback->getBody()->getData()));
        }

        return '';
    }

    private function gmail(): Gmail
    {
        return new Gmail($this->googleClient);
    }

    private function decodeBase64Url(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
