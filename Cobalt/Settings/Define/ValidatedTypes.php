<?php

namespace Cobalt\Settings\Define;

enum ValidatedTypes: string {
    case boolean = "boolean";
    case integer = "integer";
    case double = "double";
    case string = "string";
    case array = "array";
}