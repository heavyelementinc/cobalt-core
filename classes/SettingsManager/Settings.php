<?php

/** This should be where settings are actually stored. */

namespace SettingsManager;

class Settings implements \Iterator {
    function __construct() {
    }
    /** ==============
     *  Iterator Stuff 
     *  ==============
     */
    private $pointer = 0;
    private $index = [];
    public function set_index($index) {
        $this->index = $index;
    }

    public function current() {
        if (!isset($this->{$this->index[$this->pointer]})) kill("Setting `" . $this->index[$this->pointer] . "` is not defined");
        return $this->{$this->index[$this->pointer]};
    }

    public function key() {
        return $this->index[$this->pointer];
    }

    public function next() {
        $this->pointer++;
    }

    public function rewind() {
        $this->pointer = 0;
    }

    public function valid() {
        return isset($this->index[$this->pointer]);
    }
}
