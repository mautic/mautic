#!/bin/sh

RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

PROJECT=`echo test_$(basename $PWD)_${CI_JOB_ID}_$(date +%s%N) | tr '[:upper:]' '[:lower:]' | tr -cd '[[a-z0-9]]'`
SYMFONY_ENV=test

compose () {
	OPTIONS="-f docker-compose.yml -p $PROJECT"

	if [[ -n $IMAGE ]]; then
		OPTIONS="$OPTIONS -f docker-compose.build.yml";
	fi

	SYMFONY_ENV=$SYMFONY_ENV docker-compose $OPTIONS $@
}

cleanup () {
	compose down -v

	if [[ -n $IMAGE ]]; then
		docker network rm nginx-proxy
	fi
}
trap 'cleanup ; printf "${RED}Tests Failed For Unexpected Reasons${NC}\n"' HUP INT QUIT PIPE TERM

OPTIONS=""
if [[ -n $IMAGE ]]; then
	docker network create nginx-proxy
	OPTIONS="$OPTIONS --no-build"
fi

compose up -d $OPTIONS $@

if [ $? -ne 0 ] ; then
	printf "${RED}Docker Compose Failed${NC}\n"
	exit -1
fi

compose logs -f app & TEST_EXIT_CODE=`docker wait ${PROJECT}_app_1`

if [ -z ${TEST_EXIT_CODE+x} ] || [ "$TEST_EXIT_CODE" -ne 0 ] ; then
	printf "${RED}Tests Failed${NC} - Exit Code: $TEST_EXIT_CODE\n"
else
	printf "${GREEN}Tests Passed${NC}\n"
fi

cleanup

exit $TEST_EXIT_CODE
