<?php

namespace Cobalt\Commands\Classes\Updates;

use Error;
use Exception;
use Iterator;
use JsonSerializable;
use Override;
use TypeError;

class UpdateHistory implements Iterator {
    private array $history = [];
    private int $index = 0;
    private ?int $currentTarget = null;
    private string $type = '';
    const DIR = __APP_ROOT__ . "/ignored/updates/history.%s.json";
    function __construct() {
        // $this->type = match($type) {
        //     'env' => 'env',
        //     'app' => 'app',
        //     default => throw new TypeError("\$type must be either 'app' or 'env'")
        // };
        $this->init();
    }

    function getFilename() {
        return sprintf(self::DIR, $this->type);
    }

    function init() {
        $filename = $this->getFilename();
        if(!file_exists($filename)) {
            file_put_contents($filename, '[]');
        }
        $file = json_decode(file_get_contents($filename), true);
        foreach($file['history'] as $index => $values) {
            $this->history[$index] = [
                'app' => new Update( 'app', $values['app']),
                'env' => new Update( 'env', $values['env']),
            ];
            if($values['current'] === true) {
                $this->index = $index;
                $this->currentTarget = $index;
            }
        }
        if(key_exists('current',$file['meta'] ?? [])) {
            $this->currentTarget = $file['meta']['current'] ?? null;
        }
    }
    
    public function hasUpdateHistory():bool {
        return ($this->currentTarget >= 0) ? true : false;
    }
    

    public function setCurrent(int $index) {
        if($index < 0) throw new Error("Index underflow");
        $this->currentTarget = $index;
    }

    public function write() {
        $arr = [
            'history' => $this->history,
            'meta' => [
                'current' => $this->currentTarget,
            ]
        ];
        file_put_contents($this->getFilename(), json_encode($arr));
    }

    public function update(bool $force = false){
        $current = $this->history[$this->currentTarget];
        say("Updating 'app' from hash ".$current->jsonSerialize()['currentHash']);
        $update = new Update($this->type, null);
        $update->update($force);
        $this->setCurrent(array_push($this->history, $update) - 1);
        $this->write();
        say("Updated $this->type to hash ".$update->jsonSerialize()['currentHash']);
    }

    public function rollback(int $rollback = 1) {
        if($this->currentTarget === null) throw new Exception("Current target must be set");
        if($this->currentTarget <= 0) throw new Exception("There's no update history to roll back to.");
        $previous = $this->current();
        $this->setCurrent($this->currentTarget - $rollback);
        $current = $this->current();
        $current->rollback($previous);
    }

    /**
     * 
     * @return Update
     */
    #[Override]
    public function current(): mixed
    {
        return $this->history[$this->key()];
    }

    #[Override]
    public function next(): void
    {
        $this->index += 1;
    }

    #[Override]
    public function key(): mixed
    {
        return $this->index;
    }

    #[Override]
    public function valid(): bool
    {
        return key_exists($this->key(), $this->history);
    }

    #[Override]
    public function rewind(): void
    {
        $this->index = 0;
    }

}