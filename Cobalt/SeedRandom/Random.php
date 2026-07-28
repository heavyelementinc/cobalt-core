<?php
namespace Cobalt\SeedRandom;

class Random {
    private int $version = 1;

    const V1_MODULO = 2796203;
    const V1_MULTIPLY = 125;

    // random seed
    private int $random_seed = 0;
    const MAX_VALUE = 9_999_999;

    // set seed
    public function set_seed($s = 0):int {
        $this->random_seed = abs(intval($s)) % self::MAX_VALUE + 1;
        return $this->num();
    }

    public function discard(int $discard) {
        $this->ensure_randomness();
        $this->random_seed += $discard;
    }

    public function get_seed():int {
        return $this->random_seed;
    }

    public function set_version(int $version):void {
        $this->version = $version;
    }

    public function num($min = 0, $max = self::MAX_VALUE):int {
        $this->ensure_randomness();
        $modulo = self::V1_MODULO;
        $multiply = self::V1_MULTIPLY;
        switch($this->version) {
            case 1:
            default:
                $modulo = self::V1_MODULO;
                $multiply = self::V1_MULTIPLY;
        };
        
        $this->random_seed = ($this->random_seed * $multiply) % $modulo;
        return $this->random_seed % ($max - $min + 1) + $min;
    }

    public function shuffle(array &$items):void {
        $this->ensure_randomness();
        @mt_srand($this->random_seed);
        for ($i = count($items) - 1; $i > 0; $i--)
        {
            $j = @mt_rand(0, $i);
            $tmp = $items[$i];
            $items[$i] = $items[$j];
            $items[$j] = $tmp;
        }
    }

    private function ensure_randomness():void {
        if ($this->random_seed == 0) $this->set_seed(mt_rand());
    }
}