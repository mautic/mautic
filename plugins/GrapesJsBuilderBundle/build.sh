#!/bin/sh

working_dir=$(pwd)

while getopts b: flag
do
    case "${flag}" in
        b) buildType=${OPTARG};;
    esac
done

cd Assets/library/js/grapesjs
npm run build:css
npm run build:js

cd $working_dir

if [[ $buildType == "prod" ]]; then
  npm run build
else
  npm run build-dev
fi
