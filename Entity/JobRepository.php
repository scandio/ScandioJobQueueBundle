<?php

namespace Scandio\JobQueueBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Scandio\JobQueueBundle\Entity\Job;

class JobRepository extends EntityRepository
{
    /**
     * @param $workerName
     * @param $command
     * @return int
     */
    public function queue($workerName, $command, $priority = Job::PRIORITY_NORMAL)
    {
        $dbal = $this->getEntityManager()->getConnection();

        $tableName = $this
            ->getEntityManager()
            ->getClassMetadata('Scandio\JobQueueBundle\Entity\Job')
            ->getTableName();

        $sql = <<<SQL
INSERT INTO $tableName (state, created_at, command, worker_name, priority)
VALUES (:state, :createdAt, :command, :workerName, :priority)
SQL;

        return $dbal->executeQuery($sql, array(
            'state' => Job::STATE_QUEUE,
            'workerName' => $workerName,
            'createdAt' => date('Y-m-d H:i:s'),
            'command' => $command,
            'priority' => $priority
        ));
    }

    /**
     * @param Job $job
     * @param int $priority
     */
    public function prioritize(Job $job, $priority)
    {
        $job->setPriority($priority);

        $em = $this->getEntityManager();
        $em ->persist($job);
        $em ->flush();
    }

    /**
     * @param $workerName
     */
    public function getNextJob($workerName)
    {
        $qb = $this->createQueryBuilder('j');
        $qb ->where('j.workerName = :workerName')
            ->andWhere('j.state = :state')
            ->orderBy('j.priority', 'DESC')
            ->addOrderBy('j.createdAt', 'ASC')
            ->addOrderBy('j.id', 'ASC')
            ->setMaxResults(1);

        $qb ->setParameters(array(
            'workerName' => $workerName,
            'state' => Job::STATE_QUEUE
        ));

        $job = $qb->getQuery()->getResult();

        return !empty($job) ? array_pop($job) : false;
    }

    /**
     * @param \Scandio\JobQueueBundle\Entity\Job $job
     */
    public function start(\Scandio\JobQueueBundle\Entity\Job $job)
    {
        $job->setStartedAt(new \DateTime());
        $job->setState(Job::STATE_ACTIVE);

        $em = $this->getEntityManager();
        $em ->persist($job);
        $em ->flush();
    }

    /**
     * @param \Scandio\JobQueueBundle\Entity\Job $job
     * @param $output
     */
    public function finish(\Scandio\JobQueueBundle\Entity\Job $job, $output)
    {
        $job->setFinishedAt(new \DateTime());
        $job->setState(Job::STATE_FINISHED);
        $job->setOutput($output);

        $em = $this->getEntityManager();
        $em ->persist($job);
        $em ->flush();
    }

    /**
     * @param $workerName
     */
    public function resetAll($workerName)
    {
        $dbal = $this->getEntityManager()->getConnection();

        $tableName = $this
            ->getEntityManager()
            ->getClassMetadata('Scandio\JobQueueBundle\Entity\Job')
            ->getTableName();

        $waiting = Job::STATE_QUEUE;
        $active = Job::STATE_ACTIVE;

        $sql = <<<SQL
UPDATE $tableName SET state = $waiting WHERE state = $active AND worker_name = "$workerName";
SQL;

        return $dbal->exec($sql);
    }

    /**
     * @param $workerName
     * @param $state
     * @return mixed
     */
    public function count($workerName, $state)
    {
        $dbal = $this->getEntityManager()->getConnection();

        $tableName = $this
            ->getEntityManager()
            ->getClassMetadata('Scandio\JobQueueBundle\Entity\Job')
            ->getTableName();

        $sql = <<<SQL
SELECT COUNT(*) AS count FROM $tableName WHERE state = :state AND worker_name = :workerName LIMIT 1;
SQL;
        $stat = $dbal->executeQuery($sql, array('state' => $state, 'workerName' => $workerName))->fetch();

        return $stat['count'];
    }

