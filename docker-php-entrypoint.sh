#!/bin/sh

set -e

TASK=${MAKE_TASK:-deploy}

make $TASK

if [ -n "${TASK##*test*}" ]; then
	crond
	exec "$@"
fi
