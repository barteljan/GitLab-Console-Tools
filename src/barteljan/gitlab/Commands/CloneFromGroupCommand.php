<?php
/**
 * Created by PhpStorm.
 * User: bartel
 * Date: 16.01.16
 * Time: 09:00
 */

namespace barteljan\gitlab\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Process\Process;

class CloneFromGroupCommand extends Command{


    protected function configure()
    {

        $this->setName("gitlab:clone:group")
            ->setDescription("Clone all repositories from a gitlab group.")
            ->setDefinition(array(
                new InputOption('path', 'p', InputOption::VALUE_REQUIRED, 'Path to your gitlab instance'),
                new InputOption('group', 'g', InputOption::VALUE_REQUIRED, 'Group in your gitlab instance'),
                new InputOption('token', 't', InputOption::VALUE_REQUIRED, 'Your gitlab api token')
            ))
            ->setHelp(<<<EOT
Clone all repositories from one gitlab group.
You need ssh-auth for that.

Usage:

<info>php console.php gitlab:clone:group -p"/path/to/your/gitlab" -g"YourGitlabGroup" -t"YourApiToken"</info>

EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $header_style = new OutputFormatterStyle('black', 'white', array('bold'));
        $output->getFormatter()->setStyle('header', $header_style);

        $path = $input->getOption("path");

        if(empty($path)){
            throw new \InvalidArgumentException('Gitlab path not specified');
        }

        $groupName      = $input->getOption("group");

        if(empty($groupName)){
            throw new \InvalidArgumentException('Gitlab group not specified');
        }

        $token      = $input->getOption("token");

        if(empty($token)){
            throw new \InvalidArgumentException('Gitlab token not specified');
        }

        $client = new \Gitlab\Client('http://'.$path.'/api/v3/');
        $client->authenticate($token, \Gitlab\Client::AUTH_URL_TOKEN);

        $groupsApi = $client->api("groups");

        $foundGroups = $groupsApi->search($groupName,1,1000);

        if(count($foundGroups)>0){

            foreach($foundGroups as $groupData){
                $groupId = $groupData["id"];
                $group   =  \Gitlab\Model\Group::fromArray($client,$groupsApi->show($groupId));
                $output->writeln('<header>Clone repositories from Group "'.$group->name.'" ...</header>');

                foreach($group->projects as $project){
                    $cloningPath = $project->ssh_url_to_repo;

                    //clone repo
                    $cloneCommand = 'git clone '.$cloningPath;
                    $output->writeln('  '.$cloneCommand);
                    $process = new Process('git clone '.$cloningPath);

                    $process->setTimeout(3600);
                    $process->run(function ($type, $buffer) {
                        global $output;
                        echo '       '.$buffer;
                    });



                }
            }

        }else{
            throw new \ErrorException("No group containing: ".$groupName." found");
        }
    }

}