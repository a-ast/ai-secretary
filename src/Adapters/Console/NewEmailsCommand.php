<?php
declare(strict_types=1);
namespace App\Adapters\Console;

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'ai-sec:email:new-emails',
    description: 'Classify new unread emails via AI and send Telegram notifications for important ones.',
)]
final class NewEmailsCommand extends Command
{
    public function __construct(
        #[Autowire(service: 'ai.agent.new_email')]
        private readonly AgentInterface $agent,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = $this->agent->call(new MessageBag(
                Message::ofUser('Process my unread emails: fetch them, classify importance, notify me of the important ones, and summarize.'),
            ));
            $output->writeln($result->getContent());
        } catch (\Throwable $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
