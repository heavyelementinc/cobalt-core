<?php

namespace MongoDB\Driver;

use Exception;

final class Server {
    /* Constants */
    const int TYPE_UNKNOWN = 0;
    const int TYPE_STANDALONE = 1;
    const int TYPE_MONGOS = 2;
    const int TYPE_POSSIBLE_PRIMARY = 3;
    const int TYPE_RS_PRIMARY = 4;
    const int TYPE_RS_SECONDARY = 5;
    const int TYPE_RS_ARBITER = 6;
    const int TYPE_RS_OTHER = 7;
    const int TYPE_RS_GHOST = 8;
    const int TYPE_LOAD_BALANCER = 9;
    /* Methods */
    final private function  __construct() {
        throw new Exception("Not Implemented");
    }
    final public  function executeBulkWrite(string $namespace, MongoDB\Driver\BulkWrite $bulk, ?array $options = null): MongoDB\Driver\WriteResult {
        throw new Exception("Not Implemented");
    }
    final public  function executeBulkWriteCommand(MongoDB\Driver\BulkWriteCommand $bulk, ?array $options = null): MongoDB\Driver\BulkWriteCommandResult {
        throw new Exception("Not Implemented");
    }
    final public  function executeCommand(string $db, MongoDB\Driver\Command $command, ?array $options = null): MongoDB\Driver\Cursor {
        throw new Exception("Not Implemented");
    }
    final public  function executeQuery(string $namespace, MongoDB\Driver\Query $query, ?array $options = null): MongoDB\Driver\Cursor {
        throw new Exception("Not Implemented");
    }
    final public  function executeReadCommand(string $db, MongoDB\Driver\Command $command, ?array $options = null): MongoDB\Driver\Cursor {
        throw new Exception("Not Implemented");
    }
    final public  function executeReadWriteCommand(string $db, MongoDB\Driver\Command $command, ?array $options = null): MongoDB\Driver\Cursor {
        throw new Exception("Not Implemented");
    }
    final public  function executeWriteCommand(string $db, MongoDB\Driver\Command $command, ?array $options = null): MongoDB\Driver\Cursor {
        throw new Exception("Not Implemented");
    }
    final public  function getHost(): string {
        throw new Exception("Not Implemented");
    }
    final public  function getInfo(): array {
        throw new Exception("Not Implemented");
    }
    final public  function getLatency(): ?integer {
        throw new Exception("Not Implemented");
    }
    final public  function getPort(): int {
        throw new Exception("Not Implemented");
    }
    final public  function getServerDescription(): MongoDB\Driver\ServerDescription {
        throw new Exception("Not Implemented");
    }
    final public  function getTags(): array {
        throw new Exception("Not Implemented");
    }
    final public  function getType(): int {
        throw new Exception("Not Implemented");
    }
    final public  function isArbiter(): bool {
        throw new Exception("Not Implemented");
    }
    final public  function isHidden(): bool {
        throw new Exception("Not Implemented");
    }
    final public  function isPassive(): bool {
        throw new Exception("Not Implemented");
    }
    final public  function isPrimary(): bool {
        throw new Exception("Not Implemented");
    }
    final public  function isSecondary(): bool {
        throw new Exception("Not Implemented");
    }
}