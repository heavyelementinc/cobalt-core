<?php

namespace Cobalt\Database\Interfaces;


interface InsertManyResult {
    // public function __construct(private $result, private mixed $insertedId);
    public function getInsertedCount():int;

    public function getInsertedIds():array;

    public function isAcknowledged():bool;
}