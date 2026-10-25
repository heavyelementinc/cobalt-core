<?php
namespace Cobalt\Auth\Cron;

use Auth\SessionManager;
use Cobalt\Cron\CronManager;
use Cobalt\Cron\Tasks\GenericCronTask;
use MongoDB\BSON\UTCDateTime;
use Override;
use Throwable;

class SessionExpire extends GenericCronTask {
    public int $deleted = 0;
    public ?array $taskResult = [];

    public function crontask_details():array {
        return [
            'name' => 'SessionExpire',
            'frequency' => 60 * 60 * 2 // Every two hours
        ];
    }

    #[Override]
    public function crontask_status_lookup(int $status): string {
        return match($status) {
            0 => "Cleaned",
            default => "An unknown error occurred"
        };
    }

    #[Override]
    public function crontask_setup(?array $taskResult): void {
        $this->taskResult = $taskResult;
    }

    #[Override]
    public function crontask_execute(CronManager $manager): int {
        $sessions = new SessionManager();
        
        // try {
        $result = $sessions->deleteMany([
            'expires' => ['$lte' => new UTCDateTime()]
        ]);
        $this->deleted = $result->getDeletedCount();
        // } catch(Throwable $e) {
            
        // }
        return 0;
    }

    #[Override]
    public function crontask_post(int &$status): void {
        
    }

}