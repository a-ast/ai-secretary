<?php

declare(strict_types=1);

namespace App\Adapters\Gmail;

use App\Domain\Email\Email;
use App\Domain\Email\EmailThread;
use App\Domain\Port\EmailThreadFetcherInterface;
use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\MessagePart;

final readonly class GmailEmailThreadFetcher implements EmailThreadFetcherInterface
{
    public function __construct(private Client $googleClient) {}

    public function getUserEmail(): string
    {
        $service = new Gmail($this->googleClient);

        return $service->users->getProfile('me')->getEmailAddress();
    }

    public function fetchThreadsSince(\DateTimeImmutable $since): array
    {
        $service = new Gmail($this->googleClient);

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

        foreach ($payload->getParts() ?? [] as $part) {
            if ('text/plain' === $part->getMimeType()) {
                return $this->decodeBase64Url((string) $part->getBody()->getData());
            }
        }

        foreach ($payload->getParts() ?? [] as $part) {
            if ('text/html' === $part->getMimeType()) {
                return strip_tags($this->decodeBase64Url((string) $part->getBody()->getData()));
            }
        }

        return '';
    }

    private function decodeBase64Url(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
