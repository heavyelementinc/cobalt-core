<?php

/**
 * @todo Do not display help items that require environment context if in pre-env
 */
class Upgrade{

    public $help_documentation = [
        'all' => [
            'description' => "Upgrades both Cobalt Engine and your application.",
            'context_required' => true,
        ],
        'app' => [
            'description' => "Upgrades only your application",
            'context_required' => true,
        ],
        'core' => [
            'description' => "Upgrades only Cobalt Engine",
            'context_required' => false
        ]
    ];
    
    

}