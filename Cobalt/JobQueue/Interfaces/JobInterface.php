<?php

namespace Cobalt\JobQueue\Interfaces;

use Cobalt\Model\Model;
use Cobalt\JobQueue\Enums\JobState;
use Cobalt\Model\Types\ModelType;
use MongoDB\BSON\Document;
use MongoDB\BSON\ObjectId;

interface JobInterface {

    /**
     * Returns an Enum
     * @return JobState 
     */
    function getState():JobState;
    
    /**
     * Returns a string denoting the current status of the job
     * @return string 
     */
    function getMessage():string;

    /** 
     * Returns a float between 0 and 1 denoting the progress of the job.
     * @return float >=0 <=1
     */
    function getProgress():float;

    /**
     * Returns an array of permissions or an empty array for any
     * @return string[] 
     */
    function getPermissions():array;

    /**
     * Adds a BatchItem to this job's queue for later processing
     * @param BatchItem $item 
     * @return void 
     */
    function addBatchItem(BatchItem $item):void;
    function hasItems():bool;
    /**
     * Queues this job for processing and dispatches it asynchronously
     * @return null|ObjectId 
     */
    function queue():?ObjectId;

    function getJobId():?ObjectId;

    /**
     * @return BatchItem[]
     */
    function getBatchItems():array;
    
    /**
     * When the `execute()` method is called, the class should instantiate
     * the job's targetModel, look up the targetDocument
     * @return void
     */
    function execute():void;

    function updateState(JobState $state, int $batchIndex, string $message);
}