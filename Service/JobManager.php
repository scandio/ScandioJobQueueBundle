<?php
namespace Scandio\JobQueueBundle\Service;

use Symfony\Component\DependencyInjection\Container;
use Scandio\JobQueueBundle\Entity\Job;

class JobManager
{
    /**
     * @var array
     */
    private $workers = array();

    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    private $container;

    /**
     * @var bool
     */
    private $enabledRandomization;

    public function __construct(Container $container, array $workers = array(), $enabledRandomization = true)
    {
        $this->container = $container;
        $this->workers = $workers;
        $this->enabledRandomization = $enabledRandomization;
    }

    /**
     * @param $workerName
     * @param $command
     */
    public function queue($workerName, $command, $priority = Job::PRIORITY_NORMAL)
    {
        $this->container
            ->get('doctrine.orm.entity_manager')
            ->getRepository('Scandio\JobQueueBundle\Entity\Job')
            ->queue($workerName, $command, $priority);
    }

    /**
     * @param $command
     * @param int $priority
     */
    public function queueRandom($command, $priority = Job::PRIORITY_NORMAL)
    {
        $this->queue($this->getRandomWorker(), $command, $priority);
    }

    /**
     * @param $workerName
     * @param $searchTerm
     */
    public function search($field, $searchTerm)
    {
        return $this->container
            ->get('doctrine.orm.entity_manager')
            ->getRepository('Scandio\JobQueueBundle\Entity\Job')
            ->search($field, $searchTerm);
    }

    /**
     * @param $workerName
     * @return mixed
     */
    public function clean($workerName)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');

        $lockRepository = $em->getRepository('Scandio\JobQueueBundle\Entity\Lock');
        $jobRepository = $em->getRepository('Scandio\JobQueueBundle\Entity\Job');

        $rowsAffected = $jobRepository->resetAll($workerName);
        $lockRepository->release($workerName);

        return $rowsAffected;
    }

    /**
     * @param $workerName
     * @return array
     */
    public function stats($workerName)
    {
        $workers = $this->workers;
        if (!empty($workerName)) {
            $workers = array($workerName);
        }

        $em = $this->container->get('doctrine.orm.entity_manager');

        $lockRepository = $em->getRepository('Scandio\JobQueueBundle\Entity\Lock');
        $jobRepository = $em->getRepository('Scandio\JobQueueBundle\Entity\Job');

        $statistics = array();
        foreach ($workers as $workerName) {
            $isLockedSince = $lockRepository->isLockedSince($workerName);

            $statistics[$workerName] = array(
                'isLocked' => (int) ($isLockedSince instanceof \DateTime),
                'isLockedSince' => $isLockedSince instanceof \DateTime ? $isLockedSince->format('d.m.Y H:i:s') : '',
                'finished' => $jobRepository->count($workerName, Job::STATE_FINISHED),
                'queued' => $jobRepository->count($workerName, Job::STATE_QUEUE),
                'active' => $jobRepository->count($workerName, Job::STATE_ACTIVE),
                'paused' => $jobRepository->count($workerName, Job::STATE_PAUSED),
                'inactive' => $jobRepository->count($workerName, Job::STATE_INACTIVE),
                'dead' => (int) $lockRepository->isDead($workerName)
            );
        }

        return $statistics;
    }

    /**
     * @param $workerName
     * @param int $limit
     * @return mixed
     */
    public function history($workerName, $limit = 20)
    {
        $jobs = $this
            ->container
            ->get('doctrine.orm.entity_manager')
            ->getRepository('Scandio\JobQueueBundle\Entity\Job')
            ->getLastFinishedJobs($workerName, $limit);

        krsort($jobs);

        return $jobs;
    }

    /**
     * @param null $workerName
     * @param int $state
     * @return array
     */
    public function purge($workerName=null, $state = Job::STATE_FINISHED)
    {
        $workers = $this->workers;
        if (!empty($workerName)) {
            $workers = array($workerName);
        }
        $jobRepository = $this->container
            ->get('doctrine.orm.entity_manager')
            ->getRepository('Scandio\JobQueueBundle\Entity\Job');

        $message = array();
        foreach ($workers as $worker) {
            $count = $jobRepository->purge($workerName, $state);
            $message[$worker] = $count;
        }

        return $message;
    }

    /**
     * @param $workerName
     * @return int
     */
    public function stop($workerName)
    {
        return $this
            ->container
            ->get('doctrine.orm.entity_manager')
            ->getRepository('Scandio\JobQueueBundle\Entity\Job')
            ->stopAll($workerName);
    }

    /**
     * @param $workerName
     * @return int
     */
    public function resume($workerName)
    {
        return $this
            ->container
            ->get('doctrine.orm.entity_manager')
            ->getRepository('Scandio\JobQueueBundle\Entity\Job')
            ->resumeAll($workerName);
    }

    /**
     * @param $workerName
     * @return mixed
     */
    public function queuedCommands($workerName)
    {
        return $this
            ->container
            ->get('doctrine.orm.entity_manager')
            ->getRepository('Scandio\JobQueueBundle\Entity\Job')
            ->getQueuedCommands($workerName);
    }

    /**
     * @return string
     */
    public function getRandomWorker()
    {
        $workerName = 'default';
        if ($this->enabledRandomization && !empty($this->workers)) {
            $workerName = $this->workers[rand(0, (count($this->workers)-1) )];
        }

        return $workerName;
    }

    /**
     * @return array
     */
    public function getAllWorkers()
    {
        return $this->workers;
    }
}