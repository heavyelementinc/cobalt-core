<?php

namespace ServerSentEvents;

class DemoStream extends Stream {

    private $rand = 0;
    private $events = 0;
    private $msgIndex = 0;
    private $messages = [
        "Here's a secret message from uncharted space",
        "Buzz Lightyear to the rescue!",
        "Things are only impossible until they're not.",
        "Live long and prosper 🖖",
        "If we're throwing out the rule of law then I'm not a captain, you're not a commander, and she is not the president",
        "It is possible to commit no errors and still lose. That is not a weakness. That is life.",
        "You can use logic to justify almost anything. That's its power. And its flaw.",
    ];

    function __construct() {
        $this->rand = rand(1, 5);
        parent::__construct();
    }

    public function getInterval(): int {
        return 1;
    }

    public function execute() {
        if (!$this->rand) {
            $this->rand = rand(1, 2);
            $this->events++;
            return ['type' => "quote", 'data' => ['events' => $this->events, 'message' => $this->getMessage()]];
        }
        $this->rand--;
        return null;
    }

    private function getMessage() {
        $index = rand(0, count($this->messages) - 1);
        if ($index === $this->msgIndex) return $this->getMessage();
        $this->msgIndex = $index;
        return $this->messages[$this->msgIndex];
    }
}
