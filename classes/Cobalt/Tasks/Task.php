<?php
namespace Cobalt\Tasks;

use DateTime;
use Iterator;
use JsonSerializable;
use MongoDB\BSON\Document;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Persistable;
use MongoDB\BSON\UTCDateTime;
use ReflectionObject;
use stdClass;
use TypeError;

class Task implements Persistable, JsonSerializable, Iterator {
    const TASK_FINISHED = 0;
    const ERROR_METHOD_DOES_NOT_EXIST = 2;
    const GENERAL_TASK_ERROR = 1;

    const TASK_SKIP = 999;
    const TASK_CONTINUE = self::TASK_SKIP;

    public ?ObjectId $_id = null;
    private ?string $className = null;
    private ?string $method = null;
    private ?array $args = [];
    private ?array $additional_data = [];
    private ?ObjectId $for = null;
    private ?DateTime $date = null;
    private ?DateTime $completed = null;
    private int $failureCount = 0;

    private array $keys = ['className', 'method', 'args', 'date', 'additional_data', 'for', 'failureCount'];
    private int $index = 0;
    public function current(): mixed {
        return $this->{$this->key()};
    }

    public function next(): void {
        $this->index += 1;
    }

    public function key(): mixed {
        return $this->keys[$this->index];
    }

    public function valid(): bool {
        if($this->index < 0) return false;
        if($this->index >= count($this->keys)) return false;
        return true;
    }

    public function rewind(): void {
        $this->index = 0;
    }

    public function jsonSerialize(): mixed {
        return $this->bsonSerialize();
    }

    public function bsonSerialize(): array|stdClass|Document {
        return [
            // '_id' => $this->_id,
            'className' => $this->className,
            'method' => $this->method,
            'args' => $this->args,
            'date' => new UTCDateTime($this->date),
            'additional_data' => $this->additional_data,
            'for' => $this->for,
            'failureCount' => $this->failureCount,
            // 'completed' => new UTCDateTime($this->completed),
        ];
    }

    public function bsonUnserialize(array $data): void {
        $this->_id = $data['_id'];
        $this->className = $data['className'];
        $this->method = $data['method'];
        $this->args = $data['args']->getArrayCopy();
        $this->additional_data = $data['additional_data']->getArrayCopy();
        $this->for = $data['for'];
        $this->failureCount = $data['failureCount'];

        if($data['date'] instanceof UTCDateTime) {
            $this->date = $data['date']->toDateTime();
        } else {
            $this->date = $data['date'];
        }
        if($data['completed'] instanceof UTCDateTime) {
            $this->completed = $data['completed']->toDateTime();
        } else {
            $this->completed = $data['completed'];
        }
    }

    /**
     * This checks to ensure that each element is set!
     * @return bool 
     */
    public function sanity_check(): bool {
        if(!$this->className) return false;
        if(!$this->method) return false;
        if(!$this->date) return false;
        $ref = new ReflectionObject(new $this->className());
        $meth = $ref->getMethod($this->method);
        if($meth === null) throw new TypeError("Method doesn't exist!");
        $type = $meth->getReturnType();
        if($type === null) throw new TypeError("$this->method does not specify a return type! Must be of type (int)");
        if($type->getName() !== "int") throw new TypeError("Return type of $this->method must be of type (int)!");
        return true;
    }

    public function set_class($class) {
        if(!is_object($class)) throw new TypeError("Cannot process type: " . gettype($class));
        $this->className = $class::class;
    }

    public function set_method(string $method) {
        $this->method = $method;
    }

    public function set_args(array $args) {
        $this->args = $args;
    }

    public function set_for(ObjectId $for) {
        $this->for = $for;
    }

    public function set_additional_data(array $data) {
        $this->additional_data = $data;
    }

    public function set_date(DateTime|int $date) {
        if($date instanceof DateTime === false) {
            $timestamp = $date;
            $date = new DateTime();
            $date->setTimestamp($timestamp);
        }
        $this->date = $date;
    }

    /**
     * Set a timer *n* seconds from now
     * @param int $time the number of seconds to wait before the event is executed
     * @return void 
     */
    public function set_timer(int $time):void {
        $date = new DateTime();
        $date->setTimestamp(time() + $time);
        $this->set_date($date);
    }

    public function get_class() {
        return $this->className;
    }

    public function get_method() {
        return $this->method;
    }

    public function get_args() {
        return $this->args;
    }

    public function get_for() {
        return $this->for;
    }

    public function get_additional_data() {
        return $this->additional_data;
    }

    public function get_date() {
        return $this->date;
    }
    
    public function get_completed() {
        return $this->completed;
    }

    public function get_failureCount() {
        return $this->failureCount;
    }

}