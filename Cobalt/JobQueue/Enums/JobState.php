<?php
namespace Cobalt\JobQueue\Enums;

enum JobState:int {
    case FAILED = -1;
    case CREATED = 0;
    case QUEUED  = 1;
    case PROCESSING = 2;
    case FINISHED = 3;
}