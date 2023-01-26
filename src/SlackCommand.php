<?php declare(strict_types=1);

namespace App\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class SlackCommand extends Command{


    protected static $defaultName = 'slack';

    protected function configure(): void
    {
        $this->setDescription("Asks user to choose from the actions list.");
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $output->writeln([
            '====**** SLACK MESSAGE SENDER ****====',
            '==========================================',
            '',
        ]);

        $output->writeln([
            'What would you like to do?',
            '1. Send a message',
            '2. List templates',
            '3. Add a template',
            '4. Update a template',
            '5. Delete a template',
            '6. List users',
            '7. Add a user',
            '8. Show sent messages',
            '9. Exit',
            ''
        ]);

        $helper = $this->getHelper('question');

        $question = new Question('Please enter a number from the above options.', 'None');

        $choice = $helper->ask($input, $output, $question);

        switch ($choice) {
            case "1":
                echo "\nSEND A MESSAGE\n\n";
                break;
            case "2":
                echo "\nLIST TEMPLATES\n\n";
                $this->listTemplates();
                break;
            case "3":
                echo "\nADD A TEMPLATE\n\n";
                $this->addTemplate($input, $output);
                break;
            case "4":
                echo "\nUPDATE A TEMPLATE\n\n";
                $this->updateTemplate($input, $output);
                break;
            case "5":
                echo "\nDELETE A TEMPLATE\n\n";
                $this->deleteTemplate($input, $output);
                break;
            case "6":
                echo "\nLIST USERS\n\n";
                $this->listUsers();
                break;
            case "7":
                echo "\nADD A USER\n\n";
                break;
            case "8":
                echo "\nSHOW SENT MESSAGES\n\n";
                $this->sentMessages();
                break;
            case "9":
                echo "\nExit\n\n";
                break;
            default:
                echo 'no response chosen';
                break;
        }

        return Command::SUCCESS;
    }


    private function listTemplates()
    {
        $finder = new Finder();

        $finder->files()->in(__DIR__)->path('data')->name("templates.json");

        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $contents = $file->getContents();
                var_dump($contents);
            }
        }

    }

    private function addTemplate(InputInterface $input, OutputInterface $output): int
    {
        $filesystem = new Filesystem();
        $path = "./src/data/templates.json";
        $jsonString = file_get_contents($path);
        $templates = json_decode($jsonString, true);

        echo "Available variables:\n* {name}\n* {username}\n* {displayName}\n\n";

        $helper = $this->getHelper('question');

        $question = new Question('Enter your new template and press enter to save:', '');
        $newTemplate = $helper->ask($input, $output, $question);

        $lastKey = array_key_last($templates);
        $newId = $lastKey + 2;

        $jsonArray = json_encode(array(
            "id" => strval($newId),
            "message"  => $newTemplate
        ));

        $filesystem->appendToFile("./src/data/templates.json", $jsonArray, JSON_PRETTY_PRINT);

        return Command::SUCCESS;

    }


    private function updateTemplate(InputInterface $input, OutputInterface $output): int
    {
        $path = "./src/data/templates.json";
        $jsonString = file_get_contents($path);
        $templates = json_decode($jsonString, true);

        $helper = $this->getHelper('question');

        $whichTemplate = new Question('Which template would you like to update?', '');
        $selectedTemplate = $helper->ask($input, $output, $whichTemplate);

        $helper2 = $this->getHelper('question');
        $updatedTemplate = new Question('Enter your new template and press enter to save:', '');
        $newTemplate = $helper2->ask($input, $output, $updatedTemplate);

        foreach($templates as &$e) {
            if ($e['id'] === $selectedTemplate) {
                $e['message'] = $newTemplate;
            }
        }
        $finalArray = json_encode($templates, JSON_PRETTY_PRINT);
        $filesystem = new Filesystem();
        $filesystem->dumpFile($path, $finalArray);


        return Command::SUCCESS;
    }

    private function deleteTemplate(InputInterface $input, OutputInterface $output): int {

        $path = "./src/data/templates.json";
        $jsonString = file_get_contents($path);
        $templates = json_decode($jsonString, true);

        $helper = $this->getHelper('question');

        $this->listTemplates();

        $whichTemplate = new Question('Please type the id of the template you want to delete.', "");
        $selectedTemplate = $helper->ask($input, $output, $whichTemplate);

        foreach($templates as $key => &$value) {
            if ($value['id'] === $selectedTemplate) {
                unset($templates[$key]);
            }
        }


        $finalArray = json_encode(array_values($templates), JSON_PRETTY_PRINT);
        $filesystem = new Filesystem();
        $filesystem->dumpFile($path, $finalArray);

        return Command::SUCCESS;
    }

    private function listUsers()
    {

        $finder = new Finder();

        $finder->files()->in(__DIR__)->path('data')->name("users.json");

        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $contents = $file->getContents();
                var_dump($contents);
            }
        }
    }

    private function sentMessages()
    {
        $finder = new Finder();

        $finder->files()->in(__DIR__)->path('data')->name("messages.json");

        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $contents = $file->getContents();
                var_dump($contents);
            }
        }

    }

}