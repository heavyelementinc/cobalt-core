<?php

namespace Cobalt\Membership\Enums;

enum Platform:string {
    case GHOST = "ghost";
    case PATREON = "patreon";
    case YOUTUBE = "youtube";
    case UNKNOWN = "unknown";
}