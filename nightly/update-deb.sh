#!/bin/bash
# Updates the Debian repo for nightly builds
#
# To create repo:
# aptly repo create -distribution=nightly -component=main -architectures=amd64,i386,all yarn-nightly
# aptly publish repo -gpg-key=FD2497F5 -architectures=i386,amd64 yarn-nightly yarn-nightly

set -ex
aptly repo add ./public/
aptly publish update -gpg-key=FD2497F5 nightly yarn-nightly
