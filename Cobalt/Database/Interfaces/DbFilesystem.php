<?php

namespace Cobalt\Database\Interfaces;

use Cobalt\Database\Classes\CobaltCursor;

interface DbFilesystem {
    function getFilesCollection():DbCollection;

    public function delete(mixed $id):void;

    public function deleteByName(string $filename):void;

    public function downloadToStream(mixed $id, $destination): void;
    public function downloadToStreamByName(string $filename, $destination, array $options = []): void;
    public function drop(): void;
    public function find(array|object $filter = [], array $options = []): CobaltCursor;
    public function findOne(array|object $filter = [], array $options = []): array|object|null;
    public function getBucketName(): string;
    public function openDownloadStream(mixed $id);
    public function openDownloadStreamByName(string $filename, array $options = []);
    public function openUploadStream(string $filename, array $options = []);
    public function rename(mixed $id, string $newFilename): void;
    public function renameByName(string $filename, string $newFilename): void;
    /**
     * Writes the contents of a readable stream to a GridFS file.
     *
     * Supported options:
     *
     *  * _id (mixed): File document identifier. Defaults to a new ObjectId.
     *
     *  * chunkSizeBytes (integer): The chunk size in bytes. Defaults to the
     *    bucket's chunk size.
     *
     *  * metadata (document): User data for the "metadata" field of the files
     *    collection document.
     *
     * @param string   $filename Filename
     * @param resource $source   Readable stream
     * @param array    $options  Stream options
     * @return mixed ID of the newly created GridFS file
     * @throws InvalidArgumentException if $source is not a GridFS stream
     * @throws StreamException if the file could not be uploaded
     * @throws DriverRuntimeException for other driver errors (e.g. connection errors)
     */
    public function uploadFromStream(string $filename, $source, array $options = []): mixed;
}