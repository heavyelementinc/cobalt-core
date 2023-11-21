<?php

namespace Auth;

use Cobalt\PersistanceMap;
use Cobalt\SchemaPrototypes\ArrayResult;
use Cobalt\SchemaPrototypes\BooleanResult;
use Cobalt\SchemaPrototypes\DateResult;
use Cobalt\SchemaPrototypes\EmailAddressResult;
use Cobalt\SchemaPrototypes\StringResult;
use Cobalt\SchemaPrototypes\UploadResult;
use Cobalt\SchemaPrototypes\UniqueResult;
use Cobalt\SchemaPrototypes\EncryptedResult;
use Cobalt\SchemaPrototypes\ObjectResult;

class UserPersistance extends PersistanceMap {

    public function __get_schema(): array {
        return [
            'fname' => [
                new StringResult,
                'limit' => 150,
            ],
            'lname' => [
                new StringResult,
                'limit' => 150
            ],
            'uname' => new UniqueResult(new UserCRUD(), true),
            'pword' => new StringResult,
            'email' => new EmailAddressResult,
            'avatar' => new UploadResult,
            'flags' => [
                
            ],
            'flags.verified' => [
                new BooleanResult,
                'display' => fn ($val) => ($val) ? "<i name='check-decagram' title='Verified user'></i>" : ""
            ],
            'flags.password_reset_required' => new BooleanResult,
            'flags.locked' => new BooleanResult,
            'token' => new StringResult,
            'prefs' => new ObjectResult,
            'since' => new DateResult,
            'groups' => new ArrayResult,
            'permissions' => new ArrayResult,
        ];
    }
    
    public function display_name() {
        $name = $this->fname;
        if($name) $name .= " $this->lname";
        if(!$name) $name = $this->uname;
        return $name;
    }

    public function name() {
        if($this->fname && $this->lname) return "<span title='Username: $this->uname'>$this->fname " . $this->lname[0] . ".</span>";
        return $this->uname;
    }

    public function nametag() {
        return "<div class='cobalt-user--profile-display'>".$this->{"avatar.display"}." $this->name ".$this->{'flags.verified.display'}."</div>";
    }

}