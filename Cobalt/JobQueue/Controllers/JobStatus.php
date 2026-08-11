<?php

namespace Cobalt\JobQueue\Controllers;

use Cobalt\Auth\Users\Models\User;
use Cobalt\Controllers\Controller;
use Cobalt\JobQueue\Enums\JobState;
use Cobalt\Model\Traits\Accessible;
use Exception;
use Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\Unauthorized;
use Exceptions\HTTP\UnknownError;
use MongoDB\BSON\Document;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument\BSONDocument;
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
            if($id instanceof ObjectId === false) $_id = new ObjectId($id);
            else {
                $_id = (string)$id;
            }
            /** @var ObjectId $_id */
        } catch (Exception $e) {
            throw new BadRequest("Invalid Job ID");
        }

        // At this point, the job's document is available to
        // the class
        /** @var Job $jobItem */
        $jobItem = $this->findOne(['_id' => $id]);

        // Check if the current session has permission to see this model
        $permissions = $jobItem->getPermissions();
        if(!empty($permissions)) {
            if(!User::hasAnyPermission(null, $permissions, true)) {
                throw new Unauthorized("Not allowed.");
            }
        }

        // Check the state of this job
        switch($jobItem->getState()) {
            // Handle values approporiately
            case JobState::CREATED:
                throw new UnknownError("Something went wrong.");
            case JobState::QUEUED:
            case JobState::FINISHED:
                return;
        }

        // Respond with the job's details 
        return [
            'status'   => $jobItem->getState(),
            'progress' => $jobItem->getProgress(),
            'message'  => $jobItem->getMessage(),
        ];

    }

    // public function status_old(string|ObjectId $id) {
    //     try {
    //         // Convert a string to an ObjectId
    //         if(is_string($id)) {
    //             $id  = $id;
    //             $_id = new ObjectId($id);
    //         } else {
    //             $_id = $id;
    //             $id  = (string)$id;
    //         }
    //         /** @var ObjectId $_id */
    //     } catch (Exception $e) {
    //         throw new BadRequest("Invalid object ID");
    //     }
    //     // Check if the user is allowed to read the job's status
    //     if(!is_root() && !in_array($id, $_SESSION[self::SESSION_JOB_QUEUE_KEY] ?? [])) {
    //         throw new Unauthorized("You're not authorized to read the given status");
    //     }

    //     $job = new Job();
    //     $doc = $job->adopt($_id);
    //     if(!$doc) throw new NotFound("Unknown job");
        
    //     if($doc->status === Job::STATUS_FINISHED || $doc->current >= $doc->total) {
    //         $projection = [];
    //         foreach($doc->queue as $item) {
    //             $projection[$item->name] = 1;
    //         }
            
    //         $model = $job->getAdoptedModelInstance();
    //         $mutable = $model->findOne(['_id' => $model], ['projection' => $projection]);
    //         foreach($doc->queue as $item) {
    //             $model->{$item->name}->onUpdateConfirmed($mutable->{$item->name}->value);
    //         }
    //     }

    //     return [
    //         'id'      => $id,
    //         'status'  => $doc->status,
    //         'total'   => $doc->total,
    //         'current' => $doc->current,
    //         'update'  => $doc->update,
    //         'message' => $doc->message,
    //     ];
    // }

    public function event(string|ObjectId $id) {
        ignore_user_abort(true);
        session_write_close();
        header("Content-Type: text/event-stream");
        header("Cache-Control: no-cache");
        // header("Access-Control-Allow-Origin: *");
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

        $currentEvent = $this->findOne(['_id' => $_id]);
        if(!$currentEvent) throw new NotFound("Invalid EventId");
        
        if($currentEvent->status === "finished") {
            $this->onFinished($currentEvent);
        }

        // Now we know that our $id is an ObjectId
        $changeStream = $this->collection->watch();

        for ($changeStream->rewind(); true; $changeStream->next()) {
            // Let's ensure we're not running this event loop if the client has diconnected
            if(connection_aborted()) exit;

            // If the changeStream is invalid that means nothing has happened.
            if(!$changeStream->valid()) continue;

            // Grab the event from the current stream
            $event = $changeStream->current();

            if($event['operationType'] === 'invalidate') break;
            
            // Get the id of the current document as a string.
            $modifiedId = (string)$event['documentKey']['_id'];
            
            // Filter out documents we're not watching
            if($id !== (string)$modifiedId) continue;

            
            switch($event['operationType']) {
                case 'delete':
                    return;
                case 'insert':
                case 'replace':
                    break;
                case 'update':
                    $this->onUpdateEvent($event['fullDocument'], $sinceLastMessage, $heartbeatCountSinceLastEventUpdate);
                    break;
            }
            // If we've made it here, it means all other checks have failed and
            // we're likely to have not sent a message.
            $this->heartbeatCheck();
        }
    }

    
    const int MINIMUM_HEARTBEAT_INTERVAL = 10;
    const int MAX_HEARTBEATS_BEFORE_AUTO_ABORT = 12;
    private int $messageCount = 0;
    private int $lastMessageSentEpoch = 0;
    private int $heartbeatCountSinceLastEventUpdate = 0;
    private string $lastMessageType = '';

    private function heartbeatCheck() {
        if(time() - $this->lastMessageSentEpoch >= self::MINIMUM_HEARTBEAT_INTERVAL) {
            $this->sendMessage('heartbeat', []);
        }
    }

    private function onUpdateEvent(Document $document, &$sinceLastMessage, &$heartbeatCountSinceLastEventUpdate) {
        if($document->status === "finished") $this->onFinished($document);
        $this->sendMessage('update', $document->toPHP());
    }

    private function onFinished(Document $document):never {
        $this->sendMessage('finished', $document->toPHP());
        exit;
    }

    private function sendMessage(string $type, array $content) {
        $content['id'] = $this->messageCount;
        print(json_encode(['type' => $type, 'details' => $content])."\n");
        $this->messageCount += 1;
        $this->lastMessageSentEpoch = time();
        
        // Check if our last message and current messages were heartbeats
        if($type === "heartbeat" && $this->lastMessageType === "heartbeat") {
            $this->heartbeatCountSinceLastEventUpdate += 1;
            if($this->heartbeatCountSinceLastEventUpdate >= self::MAX_HEARTBEATS_BEFORE_AUTO_ABORT) {
                exit;
            }
        } else $this->heartbeatCountSinceLastEventUpdate = 0;
        
        $this->lastMessageType = $type;
        ob_flush();
        flush();
    }

    public function queueEvent(ObjectId $_id = null) {
        
    }
}