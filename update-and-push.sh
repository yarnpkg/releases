#!/bin/bash
set -ex

#git reset --hard HEAD
git clean -fd
git pull

./update.sh
if [ $? -ne 0 ]; then
  exit 1
fi;

LATEST_VERSION=`curl --fail https://yarnpkg.com/latest-version`
git add -A
git commit -a -m "Automated upgrade to Yarn $LATEST_VERSION" --author='DanBuild <build@dan.cx>'
git push origin
