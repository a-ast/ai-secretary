<?php

declare(strict_types=1);

namespace App\Adapters\Gmail;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Gmail;

final readonly class GoogleClientFactory
{
    public function __construct(
        private string $credentialsPath,
        private string $tokenPath,
    ) {}

    public function create(): Client
    {
        $client = $this->createUnauthenticated();

        if (!file_exists($this->tokenPath)) {
            throw new \RuntimeException(
                'Gmail token not found. Run: php bin/console app:gmail-auth'
            );
        }

        $token = json_decode(file_get_contents($this->tokenPath), true);
        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            if (!$client->getRefreshToken()) {
                throw new \RuntimeException(
                    'Gmail token expired and no refresh token available. Run: php bin/console app:gmail-auth'
                );
            }

            $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            $this->saveToken($newToken);
        }

        return $client;
    }

    public function createUnauthenticated(): Client
    {
        if (!file_exists($this->credentialsPath)) {
            throw new \RuntimeException(
                sprintf('Gmail credentials not found at "%s". Download OAuth credentials from Google Cloud Console.', $this->credentialsPath)
            );
        }

        $client = new Client();
        $client->setAuthConfig($this->credentialsPath);
        $client->addScope(Gmail::GMAIL_READONLY);
        $client->addScope(Calendar::CALENDAR_READONLY);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        return $client;
    }

    public function saveToken(array $token): void
    {
        $dir = dirname($this->tokenPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        file_put_contents($this->tokenPath, json_encode($token));
    }
}
