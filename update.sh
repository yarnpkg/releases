#!/bin/bash
set -ex

LATEST_VERSION=`curl --fail https://yarnpkg.com/latest-version`
echo "Latest version is $LATEST_VERSION"

# Check Debian version
DEBIAN_VERSION=`reprepro -b debian --list-format '${version}' -A amd64 list stable yarn`
! dpkg --compare-versions $DEBIAN_VERSION lt $LATEST_VERSION
DEBIAN_OUTDATED=$?

# Check RPM version
RPM_PATH="$(dirname $(readlink --canonicalize-existing $0))/rpm"
RPM_VERSION=`repoquery --repofrompath=yarn,file://$RPM_PATH --repoid=yarn --queryformat='%{VERSION}' yarn`
! dpkg --compare-versions $RPM_VERSION lt $LATEST_VERSION
RPM_OUTDATED=$?

if [[ $DEBIAN_OUTDATED -eq 0 && $RPM_OUTDATED -eq 0 ]]; then
  echo 'Both packages are up-to-date'
  exit 0
fi;

if [ $DEBIAN_OUTDATED -ne 0 ]; then
  echo 'Updating Debian'
  DEB_TEMP=`mktemp --suffix=.deb`
  wget https://yarnpkg.com/latest.deb -O $DEB_TEMP
  reprepro -b debian includedeb stable $DEB_TEMP
  rm $DEB_TEMP
fi;

if [ $RPM_OUTDATED -ne 0 ]; then
  echo 'Updating RPM'
  RPM_TEMP=`mktemp --suffix=.rpm`
  wget https://yarnpkg.com/latest.rpm -O $RPM_TEMP
  pushd rpm
  ./add-rpm-package.sh $RPM_TEMP
  popd
  rm $RPM_TEMP
fi;

echo 'Updated!'
