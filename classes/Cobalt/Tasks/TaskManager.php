<?php
namespace Cobalt\Tasks;

use Drivers\Database;
use Error;
use Exception;
use GuzzleHttp\Exception\ClientException;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class TaskManager extends Database {
    const SANITY_CHECK_FAILURE_INC = 999;
    const MARK_JOBS_COMPLETE = true;
    public function get_collection_name() {
        return "task_queue";
    }

    public function task(?ObjectId $for = null):Task {
        // If we haven't been handed a $for, then we should return a new task
        if($for === null) return new Task();
        // If we do have a $for, we should make sure that one doesn't exsit in the DB.
        $doc = $this->findOne(['for' => $for]);
        // If we have one, return it.
        if($doc !== null) return $doc;
        // Return a new task
        return new Task();
    }

    public function queue_item(Task $task) {
        if(!$task->sanity_check()) throw new Exception("This task does not pass all checks!");
        $result = $this->insertOne($task);
        return $result->getInsertedCount();
    }

    public function update_item(Task $task) {
        $for = $task->get_for();
        if(!$for) throw new Exception("Update tasks must have \$for specified!");
        $exists = $this->findOne(['for' => $for]);
        if($exists === null) return $this->queue_item($task);

        $result = $this->updateOne(['for' => $for], ['$set' => $task]);
        return $result->getModifiedCount();
    }
    
    public function process_queue($since = null) {
        $start = microtime(true);
        $query = $this->get_query($since);
        $count = $this->count($query);
        if($count === 0) return cobalt_log("TaskManager", "No tasks to execute", COBALT_LOG_NOTICE);
        cobalt_log("TaskManager", "Enumerated $count tasks to be processed", COBALT_LOG_NOTICE);
        $results = $this->find($query);
        $count = 0;
        foreach($results as $task) {
            try {
                $job_start = microtime(true);
                $job_status = $this->execute($task);
                if($job_status === null || $job_status === Task::TASK_FINISHED) {
                    if(self::MARK_JOBS_COMPLETE) $this->mark_as_complete($task);
                }
                $job_end = microtime(true);
                // cobalt_log("TaskManager", "Job \"".$task->get_class()."::".$task->get_method()."\" completed in " ($job_end - $job_start)." seconds");
                if($job_status === Task::TASK_SKIP) {
                    continue;
                }
            } catch(Exception $e) {
                cobalt_log("TaskManager", "Failed to process ".$task->get_class()."::".$task->get_method() . " " . $e->getMessage(), COBALT_LOG_EXCEPTION);
                $this->failure($task);
            } catch (Error $e) {
                cobalt_log("TaskManager", "Failed to process ".$task->get_class()."::".$task->get_method() . " " . $e->getMessage(), COBALT_LOG_ERROR);
                $this->failure($task);
            }
            $count += 1;
        }
        if($count >= 1) cobalt_log("TaskManager", "Ran $count tasks in ".(time() - $start)." seconds", COBALT_LOG_NOTICE);
    }

    public function execute(Task $task) {
        $sanity = $task->sanity_check();
        if(!$sanity) {
            // If we fail a sanity check here, then we know something is really wrong and we should mark 
            // this task as failed by adding 999 to the 'failureCount' field
            $this->failure($task, self::SANITY_CHECK_FAILURE_INC);
            return Task::TASK_SKIP;
        }
        $class = $task->get_class();
        $method = $task->get_method();
        $args = $task->get_args();
        $instance = new $class;
        if(!method_exists($instance, $method)) return $task::ERROR_METHOD_DOES_NOT_EXIST;
        try {
            $result = $instance->{$method}($task, ...$args);
        } catch (ClientException $error ) {
            switch($error->getCode()) {
                case 404:
                default:
                    return $task::TASK_FINISHED;
            }
        } catch (Exception $error) {
            return $task::GENERAL_TASK_ERROR;
        }
        return $result;
    }

    public function mark_as_complete(Task $task) {
        // $this->updateOne(['_id' => $task->_id], ['$set' => ['completed' => new UTCDateTime]]);
        $this->deleteOne(['_id' => $task->_id]);
    }

    public function update_task(array $changes, ?ObjectId $id = null, ?ObjectId $for = null) {
        if(empty($changes)) throw new Exception('$changes must not be empty!');
        if($id === null && $for === null) throw new Exception("At least one identifier must be set!");
        
        $query = [];
        if($id) $query = ['_id' => $id];
        else $query = ['for' => $for];
        
        $changes['completed'] = null;
        
        return $this->updateOne($query, ['$set' => $changes])->getModifiedCount();
    }

    public function get_query($since = null) {
        if($since === null) $since = new UTCDateTime();
        return [
            'date' => [
                '$lte' => $since
            ],
            'failureCount' => ['$lte' => 3],
        ];
    }

    public function failure($task, $inc = 1) {
        $this->updateOne(['_id' => $task->_id], ['$inc' => ['failureCount' => $inc]]);
    }
}