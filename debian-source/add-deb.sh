#!/bin/bash
# Updates the Debian repo for stable builds
#
# To create repo:
# aptly -config=./.aptly.conf repo create -distribution=stable -component=main -architectures=amd64,i386,all yarn
# aptly -config=./.aptly.conf publish repo -gpg-key=9D41F3C3 -architectures=i386,amd64,all yarn

set -ex

./update-aptly-config.sh

# Aptly doesn't support publishing into a custom directory, so we do some hacks:
# Move the public directory to where Aptly expects it, publish, then move it
# back to our preferred location at the end.
mkdir -p public/
mv ../debian/* public/

# Add the package to the repo and publish the changes
aptly -config=./.aptly.conf repo add yarn $1
aptly -config=./.aptly.conf publish update -gpg-key=9D41F3C3 stable

# Move the public files back to the right place
mv public/* ../debian/
