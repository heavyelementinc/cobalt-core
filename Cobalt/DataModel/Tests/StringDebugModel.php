<?php
namespace Cobalt\DataModel\Tests;

use Cobalt\DataModel\Directives\Base\DirectiveCommon;
use Cobalt\DataModel\Directives\Filters\Clearable;
use Cobalt\DataModel\Types\DataModel;
use Cobalt\DataModel\Directives\DefaultValue;
use Cobalt\DataModel\Directives\Filters\Max;
use Cobalt\DataModel\Directives\Filters\Min;
use Cobalt\DataModel\Directives\Filters\Nullable;
use Cobalt\DataModel\Directives\Filters\Pattern;
use Cobalt\DataModel\Directives\PrivateValue;
use Cobalt\DataModel\Directives\Filters\Required;
use Cobalt\DataModel\Directives\Filters\Valid;
use Cobalt\DataModel\Directives\StringDirective;
use Cobalt\DataModel\Types\StringType;
use Override;

class StringDebugModel extends DataModel {
    #[Clearable()]
    readonly StringType $clearable;

    #[Clearable('clearableFunct')]
    readonly StringType $clearableFn;

    function clearableFunct(DirectiveCommon $clear, $value) {
        return true;
    }


    #[DefaultValue("Successful test!")]
    readonly StringType $defaultValue;

    #[DefaultValue("defaultFunct", true)]
    readonly StringType $defaultValueFn;
    
    function defaultFunct(DirectiveCommon $def, $value) {
        return "Some Default value";
    }

    #[Min(5)]
    readonly StringType $min;

    #[Max(5)]
    readonly StringType $max;

    readonly StringType $notNullable;

    #[Nullable(true)]
    // #[DefaultValue("test")]
    readonly StringType $nullable;

    #[Pattern("/[a-b]/")]
    readonly StringType $pattern;

    #[PrivateValue(true)]
    readonly StringType $private;

    #[Required(true)]
    readonly StringType $required;
    
    #[Valid([
        'test1' => 'Test One',
        'test2' => 'Test Two',
        'test3' => [
            'value' => 'Test Three'
        ]
    ])]
    readonly StringType $valid;

    #[Valid('fromFunction')]
    readonly StringType $validFromFunction;

    #[Override]
    public function getDefaultField(): StringType {
        return $this->field;
    }

    #[Override]
    public function getCollectionName($string = null): string {
        return "trueDatabase";
    }

    public function fromFunction():array {
        return [
            'test1' => 'Test One'
        ];
    }
}
