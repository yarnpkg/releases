<?php
class Config {
  // GitHub organization name, repository name, and Git branch.
  // Only builds from this branch in this repo will be archived.
  const ORG_NAME = 'yarnpkg';
  const REPO_NAME = 'yarn';
  const BRANCH = 'master';

  const CIRCLECI_TOKEN = 'CHANGEME';

  const ARTIFACT_PATH = __DIR__.'/../public/';
}
