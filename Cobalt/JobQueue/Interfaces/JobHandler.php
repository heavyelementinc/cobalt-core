<?php

namespace Cobalt\JobQueue\Interfaces;

use Cobalt\JobQueue\Models\Job;

interface JobHandler {
    public function __job__on_start(object $item, Job $job, int $index);
    public function __job__on_complete(object $item, Job $job, int $index);
}