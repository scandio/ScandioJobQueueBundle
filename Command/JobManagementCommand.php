<?php
namespace Scandio\JobQueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

class JobManagementCommand extends ContainerAwareCommand
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var array
     */
    private $locales;

    protected function configure()
    {
        $this
            ->setName('scandio:job-queue:manager')
            ->setDescription('Manages the job queue')
            ->addArgument('action', InputArgument::OPTIONAL)
            ->addOption('workerName', null, InputArgument::OPTIONAL, 'Worker Name', null)
            ->addOption('limit', null, InputArgument::OPTIONAL, 'Count limit', 20)
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $timeStart = microtime(true);

        $action = $input->getArgument('action');
        if (empty($action)) {
            $methods = get_class_methods($this);
            $allMethods = array();
            foreach ($methods as $method) {
                if (substr($method, -strlen('Action')) === 'Action') {
                    $m = explode('Action', $method);
                    $allMethods[] = reset($m);
                }
            }

            throw new \Exception(sprintf('Action is missing! Possible Actions: '.implode(', ', $allMethods)));
        }

        $function = $action.'Action';
        if (!method_exists($this, $function)) {
            throw new \Exception(sprintf('Action "%s" does not exist!', $action));
        }

        $message = $this->{$function}($input, $output);

        $output->writeln($message);
        $output->writeln('Worker executed in <info>'.round(microtime(true)-$timeStart, 2).'</info> Seconds');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param $workerName
     * @param $command
     */
    protected function insertAction(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');
        $workerName = $input->getOption('workerName');
        if (is_null($input->getOption('workerName'))) {
            $workerName = $dialog->ask($output, 'What worker should be used [default] :  ', 'default');
        }
        $command = $dialog->ask($output, 'Please enter the command to execute: ', '');

        if (!empty($command)) {
            $this->getContainer()->get('scandio.job_manager')->queue($workerName, $command);
        }

        return sprintf('added command "%s" to worker "%s"', $command, $workerName);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param $workerName
     * @param $command
     */
    protected function cleanAction(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');
        $workerName = $input->getOption('workerName');
        if (is_null($input->getOption('workerName'))) {
            $workerName = $dialog->ask($output, 'Which worker should be cleaned [default] :  ', 'default');
        }
        $userInput = $dialog->ask($output, 'Are you sure you want to reset the worker? Type "yes" or "no" [no]: ', 'no');


        $rowsAffected = 0;
        if (strtolower($userInput) == "yes") {
            $rowsAffected = $this->getContainer()->get('scandio.job_manager')->clean($workerName);
        }

        return sprintf('worker "%s" cleaned. %d rows have been cleaned!', $workerName, $rowsAffected);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return string
     */
    protected function purgeAction(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');
        $workerName = $input->getOption('workerName');
        $userInput = $dialog->ask($output, 'Are you sure you want to purge finished jobs? Type "yes" or "no" [no]: ', 'no');

        $messages = array();
        if (strtolower($userInput) == "yes") {
            $messages = $this->getContainer()->get('scandio.job_manager')->purge($workerName);
        }

        $outputLines = array();
        foreach ($messages as $worker => $affectedRows) {
            $outputLines[] = sprintf('worker "%s" purged. %d rows have been purged!', $worker, $affectedRows);
        }

        return $outputLines;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return string
     */
    protected function statsAction(InputInterface $input, OutputInterface $output)
    {
        $workerName = $input->getOption('workerName');
        $stats = $this->getContainer()->get('scandio.job_manager')->stats($workerName);

        foreach ($stats as $worker => $workerStats) {
            $output->writeln($worker);
            $output->writeln('----------------------------------------');
            foreach ($workerStats as $label => $count) {
                $output->writeln($label.': '.$count);
            }
            $output->writeln('----------------------------------------');
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function historyAction(InputInterface $input, OutputInterface $output)
    {
        $limit = $input->getOption('limit');
        $workerName = $input->getOption('workerName');
        if (is_null($input->getOption('workerName'))) {
            $dialog = $this->getHelperSet()->get('dialog');
            $workerName = $dialog->ask($output, 'Show last finished jobs [default] :  ', 'default');
        }

        $jobs = $this->getContainer()->get('scandio.job_manager')->history($workerName, $limit);

        foreach ($jobs as $job) {
            $output->writeln(array(
                '------------------------------------------------------',
                'Worker: <info>'.$job->getWorkerName().'</info>',
                'ID: <info>'.$job->getId().'</info>',
                'Command: <error>'.$job->getCommand().'</error>',
                'Priority: <info>'.$job->getPriority().'</info>',
                'Output: ',
                $job->getOutput(),
                'Started at: <info>'.$job->getStartedAt()->format('d.m.Y H:i:s').'</info>',
                'Finished at: <info>'.$job->getFinishedAt()->format('d.m.Y H:i:s').'</info>',
                'Execution Time: <info>'.$job->getExecutionTimeString().'</info>',
                '------------------------------------------------------',
                '',
            ));
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function searchAction(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');
        $searchField = $dialog->ask($output, 'What field? [command] :  ', 'command');
        $searchTerm = $dialog->ask($output, 'Search :  ');

        if (strlen($searchTerm) >= 1) {
            $jobs = $this->getContainer()->get('scandio.job_manager')->search($searchField, $searchTerm);

            foreach ($jobs as $job) {
                $output->writeln(array(
                    '{'.$job->getState().'}'
                        .' #C:'.($job->getCreatedAt() instanceof \DateTime ? $job->getCreatedAt()->format('d.m.Y H:i:s') : '-')
                        .' #S:'.($job->getStartedAt() instanceof \DateTime ? $job->getStartedAt()->format('d.m.Y H:i:s') : '-')
                        .' #F:'.($job->getFinishedAt() instanceof \DateTime ? $job->getFinishedAt()->format('d.m.Y H:i:s') : '-'),
                    '<info>['.$job->getId().'] </info> #'.$job->getCommand(),
                    $job->getOutput(),
                    ''
                ));
            }
        } else {
            $output->writeln(array('Search Term was too short. Aborting'));
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return string
     */
    protected function stopAction(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');
        $workerName = $input->getOption('workerName');
        if (is_null($input->getOption('workerName'))) {
            $workerName = $dialog->ask($output, 'Stop worker [default] :  ', 'default');
        }
        $userInput = $dialog->ask($output, 'Are you sure you want to stop the current worker? Type "yes" or "no" [no]: ', 'no');

        $rowsAffected = 0;
        if (strtolower($userInput) == "yes") {
            $rowsAffected = $this->getContainer()->get('scandio.job_manager')->stop($workerName);
        }

        return sprintf('worker "%s" stopped. %d jobs have been stopped!', $workerName, $rowsAffected);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return string
     */
    protected function resumeAction(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');
        $workerName = $input->getOption('workerName');
        if (is_null($input->getOption('workerName'))) {
            $workerName = $dialog->ask($output, 'Resume worker [default] :  ', 'default');
        }
        $userInput = $dialog->ask($output, 'Are you sure you want to resume the current worker? Type "yes" or "no" [no]: ', 'no');

        $rowsAffected = 0;
        if (strtolower($userInput) == "yes") {
            $rowsAffected = $this->getContainer()->get('scandio.job_manager')->resume($workerName);
        }

        return sprintf('worker "%s" resumed. %d jobs have been resumed!', $workerName, $rowsAffected);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return mixed
     */
    protected function queuedCommandsAction(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelperSet()->get('dialog');
        $workerName = $input->getOption('workerName');
        if (is_null($input->getOption('workerName'))) {
            $workerName = $dialog->ask($output, 'Queued Commands [default] :  ', 'default');
        }

        return $this->getContainer()->get('scandio.job_manager')->queuedCommands($workerName);
    }
}
