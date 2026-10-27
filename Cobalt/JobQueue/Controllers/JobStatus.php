<?php

namespace Cobalt\JobQueue\Controllers;

use Cobalt\Controllers\Controller;
use Cobalt\JobQueue\Jobs\Job;
use Cobalt\Model\Traits\Accessible;
use Exception;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\Unauthorized;
use MongoDB\BSON\Document;
use MongoDB\BSON\ObjectId;
use Override;

class JobStatus extends Controller {
    use Accessible;
    const string SESSION_JOB_QUEUE_KEY = "__user_queued_jobs";
    #[Override]
    public function getCollectionName($string = null): string {
        return "CobaltJobQueue";
    }

    public function status(string|ObjectId $id) {
        try {
            // Convert a string to an ObjectId
            if(is_string($id)) {
                $id  = $id;
                $_id = new ObjectId($id);
            } else {
                $_id = $id;
                $id  = (string)$id;
            }
            /** @var ObjectId $_id */
        } catch (Exception $e) {
            throw new BadRequest("Invalid object ID");
        }
        // Check if the user is allowed to read the job's status
        if(!is_root() && !in_array($id, $_SESSION[self::SESSION_JOB_QUEUE_KEY] ?? [])) {
            throw new Unauthorized("You're not authorized to read the given status");
        }

        $job = new Job();
        $doc = $job->adopt($_id);
        if(!$doc) throw new NotFound("Job not found");
        
        if($doc->status === Job::STATUS_FINISHED || $doc->current >= $doc->total) {
            $projection = [];
            foreach($doc->queue as $item) {
                $projection[$item->name] = 1;
            }
            
            $model = $job->getAdoptedModelInstance();
            $mutable = $model->findOne(['_id' => $model->_id], ['projection' => $projection]);
            foreach($doc->queue as $item) {
                $model->{$item->name}->onUpdateConfirmed($mutable->{$item->name}->value);
            }
        } else if ($doc->status !== Job::STATUS_ABORTED) {
            Job::sendJobStatusHeader($_id);
        }

        return [
            'id'      => $id,
            'status'  => $doc->status,
            'total'   => $doc->total,
            'current' => $doc->current,
            'update'  => $doc->update,
            'message' => $doc->message,
        ];
    }

}