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
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'ai-sec:email:thread',
    description: 'Review email threads for neglected conversations awaiting a reply.',
)]
final class ThreadReviewCommand extends Command
{
    public function __construct(
        #[Autowire(service: 'ai.agent.thread_review')]
        private readonly AgentInterface $agent,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('days', null, InputOption::VALUE_REQUIRED, 'How many days back to look', 7);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = (int) $input->getOption('days');

        try {
            $result = $this->agent->call(new MessageBag(
                Message::ofUser(sprintf(
                    'Review my email threads from the past %d day(s). Find any requiring action, notify me, and summarize.',
                    $days,
                )),
            ));
            $output->writeln($result->getContent());
        } catch (\Throwable $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
