<?php

class EventStream {
    function start_streaming() {
        $stream = new \ServerSentEvents\DemoStream();
        $stream->start();
    }

    function watch_old() {
        $watchId = $_SESSION['watchId'];

        $stream = new \ServerSentEvents\Stream();
        $watch = new \Drivers\Watch($watchId);

        // Let's watch the changes in the database
        if (!$watch->validate_id()) throw new \Exceptions\HTTP\BadRequest("Malformed watch ID");
        $changeStream = $watch->stream();

        // Let's check if we need to listen on the loop
        $status = $watch->findOne(['_id' => $watchId]);
        if ($status['status'] === "complete") return $stream->finish();

        for ($changeStream->rewind(); true; $changeStream->next()) {
            // Let's exit the loop if the connection is closed
            if (connection_aborted()) return;

            if (!$changeStream->valid()) {
                continue;
            }

            $event = $changeStream->current();

            if ($event['operationType'] === 'invalidate') {
                break;
            }

            switch ($event['operationType']) {
                case 'delete':
                    $stream->dispatchEvent("deleted", ["$watchId was deleted"]);
                    break 2;
                case 'insert':
                case 'replace':
                case 'update':
                    // Check if the updated record is the one we're looking for
                    if ($watchId !== (string)$event['_id']) break;
                    // Dispatch an event if it is
                    $progress = floor($event['fullDocument']->current / $event['fullDocument']->total * 100);
                    $stream->dispatchEvent($event['operationType'], ['progress' => $progress]);
                    break;
            }
        }

        // Close the stream
        $stream->finish();
        exit;
    }

    function watch() {
        $watchId = $_SESSION['watchId'];
        $stream = new \ServerSentEvents\Stream();
        $watch = new \Drivers\Watch($watchId);

        $iterations = 0;
        $lastProgress = 0;

        // Let's watch the changes in the database
        if (!$watch->validate_id()) throw new \Exceptions\HTTP\BadRequest("Malformed watch ID");

        while (true) {
            if (connection_aborted()) {
                $watch->abort();
                exit;
            }
            // Let's check if we need to listen on the loop
            $doc = $watch->findOne(['_id' => $watch->__id($watchId)]);
            if (!$doc || $doc->status === "aborted") {
                $stream->error();
                break;
            }
            if ($doc->status === "complete") break;
            if ($doc->status === "pending") {
                $progress = floor($doc->current / $doc->total * 100);
                $stream->updateProgressBar($progress, $doc->message ?? "Post-processing");
                if ($lastProgress !== $progress) {
                    $iterations = 0;
                    $lastProgress = $progress;
                } else {
                    $iterations++;
                    if ($iterations >= 5000) {
                        $watch->abort();
                        $stream->error();
                    }
                }
                // if ($progress === 100) break;
            }

            sleep(.5);
        }
        $stream->finish();
        sleep(1);
        return;
    }
}

/**
 * 
 * 1st api call  async -> updates the database every time it generates a thumbnail
 * 
 * 2nd api call -> watches changes in database for changes to $watchId doc
 * 
 */
