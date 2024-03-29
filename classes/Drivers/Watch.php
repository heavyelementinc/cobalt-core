<?php

namespace Drivers;

use Exception;
use \MongoDB\BSON\ObjectId;

class Watch extends Database {

    protected $watchId = null;
    protected $schema = [
        'status' => 'pending',
        'current' => 0,
        'total' => null,
        'data' => [],
        'instructions' => [],
    ];

    function __construct($watchId = null) {
        parent::__construct();
        if ($watchId) $this->set_id($watchId);
    }

    function set_id($watchId = null) {
        $this->watchId = $this->__id($watchId);
        header("X-Subscribe: " . (string)$this->watchId);
        $_SESSION['watchId'] = (string)$this->watchId;
        return (string)$this->watchId;
    }

    function validate_id() {
        if ($this->count(['_id' => $this->watchId]) === 0) return false;
        return true;
    }

    function get_id() {
        if (!$this->watchId) $this->set_id();
        return $this->watchId;
    }

    public function get() {
        return $this->findOne(['_id' => $this->watchId]);
    }

    public function get_collection_name() {
        return "CobaltWatch";
    }

    public function stream() {
        return $this->collection->watch();
    }

    public function queue($data = []) {
        $total = ['total' => 0];
        if (key_exists('data', $data)) $total['total'] = count($data['data']);
        return $this->updateOne(
            ['_id' => $this->watchId],
            ['$set' => array_merge(
                $this->schema,
                $total,
                $data,
                [
                    'created' => $this->__date(),
                    'timeout' => 30,
                    'backgroundable' => false
                ]
            )],
            ['upsert' => true]
        );
    }

    public function backgroundable(bool $background) {
        $this->set_field('backgroundable', $background);
    }

    public function timeout(int $seconds = 30) {
        $this->set_field('timeout', $seconds);
    }

    public function set($data) {
        $this->set_field('data', $data);
    }

    public function message($msg) {
        $this->set_field('message', $msg);
    }

    public function inc($num = 1) {
        $this->set_field('current', $num, '$inc');
    }

    public function dec($num = 1) {
        $this->set_field('current', $num, '$dec');
    }

    public function reset() {
        $this->set_field('current', 0);
    }

    public function total($total) {
        $this->set_field('total', $total);
    }

    protected function set_field($field, $value, $operation = '$set') {
        $result = $this->findOneAndUpdate(['_id' => $this->watchId], [$operation => [$field => $value]]);
        if ($result === null || $result->status === "aborted") throw new Exception("Task was aborted");
    }

    public function done() {
        return $this->updateOne(['_id' => $this->watchId], ['$set' => ['status' => "complete"]]);
    }

    public function clear() {
        return $this->deleteOne(['_id' => $this->watchId]);
    }

    public function abort() {
        return $this->updateOne(['_id' => $this->watchId], ['$set' => ['status' => 'aborted']]);
    }

    public function set_instructions(array $data) {
        if (!$this->watchId) $this->get_id();
        $this->updateOne(['_id' => $this->watchId], ['$set' => ['instructions' => $data]]);
        return $this->watchId;
    }
}
