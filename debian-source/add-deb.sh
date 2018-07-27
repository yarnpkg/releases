#!/bin/bash
# Updates the Debian repo for stable builds
#
# To create repo:
# aptly -config=./.aptly.conf repo create -distribution=stable -component=main -architectures=amd64,i386,armhf,arm64,all yarn
# aptly -config=./.aptly.conf publish repo -gpg-key=E074D16EB6FF4DE3 -architectures=i386,amd64,armhf,arm64,all -origin=yarn -label="yarn-stable" yarn

set -ex

./update-aptly-config.sh

# Aptly doesn't support publishing into a custom directory, so we do some hacks:
# Move the public directory to where Aptly expects it, publish, then move it
# back to our preferred location at the end.
mkdir -p public/
mv ../debian/* public/

# Remove packages as Aptly complains with "file already exists and is different"
# This is due to how it uses hardlinks, which Git doesn't store as hardlinks.
# The "publish" command will copy across all the .deb files from Aptly's pool.
rm -rf public/pool/

# Add the package to the repo and publish the changes. We need to republish
# *both* stable and rc, due to the removal of the entire pool directory above.
aptly -config=./.aptly.conf repo add $2 $1
aptly -config=./.aptly.conf publish update -gpg-key=72ECF46A56B4AD39C907BBB71646B01B86E50310 stable
aptly -config=./.aptly.conf publish update -gpg-key=72ECF46A56B4AD39C907BBB71646B01B86E50310 rc

# Move the public files back to the right place
mv public/* ../debian/
