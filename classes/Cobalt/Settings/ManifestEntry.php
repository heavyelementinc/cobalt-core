<?php

namespace Cobalt\Settings;

/**
 * 
 * @package Cobalt\Settings
 */
class ManifestEntry {
    
    private array $dataInProgress = [];

    function __construct() {
    }

    function addTo(string $type, string $context, array $manifestEntry) {
        $this->initializeType($type, $context);
        return $this->dataInProgress[$type][$context] = $this->appendValuesToArray($this->dataInProgress[$type][$context], $manifestEntry);
        // if(is_associative_array($manifestEntry)) return $this->dataInProgress[$type][$context] = array_merge($this->dataInProgress, $manifestEntry);
        // $this->appendValueToArray($type, $context, $manifestEntry);
    }

    function addManifest(array $manifest) {
        foreach($manifest as $type => $data) {
            foreach($data as $context => $details) {
                $this->addTo($type, $context, $details);
            }
        }
    }

    private function initializeType($type, $context) {
        if(!key_exists($type, $this->dataInProgress)) $this->dataInProgress[$type] = [];
        if(!key_exists($context, $this->dataInProgress[$type])) $this->dataInProgress[$type][$context] = [];
    }

    public function getFinalizedData():array {
        $final = [];
        foreach($this->dataInProgress as $type => $directives) {
            $final[$type] = [];
            foreach($directives as $context => $data) {
                if(in_array($context, ['common', 'append'])) continue;
                if(is_associative_array($data)) $final[$type][$context] = array_merge($this->dataInProgress[$type]['common'] ?? [], $data, $this->dataInProgress[$type]['append'] ?? []);
                
                $result = $this->dataInProgress[$type]['common'] ?? [];
                $result = $this->appendValuesToArray($result, $data);
                $result = $this->appendValuesToArray($result, $this->dataInProgress[$type]['append'] ?? []);
                // $result += $data;
                // $result += $this->dataInProgress[$type]['append'] ?? [];
                $final[$type][$context] = array_reverse(array_unique(array_reverse($result)));
            }
        }
        return $final;
    }

    private function appendValuesToArray($arr, $args) {
        $mutant = $arr;
        if(is_associative_array($args)) return array_merge($mutant, $args);
        array_push($mutant, ...$args);
        return $mutant;
        // if(!in_array($manifestEntry, $this->dataInProgress[$type][$context])) return $this->dataInProgress[$type][$context] += $manifestEntry;
        // unset($this->dataInProgress[$type][$context][array_search($manifestEntry, $this->dataInProgress[$type][$context])]);
        // $this->dataInProgress[$type][$context] += $manifestEntry;
    }
}