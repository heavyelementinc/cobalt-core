<?php

namespace Cobalt\Database\Interfaces;


interface InsertOneResult {
    // public function __construct(private $result, private mixed $insertedId);
    public function getInsertedCount():int;

    public function getInsertedId():mixed;

    public function isAcknowledged():bool;
}