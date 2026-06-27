<?php

namespace Cobalt\Model\Interfaces;

use Cobalt\JobQueue\Jobs\Job;

interface JobHandler {
    public function __job__on_start(object $item, Job $job, int $index);
    public function __job__on_complete(object $item, Job $job, int $index);
}