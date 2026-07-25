<?php

namespace Cobalt\Database\Drivers\MongoDb;

use Cobalt\Database\Interfaces\DbCollection;
use Cobalt\Database\Classes\CobaltCursor;
use Cobalt\Database\Interfaces\DbClient;
use Cobalt\Database\Interfaces\DbDatabase;
use Cobalt\Database\Interfaces\DbFilesystem;
use MongoDB\GridFS\Bucket;
use Override;

class Filesystem implements DbFilesystem {
    function __construct(
        readonly DbDatabase $database,
        readonly Bucket $gridFs
    ) {
        
    }
    #[Override]
    public function getFilesCollection(): DbCollection {
        $bucket = $this->gridFs->getFilesCollection();
        return new Collection($this->database->client, $this->database, $bucket);
    }

    #[Override]
    public function delete(mixed $id): void {
        $this->gridFs->delete($id);
    }

    #[Override]
    public function deleteByName(string $filename): void {
        $this->gridFs->deleteByName($filename);
    }

    #[Override]
    public function downloadToStream(mixed $id, $destination): void {
        $this->gridFs->downloadToStream($id, $destination);
    }

    #[Override]
    public function downloadToStreamByName(string $filename, $destination, array $options = []): void {
        $this->gridFs->downloadToStreamByName($filename, $destination, $options);
    }

    #[Override]
    public function drop(): void {
        $this->gridFs->drop();
    }

    #[Override]
    public function find(array|object $filter = [], array $options = []): CobaltCursor {
        $result = $this->gridFs->find($filter, $options);
        return new CobaltCursor($result, ['filter' => $filter, 'options' => $options], $this->getFilesCollection());
    }

    #[Override]
    public function findOne(array|object $filter = [], array $options = []): array|object|null
    {
        return $this->gridFs->findOne($filter, $options);
    }

    #[Override]
    public function getBucketName(): string {
        return $this->gridFs->getBucketName();
    }

    #[Override]
    public function openDownloadStream(mixed $id){
        return $this->gridFs->openDownloadStream($id);
    }

    #[Override]
    public function openDownloadStreamByName(string $filename, array $options = []){
        return $this->gridFs->openDownloadStreamByName($filename, $options);
    }

    #[Override]
    public function openUploadStream(string $filename, array $options = []){
        return $this->gridFs->openUploadStream($filename, $options);
    }

    #[Override]
    public function rename(mixed $id, string $newFilename): void {
        $this->gridFs->rename($id, $newFilename);
    }

    #[Override]
    public function renameByName(string $filename, string $newFilename): void {
        return $this->gridFs->renameByName($filename, $newFilename);
    }

    #[Override]
    public function uploadFromStream(string $filename, $source, array $options = []): mixed {
        return $this->gridFs->uploadFromStream($filename, $source, $options);
    }

}