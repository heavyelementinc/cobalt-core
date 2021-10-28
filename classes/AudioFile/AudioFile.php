<?php

namespace AudioFile;

class AudioFile extends \Drivers\Database {

    // how much detail we want. Larger number means less detail
    // (basically, how many bytes/frames to skip processing)
    // the lower the number means longer processing time
    private $detail_resolution = 40;
    // private $detail_resolution = 30;
    private $always_render = false;

    private $default_width = 500;
    private $default_height = 100;
    private $default_foreground = "#FF0000";
    private $default_background = "#FFFFFF";

    private $support_stereo = false;

    private $filename = null;

    public function get_collection_name() {
        return "AudioFilePreviews";
    }

    private function findValues($byte1, $byte2) {
        $byte1 = hexdec(bin2hex($byte1));
        $byte2 = hexdec(bin2hex($byte2));
        return ($byte1 + ($byte2 * 256));
    }

    function set_file($name) {
        $this->filename = $name;
    }

    function stereo_mode($bool = false) {
        $this->support_stereo = $bool;
    }

    function getSVG() {
        $result = $this->findOne(['file' => $this->filename]);
        if ($result !== null) {
            if ($this->always_render === false) return $result->svg;
        }
        $is_lame = `which lame`;
        if (preg_match("not found", $is_lame)) throw new \Exception("LAME dependency not satisfied");
        if ($this->filename === null) throw new \Exception("No file specified");
        if (!file_exists($this->filename)) throw new \Exception("File does not exist");

        // temporary file name
        $tmpname = "/tmp/" . substr(md5(time()), 0, 10);

        // copy from temp upload directory to current
        copy($this->filename, "{$tmpname}_o.mp3");

        // support for stereo waveform?
        $stereo = $this->support_stereo;

        // array of wavs that need to be processed
        $wavs_to_process = [];

        /**
         * convert mp3 to wav using lame decoder
         * First, resample the original mp3 using as mono (-m m), 16 bit (-b 16), and 8 KHz (--resample 8)
         * Secondly, convert that resampled mp3 into a wav
         * We don't necessarily need high quality audio to produce a waveform, doing this process reduces the WAV
         * to it's simplest form and makes processing significantly faster
         */
        if ($stereo) {
            // scale right channel down (a scale of 0 does not work)
            exec("lame {$tmpname}_o.mp3 --scale-r 0.1 -m m -S -f -b 16 --resample 8 {$tmpname}.mp3 && lame -S --decode {$tmpname}.mp3 {$tmpname}_l.wav");
            // same as above, left channel
            exec("lame {$tmpname}_o.mp3 --scale-l 0.1 -m m -S -f -b 16 --resample 8 {$tmpname}.mp3 && lame -S --decode {$tmpname}.mp3 {$tmpname}_r.wav");
            $wavs_to_process[] = "{$tmpname}_l.wav";
            $wavs_to_process[] = "{$tmpname}_r.wav";
        } else {
            exec("lame {$tmpname}_o.mp3 -m m -S -f -b 16 --resample 8 {$tmpname}.mp3 && lame -S --decode {$tmpname}.mp3 {$tmpname}.wav");
            $wavs_to_process[] = "{$tmpname}.wav";
        }

        // delete temporary files
        unlink("{$tmpname}_o.mp3");
        unlink("{$tmpname}.mp3");

        // Could just print to the output buffer, but saving to a variable
        // makes it easier to display the SVG and dump it to a file without
        // any messy ob_*() hassle
        $svg  = "<?xml version=\"1.0\"?>\n";
        $svg .= "<?xml-stylesheet href=\"waveform.css\" type=\"text/css\"?>\n";
        $svg .= "<!DOCTYPE svg PUBLIC \"-//W3C//DTD SVG 1.1//EN\" \"http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd\">\n";
        $svg .= "<svg class=\"svg-waveform\" version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\">\n";
        // rect for background color
        $svg .= "<rect width=\"0%\" height=\"100%\" />\n";

        $y_offset = floor(1 / sizeof($wavs_to_process) * 100);

        // process each wav individually
        for ($wav = 1; $wav <= sizeof($wavs_to_process); $wav++) {

            $svg .= "<svg y=\"" . ($y_offset * ($wav - 1)) . "%\" width=\"100%\" height=\"{$y_offset}%\">";

            $filename = $wavs_to_process[$wav - 1];

            /**
             * Below as posted by "zvoneM" on
             * http://forums.devshed.com/php-development-5/reading-16-bit-wav-file-318740.html
             * as findValues() defined above
             * Translated from Croation to English - July 11, 2011
             */
            $handle = fopen($filename, "r");
            // wav file header retrieval
            $heading[] = fread($handle, 4);
            $heading[] = bin2hex(fread($handle, 4));
            $heading[] = fread($handle, 4);
            $heading[] = fread($handle, 4);
            $heading[] = bin2hex(fread($handle, 4));
            $heading[] = bin2hex(fread($handle, 2));
            $heading[] = bin2hex(fread($handle, 2));
            $heading[] = bin2hex(fread($handle, 4));
            $heading[] = bin2hex(fread($handle, 4));
            $heading[] = bin2hex(fread($handle, 2));
            $heading[] = bin2hex(fread($handle, 2));
            $heading[] = fread($handle, 4);
            $heading[] = bin2hex(fread($handle, 4));

            // wav bitrate 
            $peek = hexdec(substr($heading[10], 0, 2));
            $byte = $peek / 8;

            // checking whether a mono or stereo wav
            $channel = hexdec(substr($heading[6], 0, 2));

            $ratio = ($channel == 2 ? 40 : 80);

            // start putting together the initial canvas
            // $data_size = (size_of_file - header_bytes_read) / skipped_bytes + 1
            $data_size = floor((filesize($filename) - 44) / ($ratio + $byte) + 1);
            $data_point = 0;

            while (!feof($handle) && $data_point < $data_size) {
                if ($data_point++ % $this->detail_resolution == 0) {
                    $bytes = array();

                    // get number of bytes depending on bitrate
                    for ($i = 0; $i < $byte; $i++)
                        $bytes[$i] = fgetc($handle);

                    switch ($byte) {
                            // get value for 8-bit wav
                        case 1:
                            $data = $this->findValues($bytes[0], $bytes[1]);
                            break;
                            // get value for 16-bit wav
                        case 2:
                            if (ord($bytes[1]) & 128)
                                $temp = 0;
                            else
                                $temp = 128;
                            $temp = chr((ord($bytes[1]) & 127) + $temp);
                            $data = floor($this->findValues($bytes[0], $temp) / 256);
                            break;
                    }

                    // skip bytes for memory optimization
                    fseek($handle, $ratio, SEEK_CUR);

                    // draw this data point
                    // data values can range between 0 and 255        
                    $x1 = $x2 = number_format($data_point / $data_size * 100, 2);
                    $y1 = number_format($data / 255 * 100, 2);
                    $y2 = 100 - $y1;
                    // don't bother plotting if it is a zero point
                    if ($y1 != $y2)
                        $svg .= "<line x1=\"{$x1}%\" y1=\"{$y1}%\" x2=\"{$x2}%\" y2=\"{$y2}%\" />";
                } else {
                    // skip this one due to lack of detail
                    fseek($handle, $ratio + $byte, SEEK_CUR);
                }
            }

            $svg .= "</svg>\n";

            // close and cleanup
            fclose($handle);

            // delete the processed wav file
            unlink($filename);
        }

        $svg .= "\n</svg>";
        if ($this->always_render === false) $this->updateOne(
            [
                'file' => $this->filename
            ],
            [
                '$set' => [
                    'svg' => $svg,
                    'date' => $this->__date()
                ]
            ],
            ['upsert' => ($result === null) ? true : false]
        );
        return $svg;
        // header("Content-Type: image/svg+xml");

        // print $svg;
    }
}
