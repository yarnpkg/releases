#!/bin/sh
set -ex

debian_source_path="$(dirname $(readlink --canonicalize-existing $0))"

# Aptly doesn't support passing root directory as argument, so we need to edit
# it in the config.
sed -i "s~rootDir.*~rootDir\": \"$debian_source_path\",~" $debian_source_path/.aptly.conf
