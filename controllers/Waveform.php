<?php

use AudioFile\AudioFile;
use Exceptions\HTTP\NotFound;

class Waveform {
    function get() {
        $sample = $_GET['file'];
        if (!isset($sample)) throw new NotFound("No file specified");
        $file = new AudioFile();
        $file->set_file(__APP_ROOT__ . "/public/$sample");
        $svg = $file->getSVG();
        return $svg;
    }
}
