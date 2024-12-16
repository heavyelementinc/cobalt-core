<?php

namespace Cobalt\UTMTracking;

use JsonSerializable;

// https://heavyelement.io/?utm_source=source&utm_medium=social&utm_campaign=name&utm_term=term&utm_content=content

class UTMDetails implements JsonSerializable {
    private string $utm_source; // REQUIRED: Identifies the platform or website referrer
    private string $utm_medium; // REQUIRED: Identifies the traffic type
    private string $utm_campaign; // REQUIRED: The name your team designates for a specific marketing campaign
    private ?string $utm_term; // Identifies the keywords used for paid ads
    private ?string $utm_content; // Used to differentiate links on the same ad or page

    function __construct(array $details) {
        $this->utm_source = $details['utm_source'];
        $this->utm_medium = $details['utm_medium'];
        $this->utm_campaign = $details['utm_campaign'];
        $this->utm_term = $details['utm_term'] ?? null;
        $this->utm_content = $details['utm_content'] ?? null;
    }
    
    public function source():string {
        return $this->utm_source;
    }
    
    public function medium():string {
        return $this->utm_medium;
    }
    
    public function campaign():string {
        return $this->utm_campaign;
    }
    
    public function term():?string {
        return  $this->utm_term;
    }
    
    public function content():?string {
        return  $this->utm_content;
    }

    public function __toString() {
        return json_encode($this);
    }

    public function jsonSerialize(): mixed {
        return [
            'utm_source' => $this->utm_source,
            'utm_medium' => $this->utm_medium,
            'utm_campaign' => $this->utm_campaign,
            'utm_term' => $this->utm_term,
            'utm_content' => $this->utm_content,
        ];
    }

    
}