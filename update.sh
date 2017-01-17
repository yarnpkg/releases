#!/bin/bash
set -ex

updateDebian() {
  #DEBIAN_VERSION=`aptly -config=debian-source/.aptly.conf package search $2 | sort -Vr | head -1 | grep -oP "yarn_\K([0-9\.]+)"`
  # `aptly package search` doesn't allow filtering by distribution (eg. stable
  # vs rc) so we need to manually search the package metadata.
  DEBIAN_VERSION=`grep -oP 'Version: \K([0-9\.]+)' debian/dists/$3/main/binary-amd64/Packages | sort -Vr | head -1`
  ! dpkg --compare-versions $DEBIAN_VERSION lt $1
  DEBIAN_OUTDATED=$?

  if [ $DEBIAN_OUTDATED -ne 0 ]; then
    echo 'Updating Debian'
    DEB_TEMP_DIR=`mktemp -d`
    wget --content-disposition -P $DEB_TEMP_DIR https://yarnpkg.com/$4
    pushd debian-source
    ./add-deb.sh "$DEB_TEMP_DIR/"*.deb $2 $3
    popd
    rm "$DEB_TEMP_DIR/"*.deb
    rmdir $DEB_TEMP_DIR
  else
    echo 'Debian is up-to-date'
  fi;
}

updateRPM() {
  RPM_PATH="$(dirname $(readlink --canonicalize-existing $0))/rpm"
  RPM_VERSION=`repoquery --repofrompath=yarn,file://$RPM_PATH --repoid=yarn --queryformat='%{VERSION}' yarn`
  ! dpkg --compare-versions $RPM_VERSION lt $version
  RPM_OUTDATED=$?

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
}

updateStable() {
  version=`curl --fail https://yarnpkg.com/latest-version`
  echo "==== Latest stable version is $version ===="

  updateDebian $version yarn stable latest.deb
  updateRPM $version

  if [[ $DEBIAN_OUTDATED -ne 0 && $RPM_OUTDATED -ne 0 ]]; then
    STABLE_OUTDATED=1
  else
    STABLE_OUTDATED=0
  fi
}

updateRC() {
  version=`curl --fail https://yarnpkg.com/latest-rc-version`
  echo "==== Latest RC version is $version ===="

  updateDebian $version yarn-rc rc latest-rc.deb
  RC_OUTDATED=$DEBIAN_OUTDATED
}

./debian-source/update-aptly-config.sh
updateStable
updateRC

if [[ $STABLE_OUTDATED -eq 0 && $RC_OUTDATED -eq 0 ]]; then
  echo 'All packages are up-to-date'
  exit 100
fi;

echo 'Updated!'
