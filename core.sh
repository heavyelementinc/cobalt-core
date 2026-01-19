#!/bin/bash
BASEDIR=$(dirname $(readlink -f "$0"))
php "$BASEDIR/cli/cobalt.php" $@