<?php

declare(strict_types=1);

namespace App\Adapters\AI;

use App\Domain\Email\EmailThread;
use App\Domain\Email\EmailThreadAnalysis;
use App\Domain\Port\EmailThreadAnalyzerInterface;
use Symfony\AI\Platform\Exception\RateLimitExceededException;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\PlatformInterface;

final class AiEmailThreadAnalyzer implements EmailThreadAnalyzerInterface
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
        You are an email thread analyst. Given a conversation thread, determine whether the user needs to take action.

        The thread requires action if:
        - The user is waiting for a reply and that response is genuinely needed (not a natural end to the conversation)
        - Someone is waiting for the user's reply (a direct question, request, or proposal awaiting response)

        The thread does NOT require action if:
        - It is a newsletter, promotional email, or automated notification
        - The conversation has naturally concluded with no open items
        - The thread is a one-way FYI or announcement requiring no response

        Respond ONLY with a JSON object, no markdown fences:
        {"requiresAction": bool, "awaitingReply": bool, "needsUserReply": bool, "summary": "one-sentence thread summary", "reason": "brief explanation in one sentence"}
        PROMPT;

    private const MAX_ATTEMPTS = 4;
    private const MAX_MESSAGES = 10;
    private const MAX_BODY_CHARS = 500;

    /** Optional delay between calls in microseconds (e.g. 4_100_000 for Gemini free tier). */
    private float $lastCallAt = 0.0;

    public function __construct(
        private readonly PlatformInterface $platform,
        private readonly string $model,
        private readonly int $requestDelayUs = 0,
    ) {}

    public function analyze(EmailThread $thread, string $userEmail): EmailThreadAnalysis
    {
        $messageBag = new MessageBag(
            Message::forSystem(self::SYSTEM_PROMPT),
            Message::ofUser($this->buildPrompt($thread, $userEmail)),
        );

        $response = $this->invokeWithRetry($messageBag, $thread->subject());

        $data = json_decode(trim($response), true);

        if (!is_array($data) || !array_key_exists('requiresAction', $data)) {
            throw new \RuntimeException(sprintf(
                'AI returned an unparsable response for thread "%s". Raw: %s',
                $thread->subject(),
                mb_substr($response, 0, 200),
            ));
        }

        return new EmailThreadAnalysis(
            awaitingReply: (bool) ($data['awaitingReply'] ?? false),
            needsUserReply: (bool) ($data['needsUserReply'] ?? false),
            requiresAction: (bool) $data['requiresAction'],
            summary: (string) ($data['summary'] ?? ''),
            reason: (string) ($data['reason'] ?? ''),
        );
    }

    private function buildPrompt(EmailThread $thread, string $userEmail): string
    {
        $messages = $thread->messages;
        $total = count($messages);

        if ($total > self::MAX_MESSAGES) {
            $selected = array_merge(array_slice($messages, 0, 2), array_slice($messages, -5));
            $countNote = sprintf('(showing first 2 + last 5 of %d)', $total);
        } else {
            $selected = $messages;
            $countNote = sprintf('(%d message(s))', $total);
        }

        $lines = [
            sprintf('User email: %s', $userEmail),
            sprintf('Subject: %s', $thread->subject()),
            sprintf('Thread %s:', $countNote),
            '',
        ];

        foreach ($selected as $i => $message) {
            $lines[] = sprintf('--- Message %d ---', $i + 1);
            $lines[] = sprintf('From: %s', $message->sender);
            $lines[] = sprintf('Date: %s', $message->receivedAt->format('Y-m-d H:i'));
            $lines[] = mb_substr($message->body, 0, self::MAX_BODY_CHARS);
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    private function invokeWithRetry(MessageBag $messageBag, string $subject): string
    {
        $this->waitIfNeeded();

        $attempt = 0;

        while (true) {
            try {
                $result = $this->platform->invoke($this->model, $messageBag)->asText();
                $this->lastCallAt = microtime(true);

                return $result;
            } catch (RateLimitExceededException $e) {
                ++$attempt;

                if ($attempt >= self::MAX_ATTEMPTS) {
                    throw new \RuntimeException(sprintf(
                        'Rate limit exceeded for model "%s" while analyzing thread "%s" (failed after %d attempts).%s',
                        $this->model,
                        $subject,
                        $attempt,
                        $e->getRetryAfter() !== null ? sprintf(' Retry after %ds.', $e->getRetryAfter()) : ' Check your API quota.',
                    ), 0, $e);
                }

                $sleepSeconds = 5 * (2 ** ($attempt - 1)); // 5s, 10s, 20s
                sleep($sleepSeconds);
            } catch (\Throwable $e) {
                throw new \RuntimeException(sprintf(
                    'AI platform error for model "%s" while analyzing thread "%s": %s',
                    $this->model,
                    $subject,
                    $e->getMessage(),
                ), 0, $e);
            }
        }
    }

    private function waitIfNeeded(): void
    {
        if ($this->requestDelayUs <= 0 || $this->lastCallAt === 0.0) {
            return;
        }

        $elapsedUs = (int) ((microtime(true) - $this->lastCallAt) * 1_000_000);
        $remainingUs = $this->requestDelayUs - $elapsedUs;

        if ($remainingUs > 0) {
            usleep($remainingUs);
        }
    }
}
