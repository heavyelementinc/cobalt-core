<?php
namespace Cobalt\ContactForm\Model;

use Cobalt\ContactForm\Controllers\Submissions;
use Cobalt\Controllers\ModelController;
use Cobalt\Model\Interfaces\Migration;
use Cobalt\Model\Model;
use Cobalt\Model\Types\DateType;
use Cobalt\Model\Types\EmailAddressType;
use Cobalt\Model\Types\EnumType;
use Cobalt\Model\Types\MarkdownType;
use Cobalt\Model\Types\StringType;
use Cobalt\Model\Types\UserIdType;
use Drivers\DatabaseManagement;
use MongoDB\UpdateResult;

class FormSubmission extends Model implements Migration {
    
    public function defineSchema(array $schema = []): array {
        $this->__set_index_checkbox_state(has_permission("Contact_form_submissions_delete", null, null, false));
        $addtl = new AdditionalContactFields();
        $fields = $addtl->defineSchema();
        $schema = [
            "name" => [
                new StringType,
                'char_limit' => 150,
                'index' => [
                    'title' => 'Name',
                    'order' => 0,
                    'sort' => -1,
                    'view' => fn () => $this->name
                ]
            ],
            "organization" => [
                new StringType,
                'char_limit' => 150,
                'illegal_chars' => '<>',
                'index' => [
                    'title' => 'Org',
                    'order' => 1
                ]
            ],
            "email" => [
                new EmailAddressType,
                'index' => [
                    'title' => 'Email',
                    'order' => 2,
                ]
            ],
            "phone" => new StringType,
            "preferred" => [
                new EnumType,
                'valid' => [
                    'email' => "Email",
                    'phone' => "Phone"
                ]
            ],
            "additional" => [
                new MarkdownType,
                'char_limit' => 1800
            ],
            "read" => [
                new UserIdType,
                'getUsers' => function ($val, $ref) {
                    if(!has_permission('Contact_form_submissions_modify', null, null, false)) return "";
                    return $ref->eachToView("{{doc.uname}}");
                },
                'status' => function ($val, $ref) {
                    if($val) return "read";
                    return "unread";
                },
                'index' => [
                    'title' => 'Read Status',
                    'order' => 3,
                    'sortable' => false,
                    'view' => function () {
                        if(in_array(session("_id"), $this->read->value ?? [])) return "Read";
                        return "Unread";
                    }
                ]
            ],
            "date" => [
                new DateType,
                'index' => [
                    'title' => 'Date',
                    'order' => 1,
                    'sort' => -1,
                    // 'view' => fn () => $this->date->format("c")
                ]
            ],
            "ip" => new StringType,
            "token" => new StringType,
            "type" => new StringType,
        ];
        $schema += $fields;
        return $schema;
    }

    public function defineController(): ModelController {
        return new Submissions();
    }

    public static function __getVersion(): string {
        return "2.0";
    }

    public function getCollectionName($string = null): string {
        return "CobaltContactForm";
    }


    public function __initializeDataset() {
        throw new \Exception('Not implemented');
    }

    public function __beforeMigrationUpgrade(array $doc, array &$mutated_doc, array &$update, int $count, DatabaseManagement $manager): void {
        
    }

    public function __afterMigrationUpgrade(UpdateResult $result, array $mutated_doc, array $doc, DatabaseManagement $manager): void {
        
    }

}