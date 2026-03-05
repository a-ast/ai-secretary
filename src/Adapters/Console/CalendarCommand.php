<?php

declare(strict_types=1);

namespace App\Adapters\Console;

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

#[AsCommand(
    name: 'ai-sec:calendar',
    description: 'Fetch and summarize upcoming Google Calendar events.',
)]
final class CalendarCommand extends Command
{
    public function __construct(
        #[Autowire(service: 'ai.agent.calendar')]
        private readonly AgentInterface $agent,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('days', null, InputOption::VALUE_REQUIRED, 'Number of days to look ahead (1 = today, 7 = week)', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');

        try {
            $result = $this->agent->call(new MessageBag(
                Message::ofUser(sprintf(
                    'Fetch my calendar events for the next %d day(s), summarize what\'s coming up, and send me a Telegram notification.',
                    $days,
                )),
            ));
            $io->writeln($result->getContent());
        } catch (Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
