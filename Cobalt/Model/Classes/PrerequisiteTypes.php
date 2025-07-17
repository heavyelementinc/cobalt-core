<?php
namespace Cobalt\Model\Classes;

enum PrerequisiteTypes: string {
    case EQUALS    = 'is';
    case NOTEQUALS = "ne";
    case IN        = "in";
}