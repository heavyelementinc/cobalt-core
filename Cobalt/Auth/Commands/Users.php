<?php
namespace Cobalt\Auth\Commands;

use Cobalt\Auth\Users\Models\User;
use Cobalt\Commands\Attributes\CommandMethod;
use Cobalt\Commands\Attributes\Description;
use Cobalt\Commands\Attributes\Readline;
use Cobalt\Commands\Classes\CommandInterface;
use Cobalt\Commands\Classes\CommandItem;
use Cobalt\Commands\Classes\CommandList;
use Cobalt\Model\Types\MixedType;
use Cobalt\Model\Types\NumberType;
use Cobalt\Model\Types\StringType;
use Exception;
use Override;
use Validation\Exceptions\ValidationIssue;

class Users extends CommandInterface {
    #[Override]
    public function validCommands(): CommandList {
        $list = new CommandList();

        $list->add(new CommandItem($this, 'create', 'create'));
        $list->add(new CommandItem($this, 'list', 'list'));
        $list->add(new CommandItem($this, 'delete', 'delete'));
        $list->add(new CommandItem($this, 'update', 'update'));
        $list->add(new CommandItem($this, 'promote', 'promote'));
        $list->add(new CommandItem($this, 'demote', 'demote'));
        return $list;
    }
    
    #[Override]
    public function handleFlags(array $flags, CommandItem $item, string $method, array $arguments): int {
        return COBALT_COMMAND_SUCCESS;
    }

    #[Readline]
    #[CommandMethod]
    #[Description("Create a new user")]
    public function create(string $username, ?string $email = null, ?string $password = null):int {
        $user = new User();
        try {
            $user->uname->filter($username);
        } catch(ValidationIssue $e) {
            say($e->getMessage(), "e");
            return 1;
        }
        
        $valid = $user->__filter([
            'uname' => $username,
            'pword' => $password ?? filter_readline_private('Password: ', $user->pword),
            'email' => $email ?? filter_readline("Email: ", $user->email),
        ]);
        $result = $user->insertOne($valid);
        printf("User %s created with id: %s\n", $username, $result->getInsertedId());
        return COBALT_COMMAND_SUCCESS;
    }

    #[Description("Does nothing")]
    public function read(string $username) {

    }

    #[Description("Update the single field of a user account")]
    public function update(string $username, string $field, mixed $value) {
        $u = new User();
        /** @var User $user */
        $user = $u->findOne(['uname' => $username]);
        if(!$user) throw new Exception("User doesn't exist");
        if(!key_exists($field, $user->readSchema())) {
            throw new Exception("Not a valid field");
        }
        $f = $user->readSchema()[$field]['type'];
        if($f instanceof StringType == false && $f instanceof NumberType == false) {
            throw new Exception("Cannot set this field via the CLI");
        }
        if($f instanceof NumberType) {
            $value = $user->typecast($value);
        }
        try {
            $filtered = $f->filter($value);
            $u->updateOne(['_id' => $user->_id],['$set' => [$field => $value]]);
        } catch(ValidationIssue $e) {
            throw new Exception($e->getMessage());
        }
        return COBALT_COMMAND_SUCCESS;
    }

    #[Description("Delete a user")]
    public function delete(string $username) {
        $u = new User();
        $user = $u->findOne(['uname' => $username]);
        if(!key_exists('f',$_SERVER['flags'])) {
            $bool = cli_to_bool(readline("This will delete $user->fname. There is no undo. (y/N): "));
            if(!$bool) {
                say("Aborted.");
                return COBALT_COMMAND_SUCCESS;
            }
        }
        $u->deleteOne(['_id' => $user->_id]);
        return COBALT_COMMAND_SUCCESS;
    }

    #[Description("List all users")]
    public function list() {
        $fields = [
            'uname' => 1,
            'fname' => 1,
            'lname' => 1,
            'email' => 1,
            'is_root' => 1,
        ];
        $u = new User();
        $cursor = $u->find(
             [], 
            [
                'limit' => $u->countDocuments([]),
                'projection' => $fields
            ]
        );

        $table = [];
        $max = [];
        /** @var User $user */
        foreach($cursor as $i => $user) {
            foreach($fields as $key => $v) {
                switch($key) {
                    case "is_root":
                        $table[$i][$key] = ($user[$key]) ? "Root" : "Normal";
                        break;
                    default:
                        $table[$i][$key] = $user[$key];
                }
                $max[$key] = max($max[$key], strlen($table[$i][$key])) ?? 0;
            }
        }

        foreach($fields as $key => $v) {
            $fieldName = strip_tags($u->{$key}->getLabel());
            $max[$key] = max(strlen($fieldName), $max[$key]);
            print(str_pad($fieldName,$max[$key], " ", STR_PAD_BOTH) . " | ");
        }
        print("\n");
        foreach($table as $key => $v) {
            foreach($max as $i => $d) {
                print(str_pad($v[$i], $d) . " | ");
            }
            print("\n");
        }
        return COBALT_COMMAND_SUCCESS;
    }

    #[Description("Change a user's password")]
    public function password(string $user) {
        $u = new User();
        $result = $u->findOne(['uname' => $user]);
        if(!$result) {
            say("That username does not exist.", "e");
        }
        $validated = $u->__filter(['pword' => readline("New password: ")]);
        $u->updateOne(['_id' => $result['_id']], ['$set' => $validated]);
        return COBALT_COMMAND_SUCCESS;
    }

    #[Description("Make a user root")]
    public function promote(string $user) {
        $u = new User();
        $result = $u->findOne(['uname' => $user]);
        if(!$result) {
            say("That username does not exist.", "e");
        }
        $result->updateOne(['_id' => $u->_id], ['$set' => ['is_root' => true]]);
        return COBALT_COMMAND_SUCCESS;
    }

    #[Description("Remove a user's root status")]
    public function demote(string $user) {
        $u = new User();
        $result = $u->findOne(['uname' => $user]);
        if(!$result) {
            say("That username does not exist.", "e");
        }
        $result->updateOne(['_id' => $u->_id], ['$set' => ['is_root' => false]]);
        return COBALT_COMMAND_SUCCESS;
    }
}