<?php

declare(strict_types=1);

namespace App\Adapters\Gmail;

use App\Domain\Email\Email;
use App\Domain\Port\EmailFetcherInterface;
use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\MessagePart;

final class GmailEmailFetcher implements EmailFetcherInterface
{
    public function __construct(
        private readonly Client $googleClient,
        private readonly int $maxResults = 10,
    ) {}

    public function fetchUnread(\DateTimeImmutable $since): array
    {
        $service = new Gmail($this->googleClient);

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
