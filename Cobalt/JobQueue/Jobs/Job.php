<?php

namespace Cobalt\JobQueue\Jobs;

use Cobalt\Model\Model;
use Cobalt\Model\Traits\Accessible;
use Cobalt\JobQueue\Controllers\JobStatus;
use Exception;
use MongoDB\BSON\Document;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Model\BSONDocument;
use Override;

class Job {
    use Accessible;

    const STATUS_PENDING = "pending";
    const STATUS_IN_PROGRESS = "inprogress";
    const STATUS_FINISHED = "finished";
    const STATUS_ABORTED = "aborted";

    #[Override]
    public function getCollectionName($string = null): string {
        return (new JobStatus())->getCollectionName();
    }

    public ObjectId $_id;
    private Model $model;
    private ?ObjectId $refid;
    private string $type;
    private array $queue = [];

    function init(Model $model, ?ObjectId $id, string $type) {
        $this->model = $model;
        $this->set_refid($id);
        $this->type  = $type;
    }

    function set_refid(?ObjectId $id){
        $this->refid = $id;
    }

    function queue() {
        $document = [
            // '_id'     => '',     // ObjectId
            'model'   => $this->model::class, // Namespaced model
            'refid'   => $this->refid, // ObjectId for model
            'jobtype' => $this->type,  // The job type
            'total'   => $this->length(), // The total amount of work to be done
            'current' =>  0,        // The current work item to be done
            'queue'   => $this->queue,    // Arbitrary list of job items
            'status'  => self::STATUS_PENDING, // A list job status
        ];
        $result = $this->insertOne($document);
        $id = $result->getInsertedId();

        $this->handleJobAuthForSession($id);
        header("X-Job-Status: ". JobStatus::get_route_href("status", [$id]));
        $this->_id = $id;
        return $id;
    }

    public function handleJobAuthForSession(ObjectId $id) {
        // Handle authentication for this user
        $key = JobStatus::SESSION_JOB_QUEUE_KEY;
        if(!is_array($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }
        array_push($_SESSION[$key], (string)$id);
    }

    public function length() {
        return count($this->queue);
    }

    function addOneToQueue(array $queue) {
        $item = [
            'status' => self::STATUS_PENDING,
            'message' => '',
            ...$queue
        ];
        array_push($this->queue, $item);
        // $this->updateOne(['_id' => $id], ['$addToSet' => ['queue' => ['$each' => $queue]]]);
    }

    /**
     * @property object{current:int,total:int,model:Model,queue:array} $adopted
     */
    private object $adopted;
    function getAdopted():object {
        return $this->adopted;
    }
    public function adopt(ObjectId $id) {
        $result = $this->findOne(['_id' => $id]);
        if(!$result) throw new Exception("Invalid JobId");
        $this->adopted = $result;
        if(!$this->adopted) throw new Exception("Invalid job item");
        $this->updateOne(["_id" => $id], ['$set' => ['status' => self::STATUS_IN_PROGRESS]]);
        return $this->adopted;
    }

    public function getAdoptedModelInstance() {
        $model = $this->adopted->model;
        return new $model();
    }

    public function increment(int $by = 1) {
        $updateDocument = ['$inc' => ['current' => $by]];
        $this->updateOne(["_id" => $this->adopted->_id], $updateDocument);

        $this->adopted = $this->findOne(['_id' => $this->adopted->_id]);

        if($this->adopted->current >= $this->adopted->total) {
            $this->finish("Done");
        }
        
    }

    public function updateQueueItem(int $index, string $status, string $message) {
        $this->updateOne(['_id' => $this->adopted->_id], [
            '$set' => [
                "queue.$index.status" => $status,
                "queue.$index.message" => $message,
            ]
        ]);
    }

    public function finish(string $message) {
        $this->updateOne(['_id' => $this->adopted->_id],[
            '$set' => [
                'message' => $message,
                'date' => new UTCDateTime(),
                'status' => self::STATUS_FINISHED,
            ]
        ]);
    }
}