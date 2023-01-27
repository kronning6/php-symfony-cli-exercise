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
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Helper\Table;

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


        $keepGoing = true;

        while ($keepGoing) {
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

            $question = new Question("Please enter a number from the above options. ", 'None');

            $choice = $helper->ask($input, $output, $question);


            switch ($choice) {
                case "1":
                    echo "\nSEND A MESSAGE\n\n";
                    $this->sendMessage($input, $output);
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
                    $this->addUser($input, $output);
                    break;
                case "8":
                    echo "\nSHOW SENT MESSAGES\n\n";
                    $this->sentMessages($input, $output);
                    break;
                case "9":
                    echo "Have a nice day!";
                    $keepGoing = false;
                    break;
                default:
                    echo "\nOPE! You need to select a number silly!\n\n";
                    break;
            }
        }


        return Command::SUCCESS;
    }

    private function sendMessage(InputInterface $input, OutputInterface $output): int {

        $path1 = "./src/data/templates.json";
        $path2 = "./src/data/users.json";
        $path3 = "./src/data/messages.json";
        $jsonString1 = file_get_contents($path1);
        $jsonString2 = file_get_contents($path2);
        $jsonString3 = file_get_contents($path3);
        $templates = json_decode($jsonString1, true);
        $users = json_decode($jsonString2, true);
        $sentMessages = json_decode($jsonString3, true);

        $helper = $this->getHelper('question');

        $this->listTemplates();

        $whichTemplate = new Question("What template? \n", '1');
        $selectedTemplate = $helper->ask($input, $output, $whichTemplate);

        foreach($templates as &$e) {
            if ($e['id'] === $selectedTemplate) {
                $message = $e['message'];
            }
        }

        $this->listUsers();

        $whichUser = new Question("\n\nWhat user (Please type their name)? \n", 'name');
        $selectedUser = $helper->ask($input, $output, $whichUser);

        foreach($users as &$e) {
            if ($e['name'] === $selectedUser) {
                $user = $e['name'];
            }
        }

        echo "Sending to " . $user . ":\n\n";
        $message = str_replace("{name}", $user, $message);
        echo $message . "\n\n";
        $sendMessage = new Question("Enter 'yes' to send.", 'No');
        $choice = $helper->ask($input, $output, $sendMessage);

        $command = [
            'curl -X POST --data-urlencode 
            \"payload={\"channel\": \"#accelerated-engineer-program\", 
            \"username\": \"yourname\", 
            \"text\": \"textChange.\", 
            \"icon_emoji\": \":ghost:\"}\"https://hooks.slack.com/services/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'];
        $command = str_replace("yourname", $user, $command);
        $finalCommand = str_replace("textChange", $message, $command);

        $process = new Process($finalCommand);

        if ($choice === 'yes') {
            $process->run();
        } else {
            echo "You chose not to send the message.";
        }

        $lastKey = array_key_last($sentMessages);
        $newId = $lastKey + 2;
        $date = date('d-m-y h:i:s');
        $jsonArray = [[
            "id" => strval($newId),
            "message"  => $message,
            "date" => $date
        ]];

        $merge = array_merge($sentMessages, $jsonArray);

        $finalArray = json_encode(array_values($merge), JSON_PRETTY_PRINT);

        $filesystem = new Filesystem();
        $filesystem->dumpFile($path3, $finalArray);


        return Command::SUCCESS;
    }

    private function listTemplates()
    {
        $finder = new Finder();

        $finder->files()->in(__DIR__)->path('data')->name("templates.json");

        $num = 1;
        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $contents = $file->getContents();
                $templates = json_decode($contents, true);
                foreach($templates as $e) {
                    echo strval($num) . "." . $e['message'] . "\n";
                    $num++;
                }
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

        $jsonArray = [[
            "id" => strval($newId),
            "message"  => $newTemplate
        ]];

        $merge = array_merge($templates, $jsonArray);

        $finalArray = json_encode(array_values($merge), JSON_PRETTY_PRINT);

        $filesystem->dumpFile($path, $finalArray);

        return Command::SUCCESS;

    }


    private function updateTemplate(InputInterface $input, OutputInterface $output): int
    {
        $path = "./src/data/templates.json";
        $jsonString = file_get_contents($path);
        $templates = json_decode($jsonString, true);

        $helper = $this->getHelper('question');

        $whichTemplate = new Question('Which template would you like to update?', '1');
        $num = 1;
        foreach($templates as $e) {
            echo strval($num) . "." . $e['message'] . "\n";
            $num++;
        }
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

        $whichTemplate = new Question('Please type the id of the template you want to delete.', '1');
        $num = 1;
        foreach($templates as $e) {
            echo strval($num) . "." . $e['message'] . "\n";
            $num++;
        }
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

        $num = 1;
        if ($finder->hasResults()) {
            foreach ($finder as $file) {
               $contents = $file->getContents();
               $users = json_decode($contents, true);
                    foreach($users as $e) {
                        echo strval($num) . "." . $e['name'] . "\n";
                        $num++;
                    }
           }
        }
    }

    private function addUser(InputInterface $input, OutputInterface $output): int {

        $filesystem = new Filesystem();
        $path = "./src/data/users.json";
        $jsonString = file_get_contents($path);
        $users = json_decode($jsonString, true);

        $helper = $this->getHelper('question');

        $question = new Question('Enter the user\'s name: ', 'name');
        echo "\n";
        $name = $helper->ask($input, $output, $question);
        $question2 = new Question('Enter the user\'s ID: ', 'userID');
        echo "\n";
        $userID = $helper->ask($input, $output, $question2);
        $question3 = new Question('Enter the user\'s username: ', 'username');
        echo "\n";
        $username = $helper->ask($input, $output, $question3);
        $question4 = new Question('Enter the user\'s display name: ', 'display name');
        echo "\n";
        $displayName = $helper->ask($input, $output, $question4);

        $jsonArray = [[
            "name" => $name,
            "userID"  => $userID,
            "username" => $username,
            "displayName" => $displayName
        ]];

        $merge = array_merge($users, $jsonArray);
        $finalArray = json_encode(array_values($merge), JSON_PRETTY_PRINT);
        $filesystem->dumpFile($path, $finalArray);

        return Command::SUCCESS;
    }


    private function sentMessages(InputInterface $input, OutputInterface $output): int
    {
        $path = "./src/data/messages.json";
        $jsonString = file_get_contents($path);
        $messages = json_decode($jsonString, true);

        var_dump($messages);
        $table = new Table($output);
        $table->setHeaders(['DATE', 'MESSAGE']);
        foreach($messages as $e) {
            var_dump($e);
            $table-> setRows([
                [$e['date'], $e['message']]
            ]);
        };

        $table->render();

        return Command::SUCCESS;

    }

}