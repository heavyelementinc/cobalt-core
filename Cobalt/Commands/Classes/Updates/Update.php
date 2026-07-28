<?php

namespace Cobalt\Commands\Classes\Updates;

use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitRepository;
use Error;
use Exception;
use JsonSerializable;
use Override;
use TypeError;

class Update implements JsonSerializable {
    private string $type = "app";
    private string $currentHash = "";
    private string $currentBranch = "";
    private string $lastVer = "";
    private string $lastHash = "";
    private string $lastBranch = "";

    const LOCK_FILE = __APP_ROOT__ . "/ignored/updates/LOCKFILE";

    const STATUS_UPDATED = 0;
    const STATUS_NOT_MODIFIED = 1;

    function __construct(string $type, ?array $details){
        $this->type = match($type) {
            "app" => "app",
            "env" => "env",
            default => throw new TypeError("Must be either 'app' or 'env'")
        };
        if($details) {
            $this->currentHash = $details['currentHash'];
            $this->currentBranch = $details['currentBranch'];
            $this->lastVer = $details['lastVer'];
            $this->lastHash = $details['lastHash'];
            $this->lastBranch = $details['lastBranch'];
        }
    }

    public function rollback(Update $currentUpdate) {
        if(!$this->lastVer) throw new Exception("Must have an instantiated Update object");
        $fromHash = $currentUpdate->jsonSerialize()['currentHash'];
        if($fromHash === $this->lastHash) {
            say("Hashes are identical. $this->type not modified.",'i');
            return self::STATUS_NOT_MODIFIED;
        }
        $git = new Git();
        $repo = $git->open($this->getWorkingDir());
        say("Reverting $this->type from ID $fromHash to $this->lastBranch w/ ID $this->lastHash");
        $result = $repo->execute('checkout', '-b', $this->lastBranch, $this->lastHash);
        return self::STATUS_UPDATED;
    }

    private function getLatestHash(?GitRepository $repo = null) {
        if(!$repo) {
            $git = new Git();
            $repo = $git->open($this->getWorkingDir());
        }
        $hashes = $repo->execute('ls-remote','--sort=committerdate');
        for($i = count($hashes); $i >= 0; $i--) {
            $pattern = "/".$repo->getCurrentBranchName()."$/";
            if(!preg_match($pattern,$hashes[$i])) continue;
            return explode("	",$hashes[$i])[0];
        }
        throw new Error("Failed to locate hash on remote");
    }

    public function update(bool $force = false) {
        if($force) $this->deleteLockFile();
        $this->setLockFile();
        $git = new Git();
        $repo = $git->open($this->getWorkingDir());
        $hash = $this->getLatestHash($repo);
        $this->lastHash = $repo->getLastCommitId();
        if($hash == $this->lastHash) {
            say("Nothing to do. Hashes match.");
            return self::STATUS_NOT_MODIFIED;
        }
        $this->lastBranch = $repo->getCurrentBranchName();
        $this->lastVer = $this->getVersion();
        $repo->pull();
        $this->currentBranch = $repo->getCurrentBranchName();
        $this->currentHash = $repo->getLastCommitId();
        $this->deleteLockFile();
        return self::STATUS_UPDATED;
    }

    private function hasLockFile() {
        return file_exists(self::LOCK_FILE);
    }

    private function setLockFile() {
        if($this->hasLockFile()) throw new Exception("Cannot establish lock");
        file_put_contents(self::LOCK_FILE, $this->type);
    }

    private function deleteLockFile() {
        unlink(self::LOCK_FILE);
    }

    private function getWorkingDir() {
        return match($this->type) {
            'env' => __ENV_ROOT__,
            default => __APP_ROOT__
        };
    }

    private function getVersion() {
        return match($this->type) {
            'env' => __COBALT_VERSION,
            default => __APP_SETTINGS__['version']
        };
    }

    #[Override]
    public function jsonSerialize(): mixed {
        return [
            'lastVer' => $this->lastVer,
            'lastHash' => $this->lastHash,
            'lastBranch' => $this->lastBranch,
            'currentHash' => $this->currentHash,
            'currentBranch' => $this->currentBranch,
        ];
    }

}