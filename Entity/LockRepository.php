<?php
namespace Scandio\JobQueueBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class LockRepository extends EntityRepository
{
    /**
     * @return string
     */
    private function getTableName()
    {
        return $this->getClassMetadata()->getTableName();
    }

    /**
     * @param $workerName
     * @param $field
     * @return mixed
     */
    private function getField($workerName, $field)
    {
        $table = $this->getTableName();
        $sql = "SELECT $field FROM $table WHERE name = :workerName LIMIT 1;";
        $params = array(
            'workerName' => $workerName
        );
        $result = $this->getEntityManager()->getConnection()->fetchAll($sql, $params);

        return !empty($result) && isset($result[0][$field]) ? $result[0][$field] : false;
    }

    /**
     * @param $workerName
     * @return bool
     */
    public function isLocked($workerName)
    {
        $result = $this->getField($workerName, 'name');

        return !empty($result);
    }

    /**
     * @param $workerName
     */
    public function lock($workerName)
    {
        $table = $this->getTableName();
        $sql = "INSERT INTO $table SET name=:workerName, locked_since=:lockedSince, process_id=:pid";
        $params = array(
            'workerName' => $workerName,
            'lockedSince' => date('Y-m-d H:i:s'),
            'pid' => getmypid()

        );
        return $this->getEntityManager()->getConnection()->executeQuery($sql, $params);
    }

    /**
     * @param $workerName
     */
    public function release($workerName)
    {
        $table = $this->getTableName();
        $sql = "DELETE FROM $table WHERE name=:workerName";
        $params = array(
            'workerName' => $workerName,
        );
        return $this->getEntityManager()->getConnection()->executeQuery($sql, $params);
    }

    /**
     * @param $workerName
     * @return bool|int
     */
    public function getPid($workerName)
    {
        $pid = $this->getField($workerName, 'process_id');

        return $pid;
    }

    /**
     * Works only on linux systems
     *
     * @param $workerName
     * @return bool
     */
    public function isDead($workerName)
    {
        $pid = $this->getPid($workerName);
        return !empty($pid) && function_exists('posix_getsid') && posix_getsid($pid) === false;
    }

    /**
     * @param $workerName
     * @return \DateTime|string
     */
    public function isLockedSince($workerName)
    {
        $lockedSince = $this->getField($workerName, 'locked_since');

        return $lockedSince ? new \DateTime($lockedSince) : false;
    }
}