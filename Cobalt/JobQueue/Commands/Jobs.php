<?php

namespace Cobalt\JobQueue\Commands;

use Cobalt\Commands\Attributes\Description;
use Cobalt\Commands\Classes\CommandInterface;
use Cobalt\Commands\Classes\CommandList;
use Override;
use Cobalt\Commands\Classes\CommandItem;
use Cobalt\Commands\Exceptions\CommandError;
use Cobalt\JobQueue\Jobs\Job;
use Cobalt\Model\Interfaces\JobHandler;
use Exception;
use MongoDB\BSON\ObjectId;

class Jobs extends CommandInterface {
    #[Override]
    public function validCommands(): CommandList
    {
        $commandList = new CommandList();
        $commandList->add(new CommandItem($this, 'run', 'run'));
        return $commandList;
    }

    #[Override]
    public function handleFlags(array $flags, CommandItem $item, string $method, array $arguments): int {
        return COBALT_COMMAND_SUCCESS;
    }

    #[Description("Execute a JobId")]
    public function run(string $id) {
        $job = new Job();
        try{ 
            $_id = new ObjectId($id);
        } catch(Exception $e) {
            throw new CommandError("Invalid JobId");
        }
        // Get the job's document
        $doc = $job->adopt($_id);
        // Get the model
        $model = $job->getAdoptedModelInstance();
        // Find the document to run the operation on
        $result = $model->findOne(["_id" => $doc->refid]);

        // If there's no document, finish the task and quit.
        if(!$result) {
            $job->finish("Could not find specified entry for job");
            say("Failed to locate job ID `$doc->refid`");
            return COBALT_COMMANT_UNKNOWN_ERR;
        }
        
        foreach($doc->queue as $index => $item) {
            $implements = class_implements($result->{$item->name}, true);
            if(!$implements || !in_array("Cobalt\Model\Interfaces\JobHandler",$implements)) {
                $message = "Field $item->name not an instance of JobHandler";
                $job->updateQueueItem($index, Job::STATUS_ABORTED, $message);
                say($message, "e");
                continue;
            }
            $result->{$item->name}->__job__on_start($item, $job, $index);
            $result->{$item->name}->__job__on_complete($item, $job, $index);
        }
        return COBALT_COMMAND_SUCCESS;
    }
    
}