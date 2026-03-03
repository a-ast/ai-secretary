<?php

declare(strict_types=1);

namespace App\Adapters\Console;

use App\Application\UseCase\AnalyzeEmailsUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:analyze-emails',
    description: 'Fetch unread Gmail emails, analyze importance via AI, and notify via Telegram.',
)]
final class AnalyzeEmailsCommand extends Command
{
    public function __construct(private readonly AnalyzeEmailsUseCase $useCase)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Fetching and analyzing unread emails...');

        try {
            $result = $this->useCase->execute();
        } catch (\Throwable $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return Command::FAILURE;
        }

        $output->writeln(sprintf(
            'Processed <info>%d/%d</info> emails — <info>%d</info> important, <info>%d</info> Telegram notification(s) sent.',
            $result->processed,
            $result->total,
            $result->important,
            $result->notified,
        ));

        foreach ($result->errors as $error) {
            $output->writeln(sprintf('<comment>Warning: %s</comment>', $error));
        }

        return Command::SUCCESS;
    }
}
