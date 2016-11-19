<?php
class Config {
  // GitHub organization name, repository name, and Git branch.
  // Only builds from this branch in this repo will be archived.
  const ORG_NAME = 'yarnpkg';
  const REPO_NAME = 'yarn';
  const BRANCH = 'master';

  const SIGN_AUTH_TOKEN = 'CHANGEME';
  const GITHUB_TOKEN = 'CHANGEME';
  const CIRCLECI_TOKEN = 'CHANGEME';

  const APPVEYOR_USERNAME = 'kittens';
  const APPVEYOR_PROJECT_SLUG = 'yarn';
  const APPVEYOR_WEBHOOK_AUTH_TOKEN = 'Bearer CHANGEME';

  const ARTIFACT_PATH = __DIR__.'/../artifacts/';
  const DEBIAN_INCOMING_PATH = __DIR__.'/../deb-incoming/';

  const GPG_NIGHTLY = 'FD2497F5';
  const GPG_RELEASE = '9D41F3C3';
}
