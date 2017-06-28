# Release Repositories

Currently, the release files for the Debian and RPM repositories are stored in this Git repo. This allows easy hosting of these files via a static file host such as GitHub Pages, and an easy way to revert in case any issues are encountered.

The files to handle the release repositories include:

* `debian-source`: Source for the Debian repo. This contains an [Aptly](https://www.aptly.info/) repository along with some helper shell scripts
  * `add-deb.sh [filename] [stable|rc]`: Adds a new package to the repo
* `debian`: Public files for the Debian repo
* `rpm`: Public files for the CentOS (RPM) repo

Some helper scripts are also included:
* `update.sh`: Checks the latest available version of Yarn (via https://yarnpkg.com/latest-version), and updates the Debian and RPM repositories if they don't contain this latest version
* `update-and-push.sh`: Clears local changes to the Git repo, runs `update.sh`, then commits the result if a newer version was found
