<?php

namespace Cobalt\JobQueue\Models;

use Cobalt\Database\Traits\Accessible;
use Cobalt\DataModel\Types\DictionaryType;
use Cobalt\DataModel\Types\Generic;
use Cobalt\DataModel\Types\IdType;
use Cobalt\DataModel\Types\DocumentType;
use Cobalt\DataModel\Types\NumberType;
use Cobalt\DataModel\Types\StringType;
use Cobalt\JobQueue\Enums\JobState;
use Cobalt\JobQueue\Interfaces\BatchItem;
use Cobalt\JobQueue\Interfaces\JobInterface;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use MongoDB\BSON\Document;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Persistable;
use Override;
use stdClass;
use Throwable;

/*
  In order to successfully complete a task, we must know a few things:

   * The target model (which gives us access to the correct database)
   * The target ID (which points us to the right document)
   * Arbitrary data that should be acted upon in some way
   
  To set up a job, let's imagine we want to carry out a series of operations on
  an image in a specific field
  [
    'user.avatar' => [
        [
            'path' => '/path/to/image.jpg',
            'method' => 'thumbnail',
            'arguments' => [650, 650]
        ],
        [
            'path' => '/path/to/image.jpg',
            'method' => 'reformat',
            'arguments' => ['/path/to/image.avif', "image/avif"]
        ],
        [
            'path' => '/path/to/image.jpg',
            'method' => 'reformat',
            'arguments' => ['/path/to/image.webp', "image/webp"]
        ]
    ],
    'gallery' => [
        [
            'path' => '/path/to/file.jpg',
            'method' => 'thumbnail',
            'arguments' => [650, 650]
        ],
        [
            'path' => '/path/to/file.jpg',
            'method' => 'resize',
            'arguments' => [1920, 1080]
        ]
    ]
  ]
 */

class Job implements JobInterface, Persistable {
    use Accessible;
    /**
     * @var array<string,BatchItem[]>
     */
    public array $batchItems = [];
    public DocumentType $model;
    public JobState $state = JobState::CREATED;
    public array $permissions = [];
    public int $progress = 1;
    public string $message = "";

    public int $task = 0;

    protected ?ObjectId $jobId = null;

    #[Override]
    public function bsonSerialize(): array|stdClass|Document {
        return [
            'batchItems' => $this->getBatchItems(),
            'model' => $this->model::class,
            'state' => $this->state->value,
            'message' => $this->message,
            'task' => $this->task,
            'permissions' => $this->permissions,
        ];
    }

    #[Override]
    public function bsonUnserialize(array $data): void {
        $this->jobId       = $data['_id'];
        $this->batchItems  = $data['batchItems'];
        $this->model       = new $data['model']();
        $this->state       = JobState::from($data['state']);
        $this->message     = $data['message'];
        $this->task        = $data['task'];
        $this->permissions = $data['permissions'];
    }

    #[Override]
    public function getCollectionName($string = null): string {
        return "CobaltJobQueue";
    }
    
    #[Override]
    public function addBatchItem(BatchItem $item): void {
        $this->batchItems[$item->field][] = $item;
    }

    #[Override]
    public function hasItems(): bool {
        return !empty($this->batchItems);
    }

    /**
     * 
     * @return BatchItem[] 
     */
    #[Override]
    public function getBatchItems(): array {
        return array_merge(...array_values($this->batchItems));
    }

    #[Override]
    public function queue(): ?ObjectId {
        if(!$this->hasItems()) return null;
        $this->state = JobState::QUEUED;
        $result = $this->insertOne($this);
        $id = $result->getInsertedId();
        $this->jobId = $id;
        async_run_command("jobs run $id");
        return $id;
    }

    #[Override]
    public function getJobId(): ?ObjectId {
        return $this->jobId;
    }



    #[Override]
    public function getState(): JobState {
        return $this->state;
    }

    #[Override]
    public function getMessage(): string {
        return $this->message;
    }

    #[Override]
    public function getProgress(): float {
        return $this->progress;
    }

    #[Override]
    public function getPermissions(): array {
        return $this->permissions;
    }

    #[Override]
    public function execute(): void {
        $start = microtime(true);
        // Update our job and let it know we're processing.
        $this->updateState(JobState::PROCESSING, 0, "Starting...");
        // Loop through our batch items and handle each one.

        foreach($this->batchItems as $index => $item) {
            $this->executeBatchItem($item, $index);
        }
        $end = microtime(true);
        $this->updateState(JobState::FINISHED, $index ?? 0, sprintf("Finished in %d seconds.", $end - $start));
    }

    private function executeBatchItem(BatchItem $item, int $index) {
        $field = $this->model->__lookup($item->field);
        if($field instanceof Generic == false) {
            $this->updateState(JobState::FAILED, $index, "Field $item->field was not a Generic");
            return;
        }
        if(method_exists($field, $item->method) == false) {
            $this->updateState(JobState::FAILED, $index, "Field $item->field does not have $item->method");
            return;
        }
        
        try {
            $message = $field->{$item->method}(...$item->arguments);
        } catch (Throwable $e) {
            $this->updateState(JobState::FAILED, $index, $e->getMessage());
            return;
        }

        $this->updateState(JobState::PROCESSING, $index, $message);
    }

    #[Override]
    public function updateState(JobState $state, int $batchIndex, string $message){
        $this->updateOne([
            '_id' => $this->getJobId()
        ], [
            '$set' => [
                'state'   => $state->value,
                'task'    => $batchIndex,
                'message' => $message,
            ]
        ]);
    }
}