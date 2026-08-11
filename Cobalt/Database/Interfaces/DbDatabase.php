<?php

namespace Cobalt\Database\Interfaces;

interface DbDatabase {
    // /**
    //  * Returns a DbCollection
    //  * @param string $databaseName 
    //  * @return DbCollection 
    //  */
    // public function __get(string $databaseName):DbCollection;
    
    public function dropDatabase(string $database, array $options = []):void;

    public function drop(array $options = []): void;
    
    public function getCollection(string $collection):DbCollection;

    public function selectFilesystem(array $selectFilesystemBucket = []):DbFilesystem;
}