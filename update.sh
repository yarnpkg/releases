#!/bin/bash
set -ex

LATEST_VERSION=`curl --fail https://yarnpkg.com/latest-version`
echo "Latest version is $LATEST_VERSION"

# Check Debian version
./debian-source/update-aptly-config.sh
DEBIAN_VERSION=`aptly -config=debian-source/.aptly.conf package search yarn | sort -Vr | head -1 | grep -oP "yarn_\K([0-9\.]+)"`
! dpkg --compare-versions $DEBIAN_VERSION lt $LATEST_VERSION
DEBIAN_OUTDATED=$?

# Check RPM version
RPM_PATH="$(dirname $(readlink --canonicalize-existing $0))/rpm"
RPM_VERSION=`repoquery --repofrompath=yarn,file://$RPM_PATH --repoid=yarn --queryformat='%{VERSION}' yarn`
! dpkg --compare-versions $RPM_VERSION lt $LATEST_VERSION
RPM_OUTDATED=$?

if [[ $DEBIAN_OUTDATED -eq 0 && $RPM_OUTDATED -eq 0 ]]; then
  echo 'Both packages are up-to-date'
  exit 100
fi;

if [ $DEBIAN_OUTDATED -ne 0 ]; then
  echo 'Updating Debian'
  DEB_TEMP_DIR=`mktemp -d`
  wget --content-disposition -P $DEB_TEMP_DIR https://yarnpkg.com/latest.deb
  pushd debian-source
  ./add-deb.sh "$DEB_TEMP_DIR/"*.deb
  popd
  rm "$DEB_TEMP_DIR/"*.deb
  rmdir $DEB_TEMP_DIR
fi;

if [ $RPM_OUTDATED -ne 0 ]; then
  echo 'Updating RPM'
  RPM_TEMP_DIR=`mktemp -d`
  wget --content-disposition -P $RPM_TEMP_DIR https://yarnpkg.com/latest.rpm
  pushd rpm
  ./add-rpm-package.sh "$RPM_TEMP_DIR/"*.rpm
  popd
  rm "$RPM_TEMP_DIR/"*.rpm
  rmdir $RPM_TEMP_DIR
fi;

echo 'Updated!'
