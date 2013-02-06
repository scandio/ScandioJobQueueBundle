ScandioJobQueueBundle
=====================

A Symfony2 queue system.

With this bundle, you can send commands to a queue, which will be performed according to the crontab.

## Requirements:

- Symfony2 **>=2.1.0**

## Installation

Install via composer.json:

    ...
    "repositories": [
            {
                "type": "git",
                "url": "https://github.com/scandio/ScandioJobQueueBundle.git"
            }
        ],
    ...
     "require": {
            ...
            "scandio/job-queue-bundle": "dev-master"
            ...
        },


If you use a `deps` file, add:

    [ScandioPaginatorBundle]
        git=https://github.com/scandio/ScandioJobQueueBundle.git
        target=bundles/Scandio/JobQueueBundle

Or if you want to clone the repos:

    # Install Paginator
    git clone https://github.com/scandio/ScandioJobQueueBundle.git vendor/bundles/Scandio/JobQueueBundle

## Usage

### Crontab
Just include the Scandio\JobQueueBundle\ScandioJobQueueBundle() in your AppKernel.php and activate it with the following crontab:
```
* * * * * php /var/www/project/app/console scandio:job-queue:worker --maxJobs="250" # runs every minute
```

If you want to use more than one worker, add the following lines to the app/config/config.yml:
``` yml
scandio_job_queue:
    enable_randomization: true
    workers: ['load_balance_first', 'load_balance_second', 'load_balance_third']
```

Add all workers to the crontab:
```
* * * * * php /var/www/project/app/console scandio:job-queue:worker --maxJobs="250" # runs every minute # default is always used!
* * * * * php /var/www/project/app/console scandio:job-queue:worker --maxJobs="250" "load_balance_first"
* * * * * php /var/www/project/app/console scandio:job-queue:worker --maxJobs="250" "load_balance_second"
* * * * * php /var/www/project/app/console scandio:job-queue:worker --maxJobs="250" "load_balance_third"
```

### Add Jobs
To add Jobs to the Queue, simply call the service and add a new Job:
``` php
// use a specific worker
$this->getContainer()->get('scandio.job_manager')->queueRandom('default', 'php -r \'echo "hello\n";\'');
// choose a random worker
$this->getContainer()->get('scandio.job_manager')->queueRandom('php -r \'echo "hello\n";\'');
// choose a random worker with higher priority
$this->getContainer()->get('scandio.job_manager')->queueRandom('php -r \'echo "hello\n";\'', Job::PRIORITY_HIGHER);
```

### Queue Manager
You can display a lot of information via the job-queue Manager:
```
php /var/www/project/app/console scandio:job-queue:manager
```