This is the release infrastructure for Yarn.

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

# Nightly Builds + Release Infrastructure

In addition to the Debian and RPM repositories, this repo also contains infrastucture for releasing Yarn, both the stable release as well as nightly builds. These are all located in the `nightly` directory. This is hosted at https://nightly.yarnpkg.com/

Available endpoints on `nightly.yarnpkg.com`:

* `/archive_appveyor`: Archives all master builds from AppVeyor (https://ci.appveyor.com/project/kittens/yarn) onto the nightly builds site. Called as a webhook from the AppVeyor build
* `/archive_circleci`: Archives all master builds from CircleCI (https://circleci.com/gh/yarnpkg/yarn) onto the nightly builds site. Called as a webhook from the CircleCI build
* `/latest.json`: Contains the version numbers and URLs to all the latest nightly builds
* `/latest.[type]` (eg. `/latest.tar.gz`, `/latest.msi`): Redirects to the latest nightly build of this type
* `/latest-version`: Returns the version number of the latest nightly build
* `/latest-[type]-version` (eg. `/latest-tar-version`, `/latest-msi-version`): Returns the version number of the latest nightly build containing a file of this format. This is useful because the Windows and Linux builds are performed separately, so the version number of the latest MSI may differ from the other version numbers.
* `/[type]-builds` (eg. `/tar-builds`, `/msi-builds`): Returns a list of all the nightly builds available for this type. Used on the nightly builds page (https://yarnpkg.com/en/docs/nightly).
* `/release_appveyor`: Handles stable release builds from AppVeyor. Grabs the MSI from AppVeyor, Authenticode signs it, then uploads it to the GitHub release. Called as a webhook from the AppVeyor build
* `/release_circleci`: Similar to `release_appveyor`, except for CircleCI builds. Called a webhook from the CircleCI build
* `/sign_releases`: GPG signs all `.tar.gz` and `.js` files for all GitHub releases, attaching the signatures as `.asc` files to the GitHub releases

Files in the `nightly` directory:
* `nginx.conf`: Nginx configuration for `nightly.yarnpkg.com`
* `api`: Contains publicly accessible endpoints for `nightly.yarnpkg.com`
* `lib`: Contains libraries used by the release site  
  * `config.php`: Contains all configuration for the release infra. Includes API tokens (AppVeyor, CircleCI, GitHub), GPG IDs to use when signing files, and path to the Authenticode key for signing the Windows installer.
