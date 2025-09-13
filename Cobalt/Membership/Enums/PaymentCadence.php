<?php
namespace Cobalt\Membership\Enums;

enum PaymentCadence:string {
    case MONTHLY = "monthly";
    case ANNUAL  = "annual";
    case UNKNOWN = "unknown";
}