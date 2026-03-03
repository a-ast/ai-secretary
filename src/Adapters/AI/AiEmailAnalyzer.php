<?php

declare(strict_types=1);

namespace App\Adapters\AI;

use App\Domain\Email\Email;
use App\Domain\Email\EmailAnalysis;
use App\Domain\Port\EmailAnalyzerInterface;
use Symfony\AI\Platform\Exception\RateLimitExceededException;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\PlatformInterface;

final class AiEmailAnalyzer implements EmailAnalyzerInterface
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
        You are an email importance classifier. Analyze the given email and decide if it requires immediate attention.

        An email is important if it:
        - Requires a direct response or action from the recipient
        - Contains a deadline, alert, or time-sensitive information
        - Is a personal message from a known person (not a mailing list)
        - Is a business communication requiring a decision

        An email is NOT important if it is:
        - A newsletter, promotional, or marketing email
        - An automated notification with no required action
        - Spam or bulk mail

        Respond ONLY with a JSON object, no markdown fences:
        {"important": true, "reason": "brief explanation in one sentence"}
        PROMPT;

    private const MAX_ATTEMPTS = 4;

    /** Optional delay between calls in microseconds (e.g. 4_100_000 for Gemini free tier). */
    private float $lastCallAt = 0.0;

    public function __construct(
        private readonly PlatformInterface $platform,
        private readonly string $model,
        private readonly int $requestDelayUs = 0,
    ) {}

    public function analyze(Email $email): EmailAnalysis
    {
        $userPrompt = sprintf(
            "From: %s\nSubject: %s\n\n%s",
            $email->sender,
            $email->subject,
            mb_substr($email->body, 0, 2000),
        );

        $messageBag = new MessageBag(
            Message::forSystem(self::SYSTEM_PROMPT),
            Message::ofUser($userPrompt),
        );

        $response = $this->invokeWithRetry($messageBag, $email->subject);

        $data = json_decode(trim($response), true);

        if (!is_array($data) || !array_key_exists('important', $data)) {
            throw new \RuntimeException(sprintf(
                'AI returned an unparsable response for "%s". Raw: %s',
                $email->subject,
                mb_substr($response, 0, 200),
            ));
        }

        return new EmailAnalysis(
            isImportant: (bool) $data['important'],
            reason: (string) ($data['reason'] ?? ''),
        );
    }

    private function invokeWithRetry(MessageBag $messageBag, string $emailSubject): string
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
                        'Rate limit exceeded for model "%s" while analyzing "%s" (failed after %d attempts).%s',
                        $this->model,
                        $emailSubject,
                        $attempt,
                        $e->getRetryAfter() !== null ? sprintf(' Retry after %ds.', $e->getRetryAfter()) : ' Check your API quota.',
                    ), 0, $e);
                }

                $sleepSeconds = 5 * (2 ** ($attempt - 1)); // 5s, 10s, 20s
                sleep($sleepSeconds);
            } catch (\Throwable $e) {
                throw new \RuntimeException(sprintf(
                    'AI platform error for model "%s" while analyzing "%s": %s',
                    $this->model,
                    $emailSubject,
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
