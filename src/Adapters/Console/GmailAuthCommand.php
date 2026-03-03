<?php

declare(strict_types=1);

namespace App\Adapters\Console;

use App\Adapters\Gmail\GoogleClientFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'app:gmail-auth',
    description: 'Perform one-time Gmail OAuth2 authorization and save the token.',
)]
final class GmailAuthCommand extends Command
{
    public function __construct(private readonly GoogleClientFactory $factory)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $client = $this->factory->createUnauthenticated();

        $authUrl = $client->createAuthUrl();
        $output->writeln([
            '',
            'Open the following URL in your browser and authorize the application:',
            '',
            "  <href=$authUrl>$authUrl</>",
            '',
        ]);

        $helper = $this->getHelper('question');
        $code = $helper->ask($input, $output, new Question('Paste the authorization code here: '));

        $token = $client->fetchAccessTokenWithAuthCode(trim($code));

        if (isset($token['error'])) {
            $output->writeln(sprintf('<error>Authorization failed: %s</error>', $token['error_description'] ?? $token['error']));

            return Command::FAILURE;
        }

        $this->factory->saveToken($token);

        $output->writeln('<info>Authorization successful. Token saved.</info>');

        return Command::SUCCESS;
    }
}
