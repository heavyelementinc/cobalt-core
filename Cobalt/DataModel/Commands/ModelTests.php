<?php

namespace Cobalt\DataModel\Commands;

use Cobalt\Commands\Classes\CommandInterface;
use Cobalt\Commands\Classes\CommandList;
use Override;
use Cobalt\Commands\Classes\CommandItem;
use Cobalt\DataModel\Types\ModelType;
use Cobalt\DataModel\Models\PrimaryDebugModel as DataModelTest;

class ModelTests extends CommandInterface {
    #[Override]
    public function validCommands(): CommandList {
        $list = new CommandList();
        $list->add(new CommandItem($this, 'test', 'test'), true);
        return $list;
    }

    #[Override]
    public function handleFlags(array $flags, CommandItem $item, string $method, array $arguments): int {
        return COBALT_COMMAND_SUCCESS;
    }

    function test(string $suite = "test") {
        $test = new DataModelTest();
        switch($suite) {
            case "string":
                say($test->string->getValue());
                $test->string->setValue("Title Override");
                say($test->string->getValue());
                break;
            case "json":
                $this->json_encode_test($test);
                break;
            case "valid":
                $this->valid_test($test);
                break;
            case "externalModel":
                $this->external_model($test);
                break;
            case "overload":
                $this->overload_test($test);
                break;
            case "array":
                $this->array_test($test);
                break;
        }
        
        return COBALT_COMMAND_SUCCESS;
    }

    function json_encode_test(DataModelTest $test) {
        say(json_encode($test->set->value));
        $test->set->setValue("off");
        say(json_encode($test->set->value));
    }

    function valid_test(DataModelTest $test) {
        say("Nullish coalenscence: " . ($test->valid->value ?? "passed"));
        $test->valid->setValue("garbage");
        assert($test->valid->display() === "Garbage", "Failed to display value");
        say("Value should display as 'Garbage' ". $test->valid->display());
        $test->valid->value = "test";
        say($test->valid->raw);
        say(json_encode($test->valid->valid(), JSON_PRETTY_PRINT));
        say($test->valid->options('test', ['stupid' => 'var', 'arbitrary' => ['classList' => 'dipshit', 'value' => 'iconic']]));
    }

    function external_model($test) {
        
    }

    function overload_test($test) {
        say(json_encode(isset($test->known_missing)));
        $test->known_missing = "some string";
        say(json_encode(isset($test->known_missing)));
        say($test->known_missing->value);
        say(json_encode($test->bsonSerialize(), JSON_PRETTY_PRINT));
    }

    function array_test($test) {
        say(json_encode($test->array->value));
        foreach($test->array as $key => $value) {
            say("Key: $key     Val: $value");
        }
    }
}