    /**
     * @param $workerName
     * @param int $limit
     * @return array
     */
    public function getLastFinishedJobs($workerName, $limit = 10)
    {
        $qb = $this->createQueryBuilder('j');
        $qb ->where('j.workerName = :workerName')
            ->andWhere('j.state = :state')
            ->orderBy('j.finishedAt', 'DESC')
            ->setMaxResults($limit);

        $qb ->setParameters(array(
            'workerName' => $workerName,
            'state' => Job::STATE_FINISHED
        ));

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $workerName
     * @return mixed
     */
    public function stopAll($workerName)
    {
        $dbal = $this->getEntityManager()->getConnection();

        $tableName = $this
            ->getEntityManager()
            ->getClassMetadata('Scandio\JobQueueBundle\Entity\Job')
            ->getTableName();

        $paused = Job::STATE_PAUSED;
        $queued = Job::STATE_QUEUE;

        $sql = <<<SQL
UPDATE $tableName SET state = $paused WHERE state = $queued AND worker_name = "$workerName";
SQL;

        return $dbal->exec($sql);
    }

    /**
     * @param $workerName
     * @return mixed
     */
    public function resumeAll($workerName)
    {
        $dbal = $this->getEntityManager()->getConnection();

        $tableName = $this
            ->getEntityManager()
            ->getClassMetadata('Scandio\JobQueueBundle\Entity\Job')
            ->getTableName();

        $paused = Job::STATE_PAUSED;
        $queued = Job::STATE_QUEUE;

        $sql = <<<SQL
UPDATE $tableName SET state = $queued WHERE state = $paused AND worker_name = "$workerName";
SQL;

        return $dbal->exec($sql);
    }

    /**
     * @param $field
     * @param $term
     * @return array
     */
    public function search($field, $term)
    {
        $qb = $this->createQueryBuilder('j');
        $qb ->where('j.'.$field.' LIKE :term');

        $qb ->setParameter('term', '%'.$term.'%');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $term
     * @param $workerName
     * @param int $status
     * @param null $output
     * @return mixed
     */
    public function searchAndFinish($term, $workerName, $status = Job::STATE_QUEUE, $output = null)
    {
        $dbal = $this->getEntityManager()->getConnection();

        $tableName = $this
            ->getEntityManager()
            ->getClassMetadata('Scandio\JobQueueBundle\Entity\Job')
            ->getTableName();

        $sql = <<<SQL
UPDATE $tableName
SET output = :output, state = :finished, started_at = :startedAt, finished_at = :finishedAt
WHERE state = :status AND command LIKE :term AND worker_name = :workerName;
SQL;

        return $dbal->executeQuery($sql, array(
            'status' => $status,
            'term' => "%$term%",
            'workerName' => $workerName,
            'finished' => Job::STATE_FINISHED,
            'startedAt' => date('Y-m-d H:i:s'),
            'finishedAt' => date('Y-m-d H:i:s'),
            'output' => !empty($output) ? $output : sprintf('Duplicate Entry for %s', $term)
        ));
    }

    /**
     * @param $workerName
     * @return mixed
     */
    public function getQueuedCommands($workerName)
    {
        $dbal = $this->getEntityManager()->getConnection();

        $tableName = $this
            ->getEntityManager()
            ->getClassMetadata('Scandio\JobQueueBundle\Entity\Job')
            ->getTableName();

        $sql = <<<SQL
SELECT command FROM $tableName WHERE state = :state AND worker_name = :workerName ORDER BY created_at ASC
SQL;

        $comamnds = $dbal->fetchAll($sql, array(
            'state' => JOB::STATE_QUEUE,
            'workerName' => $workerName,
        ));

        $extract = function(&$value) { return $value['command']; };
        return array_map($extract, $comamnds);
    }

    /**
     * @param string $workerName
     */
    public function purge($workerName = null, $state = JOB::STATE_FINISHED)
    {
        $dbal = $this->getEntityManager()->getConnection();

        $tableName = $this
            ->getEntityManager()
            ->getClassMetadata('Scandio\JobQueueBundle\Entity\Job')
            ->getTableName();

        $sql = "DELETE FROM $tableName WHERE state = :state";
        $params = array('state' => $state);

        if (!empty($workerName)) {
            $sql .= " AND worker_name = :workerName";
            $params['workerName'] = $workerName;
        }

        $queryStatement = $dbal->executeQuery($sql, $params);
        $queryStatement->execute();

        return $queryStatement->rowCount();
    }
}