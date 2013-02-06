<?php
namespace Scandio\JobQueueBundle\Entity;

class Lock
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var \DateTime
     */
    private $lockedSince;

    /**
     * @var int
     */
    private $pid;

    public function __construct($name)
    {
        $this->name = $name;
        $this->lockedSince = new \DateTime();
        $this->pid = getmypid();
    }

    /**
     * @param int $pid
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    }

    /**
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @param \DateTime $lockedSince
     */
    public function setLockedSince($lockedSince)
    {
        $this->lockedSince = $lockedSince;
    }

    /**
     * @return \DateTime
     */
    public function getLockedSince()
    {
        return $this->lockedSince;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
