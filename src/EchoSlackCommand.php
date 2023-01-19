<?php

namespace App\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class EchoSlackCommand extends Command
{
    protected static $defaultResponse = 'echo:response';

    protected function configure(): void
    {
        $this->setDescription("Executes a command from the list chosen by the user");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        $question = new Question('What would you like to do?', 'response');

        $response = $helper->ask($input, $output, $question);

        $output->writeln("I WANT TO $response");

        return Command::SUCCESS;
    }
}