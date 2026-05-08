<?php

namespace Cobalt\Model\Interfaces;

use Drivers\DatabaseManagement;
use Generator;
use MongoDB\InsertOneResult;
use MongoDB\UpdateResult;

/**
 * If a `version` number is unset, __beforeMigrationUpgradeLatest will be run
 * if a the version number is older than the current version, then a method
 * @package Cobalt\Model\Interfaces
 */
interface Migration {    
    /**
     * @return array{insertOneResult:InsertOneResult,totalDocuments:int}
     */
    public function __initializeDataset(int &$count);
    
    /**
     * Before a document can be migrated, it may need to be mutated in some way.
     * The __beforeMigrationUpgrade method gives you the ability to do this.
     * If the document just needs to be upgraded to the new class, leave this
     * function empty.
     * 
     * @param array $doc the raw data in the database
     * @param array &$mutated_doc the document that will be updated
     * @param array{$set:array,$unset:array,$inc:array,$min:array,$max:array,$mul,$rename:array,$setOnInsert:array,$currentDate:array} &$update
     * @param int $count 
     * @param DatabaseManagement $manager 
     * @return void 
     */
    public function __beforeMigrationUpgrade(array $doc, array &$mutated_doc, array &$update, int $count, DatabaseManagement $manager):void;
    
    /**
     * Handle any per-document after migration updates
     * @param UpdateResult $result 
     * @param array $mutated_doc 
     * @param array $doc 
     * @param DatabaseManagement $manager 
     * @return void 
     */
    public function __afterMigrationUpgrade(UpdateResult $result, array $mutated_doc, array $doc, DatabaseManagement $manager):void;

    /** If you want to have specific verrsion upgrades, end __beforeMigrationUpgrade */
}