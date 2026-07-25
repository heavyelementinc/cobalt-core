<?php

namespace Cobalt\Database\Interfaces;


interface UpdateResult {
    public function getMatchedCount():int;

    public function getModifiedCount():int;

    public function getUpsertedCount():int;

    public function getUpsertedId():mixed;

    public function isAcknowledged():bool;
}