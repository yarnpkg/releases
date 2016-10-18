#!/bin/bash
set -ex

if [ ! -f "$1" ]; then
  echo 'Please provide the name of a valid package to add'
  exit 1
fi;

PACKAGE_FILE=`basename $1`
GPGKEY=6963F07F

cp $1 .
./sign-rpm.sh --key-id=$GPGKEY $PACKAGE_FILE
createrepo --update .
gpg2 --detach-sign --armor --local-user $GPGKEY --yes repodata/repomd.xml
