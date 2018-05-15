ScandioJobQueueBundle
=====================

A Symfony queue system.

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

    [ScandioJobQueueBundle]
        git=https://github.com/scandio/ScandioJobQueueBundle.git
        target=bundles/Scandio/JobQueueBundle

Or if you want to clone the repos:

    # Install Job Queue
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
$this->getContainer()->get('scandio.job_manager')->queue('default', 'php -r \'echo "hello\n";\'');
// choose a random worker
$this->getContainer()->get('scandio.job_manager')->queueRandom('php -r \'echo "hello\n";\'');
// add a job with higher priority
$this->getContainer()->get('scandio.job_manager')->queue('default', 'php -r \'echo "hello\n";\'', Job::PRIORITY_HIGHER);
```

### Queue Manager
You can display a lot of information via the job-queue Manager:
```
php /var/www/project/app/console scandio:job-queue:manager
```

### License

Copyright (c) Scandio <http://https://github.com/scandio/>

This software is under the MIT license.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is furnished
to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
