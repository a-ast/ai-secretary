<?php

declare(strict_types=1);

namespace App\Adapters\Console;

use App\Application\UseCase\AnalyzeEmailThreadsUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'ai-sec:email:threads',
    description: 'Analyze email threads to surface neglected conversations awaiting a reply.',
)]
final class CheckEmailThreadsCommand extends Command
{
    public function __construct(private readonly AnalyzeEmailThreadsUseCase $useCase)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('days', null, InputOption::VALUE_REQUIRED, 'How many days back to look', 7);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = (int) $input->getOption('days');

        $output->writeln(sprintf('Analyzing email threads for the last %d day(s)...', $days));

        try {
            $result = $this->useCase->execute($days);
        } catch (\Throwable $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return Command::FAILURE;
        }

        $output->writeln(sprintf(
            'Found <info>%d</info> threads — <info>%d</info> analyzed, <info>%d</info> require action.',
            $result->total,
            $result->analyzed,
            $result->actionable,
        ));

        if ($result->actionable > 0) {
            $output->writeln('');
        }

        foreach ($result->actionableThreads as ['thread' => $thread, 'analysis' => $analysis]) {
            $tag = $analysis->awaitingReply ? 'AWAITING REPLY' : 'NEEDS YOUR REPLY';
            $link = sprintf('https://mail.google.com/mail/u/0/#all/%s', $thread->id);

            $output->writeln(sprintf('<info>[%s]</info> %s', $tag, $thread->subject()));
            $output->writeln(sprintf('  Summary: %s', $analysis->summary));
            $output->writeln(sprintf('  Reason:  %s', $analysis->reason));
            $output->writeln(sprintf('  Link:    %s', $link));
            $output->writeln('');
        }

        foreach ($result->errors as $error) {
            $output->writeln(sprintf('<comment>Warning: %s</comment>', $error));
        }

        return Command::SUCCESS;
    }
}
