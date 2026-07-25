<?php
namespace Cobalt\Database\Drivers\MongoDb;

use Cobalt\Database\Drivers\MongoDb\Database;
use Cobalt\Database\Interfaces\DbClient;
use Cobalt\Database\Interfaces\DbDatabase;
use MongoDB\Client as MongoDBClient;
use Override;

class Client implements DbClient {
    public readonly MongoDBClient $client;

    function __construct() {
        $this->configureDatabase();
    }
    
    #[Override]
    public function getDatabase(string $databaseName, array $options = []): DbDatabase {
        $db = $this->client->getDatabase($databaseName, $options);
        $driver = new Database($this, $db);
        return $driver;
    }

    // #[Override]
    // public function __get(string $string): DbDatabase {
    //     return $this->getDatabase($string);
    // }

    #[Override]
    public function configureDatabase(): void {
        global $CONFIG;
        $auth = [];
        if($CONFIG['db_usr']) $auth['username'] = $CONFIG['db_usr'];
        if($CONFIG['db_pwd']) $auth['password'] = $CONFIG['db_pwd'];
        if($CONFIG['db_ssl']) $auth['ssl'] = $CONFIG['db_ssl'];
        if($CONFIG['db_sslFile']) $auth['sslCAFile'] = $CONFIG['db_sslFile'];
        if($CONFIG['db_invalidCerts']) $auth['sslAllowInvalidCertificates'] = $CONFIG['db_invalidCerts'];

        $this->client = new MongoDBClient("mongodb://{$CONFIG['db_addr']}:{$CONFIG['db_port']}", 
            $auth,
            [
            'typeMap' => [
                'root' => 'array',
                'array' => 'array',
                'document' => 'array',
            ]
        ]);
    }

    #[Override]
    public function __toString(): string {
        return (string)$this->client;
    }
}