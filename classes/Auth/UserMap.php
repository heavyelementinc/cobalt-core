<?php

namespace Auth;

use Cobalt\Maps\PersistanceMap;
use Cobalt\SchemaPrototypes\Basic\ArrayResult;
use Cobalt\SchemaPrototypes\Basic\BooleanResult;
use Cobalt\SchemaPrototypes\Basic\DateResult;
use Cobalt\SchemaPrototypes\Basic\FakeResult;
use Cobalt\SchemaPrototypes\Basic\StringResult;
use Cobalt\SchemaPrototypes\Compound\UniqueEmailResult;
use Cobalt\SchemaPrototypes\Compound\UniqueResult;
use Cobalt\SchemaPrototypes\Compound\UploadImageResult;
use Cobalt\SchemaPrototypes\MapResult;
use Cobalt\SchemaPrototypes\Wrapper\IdResult;
use Drivers\Database;
use Validation\Exceptions\ValidationIssue;

class UserMap extends PersistanceMap {

    public function __set_manager(?Database $manager = null): ?Database {
        return new UserCRUD();
    }

    public function __get_schema(): array {
        return [
            'fname' => [
                new StringResult
            ],
            'lname' => [
                new StringResult
            ],
            'uname' => [
                new UniqueResult(new UserCRUD(), true),
                'required' => true
            ],
            'display_name' => [
                new FakeResult,
                'get' => function () {
                    $name = $this->fname;
                    if($name) $name .= " $this->lname";
                    if(!$name) $name = $this->uname;
                }
            ],
            'name' => [
                new FakeResult,
                'get' => function () {
                    return "<div class='cobalt-user--profile-display'>".$this->{"avatar.display"}." $this->name ".$this->{'flags.verified.display'}."</div>";
                }
            ],
            'pword' => [
                new StringResult,
                'filter' => function ($value) {
                    $password_fail = "";

                    /** Check if the password starts or ends with whitespace (not allowed) */
                    if ($value !== trim($value)) $password_fail .= "Passwords must not begin or end with spaces.\n";
            
                    /** Check if the password length meets the minimum required length */
                    if (strlen($value) < app("Auth_min_password_length")) $password_fail .= "Password must be at least " . app("Auth_min_password_length") . " characters long.\n";
            
                    /** Detect if submitted passwords are all alphabetical or all numerical characters */
                    if (ctype_alpha($value) || ctype_digit($value)) $password_fail .= "Password must include at least one letter and one number.\n";
            
                    /** Check if strings are only comprised of alphanumeric characters */
                    if (ctype_alnum($value)) $password_fail .= "Password must contain at least one special character.\n";
            
                    if (!empty($password_fail)) throw new ValidationIssue($password_fail);
                    
                    $this->__modify("flags.password_reset_required", false, false);
                    $this->__modify("flags.password_last_changed_by", session("_id") ?? "CLI", false);
                    $this->__modify("flags.password_last_changed_on", $this->make_date(), false);
            
                    /** Finally, we have a valid password. */
                    return password_hash($value, PASSWORD_DEFAULT);
                }
            ],
            'email' => [
                new UniqueEmailResult(new UserCRUD(), true)
            ],
            'avatar' => [
                new UploadImageResult,
                // 'get' => fn ($val) => $this->getAvatar($val),
                // 'set' => fn ($val) => $this->setAvatar($val),
                // 'display' => fn ($val) => $this->displayAvatar($val),
            ],
            'flags' => [
                new MapResult,
                'schema' => [
                    'verified' => new BooleanResult,
                    'password_reset_required' => new BooleanResult,
                    'password_last_changed_by' => new IdResult,
                    'password_last_changed_on' => new DateResult,
                    'locked' => new BooleanResult,
                ]
            ],
            'token' => new ArrayResult,
        ];
    }

}