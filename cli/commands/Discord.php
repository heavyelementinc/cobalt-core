<?php

use Cobalt\DBManagement\Import;
use Cobalt\Integrations\Final\Discord\DiscordConfig;
use Drivers\DatabaseManagement;
use Discord\Discord;

/**
 * The `database` command offers a CLI interface for importing and exporting the database.
 * @package cli_command
 */
class Database {
    public $help_documentation = [
        'command' => [
            'description' => "[filename] Export a database backup. Reads --export= flag (comma-delimited list)",
            'context_required' => true
        ],
        'import' => [
            'description' => "filename Import a database export"
        ]
    ];

    function cmd($command) {
        $token = new DiscordConfig();
        $discord = new Discord([
            'token' => (string)$token->bot_private_key,
        ]);

        $discord->on('ready', function ($discord) {
            
        });

        $discord->run();

    }

    function start() {
        $token = new DiscordConfig();
        $discord = new Discord([
            'token' => (string)$token->bot_private_key
        ]);

        $discord->on('ready', function ($discord) {
            echo "Bot is ready!", PHP_EOL;

            // Listen for messages
            $discord->on('message', function ($message) {
                echo "Received a message from {$message->author->username}: {$message->content}", PHP_EOL;
                
                // Respond to a specific command
                if ($message->content === '!hello') {
                    $message->channel->sendMessage('Hello, Discord!');
                }
            });
        });

        $discord->run();
    }
}