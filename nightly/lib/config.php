<?php
class Config {
  // GitHub organization name, repository name, and Git branch.
  // Only builds from this branch in this repo will be archived.
  const ORG_NAME = 'yarnpkg';
  const REPO_NAME = 'yarn';
  const BRANCH = 'master';
  const RELEASE_TAG_FORMAT = '/v[0-9]+(\.[0-9]+)*/';

  // GitHub organization and repository used for releases. This is normally the
  // same as above, but can be changed for debugging purposes
  //const RELEASE_ORG_NAME = 'Daniel15Test';
  const RELEASE_ORG_NAME = Config::ORG_NAME;
  const RELEASE_REPO_NAME = Config::REPO_NAME;

  // Auth token for sign_releases endpoint
  const SIGN_AUTH_TOKEN = 'CHANGEME';
  // File types that should be GPG signed as part of GitHub releases
  const SIGN_FILE_TYPES = '/\.(tar\.gz|js)$/';

  const GITHUB_TOKEN = 'CHANGEME';
  const CIRCLECI_TOKEN = 'CHANGEME';

  const APPVEYOR_USERNAME = 'kittens';
  const APPVEYOR_PROJECT_SLUG = 'yarn';
  const APPVEYOR_WEBHOOK_AUTH_TOKEN = 'Bearer CHANGEME';

  const JENKINS_URL = 'https://build.dan.cx/';
  const JENKINS_VERSION_JOB = 'yarn-version';
  const JENKINS_VERSION_TOKEN = 'CHANGEME';

  const ARTIFACT_PATH = __DIR__.'/../artifacts/';
  const DEBIAN_INCOMING_PATH = __DIR__.'/../deb-incoming/';

  const GPG_NIGHTLY = 'FD2497F5';
  const GPG_RELEASE = '9D41F3C3';

  const AUTHENTICODE_KEY = '/change/me.pfx';
  const AUTHENTICODE_PASS = '/change/me.key';
}
