<?php
namespace Scandio\JobQueueBundle\Entity;

class Job
{
    const STATE_QUEUE = 1;
    const STATE_ACTIVE = 2;
    const STATE_FINISHED = 3;
    const STATE_PAUSED = 4;
    const STATE_INACTIVE = 5;

    const PRIORITY_LOWEST = 1;
    const PRIORITY_LOW = 2;
    const PRIORITY_NORMAL = 3;
    const PRIORITY_HIGHER = 4;
    const PRIORITY_HIGHEST = 5;

    /**
     * @var integer $id
     *
     */
    private $id;

    /**
     * @var string $workerName
     *
     */
    private $workerName;

    /**
     * @var string $command
     *
     */
    private $command;

    /**
     * @var string $state
     */
    private $state;

    /**
     * @var integer $state
     */
    private $priority;

    /**
     * @var string $output
     */
    private $output;

    /**
     * @var datetime $createdAt
     */
    private $createdAt;

    /**
     * @var datetime $createdAt
     */
    private $startedAt;

    /**
     * @var datetime $createdAt
     */
    private $finishedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->state = self::STATE_QUEUE;
        $this->priority = self::PRIORITY_NORMAL;
    }

    /**
     * @param int $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param string $outuput
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param \Scandio\JobQueueBundle\Entity\datetime $startedAt
     */
    public function setStartedAt($startedAt)
    {
        $this->startedAt = $startedAt;
    }

    /**
     * @return \Scandio\JobQueueBundle\Entity\datetime
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }

    /**
     * @param \Scandio\JobQueueBundle\Entity\datetime $finishedAt
     */
    public function setFinishedAt($finishedAt)
    {
        $this->finishedAt = $finishedAt;
    }

    /**
     * @return \Scandio\JobQueueBundle\Entity\datetime
     */
    public function getFinishedAt()
    {
        return $this->finishedAt;
    }

    /**
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set workerName
     *
     * @param string $workerName
     * @return Queue
     */
    public function setWorkerName($workerName)
    {
        $this->workerName = $workerName;
        return $this;
    }

    /**
     * Get workerName
     *
     * @return string 
     */
    public function getWorkerName()
    {
        return $this->workerName;
    }

    /**
     * Set command
     *
     * @param string $command
     * @return Queue
     */
    public function setCommand($command)
    {
        $this->command = $command;
        return $this;
    }

    /**
     * Get command
     *
     * @return string 
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Set createdAt
     *
     * @param datetime $createdAt
     * @return Queue
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return datetime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param string $format
     * @return null
     */
    public function getExecutionTimeString($format = '%H hours, %I minutes, %S seconds')
    {
        $time = null;
        if ($this->getState() == self::STATE_FINISHED) {
            $time = $this->finishedAt->diff($this->startedAt)->format($format);
        }
        return $time;
    }
}