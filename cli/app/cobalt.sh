#!/bin/bash
BASEDIR=$(dirname $(readlink -f "$0"))
php "$BASEDIR/../cobalt-core/cli/cobalt.php" "--app=$BASEDIR" $@