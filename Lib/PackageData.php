<?php
App::uses('Github', 'Model');

class PackageData {

  public function __construct($owner, $name, $Github) {
    $this->owner = $owner;
    $this->name = $name;
    $this->Github = $Github;
  }

  protected function out($message) {
    CakeLog::debug($message);
  }

  public function retrieve() {
    $owner = $this->owner;
    $name = $this->name;

    try {
      $repo = $this->fetchRepo($owner, $name);
    } catch (NotFoundException $e) {
      return array('deleted' => true);
    }

    if (!$repo) {
      return false;
    }

    $data = array();
    $data['name'] = $name;
    $data['repository_url'] = $this->getRepositoryUrl($repo);
    $data['homepage'] = $this->getHomepage($repo);
    $data['description'] = $this->getDescription($repo);
    $data['contributors'] = $this->getContributors($repo, $owner);
    $data['collaborators'] = $this->getCollaborators($repo, $owner);
    $data['open_issues'] = $this->getIssues($repo);
    $data['created_at'] = $this->getCreatedAt($repo);
    $data['last_pushed_at'] = $this->getLastPushedAt($repo);

    $countFields = array(
      'forks',
      'forks_count',
      'network_count',
      'open_issues',
      'open_issues_count',
      'stargazers_count',
      'subscribers_count',
      'watchers',
      'watchers_count',
    );
    foreach ($countFields as $countField) {
      $data[$countField] = (empty($repo[$countField])) ? 0 : $repo[$countField];
    }

    $unset = array();
    foreach ($data as $key => $value) {
      if ($value === null) {
        $unset[] = $key;
      }
    }

    foreach ($unset as $key) {
      unset($data[$key]);
    }

    return $data;
  }

  public function fetchRepo($owner, $name) {
    $this->out('Retrieving repository');
    $repo = $this->Github->find('repository', array(
      'owner' => $owner,
      'repo' => $name
    ));

    $this->out(sprintf('Repo Data: %s', json_encode($repo)));
    if (empty($repo['Repository'])) {
      $this->out('No repo data found! Exiting...');
      return false;
    }

    if (!empty($repo['Repository']['message'])) {
      $this->out(sprintf('Error retrieving package: %s', $repo['Repository']['message']));
      if ($repo['Repository']['message'] === 'Not Found') {
        $this->out('Repository doesn\'t exist anymore! Exiting...');
        throw new NotFoundException($repo['Repository']['message']);
      }

      $this->out('No repo data found! Exiting...');
      return false;
    }

    return $repo['Repository'];
  }

  public function getHomepage($repo) {
    $this->out('Detecting homepage');
    $homepage = null;

    if (!empty($repo['homepage']) && !is_array($repo['homepage'])) {
      $homepage = $repo['homepage'];
    }

    if (strstr($homepage, 'https://github.com') !== false) {
      $homepage = null;
    }

    return $homepage;
  }

  public function getIssues($repo) {
    $this->out('Detecting number of issues');
    return $repo['has_issues'] ? $repo['open_issues'] : null;
  }

  public function getContributors($repo, $owner) {
    $this->out('Detecting total number of contributors');
    $contributors = $this->Github->find('repository', array(
      'owner' => $owner,
      'repo' => $repo['name'],
      '_action' => 'contributors',
    ));

    return empty($contributors) ? null : count($contributors);
  }

  public function getCollaborators($repo, $owner) {
    $this->out('Detecting number of collaborators');
    $collaborators = $this->Github->find('repository', array(
      'owner' => $owner,
      'repo' => $repo['name'],
      '_action' => 'collaborators',
    ));

    return empty($collaborators) ? null : count($collaborators);
  }

  public function getDescription($repo) {
    return empty($repo['description']) ? null : $repo['description'];
  }

  public function getCreatedAt($repo) {
    return empty($repo['created_at']) ? null : substr(str_replace('T', ' ', $repo['created_at']), 0, 19);
  }

  public function getLastPushedAt($repo) {
    return empty($repo['pushed_at']) ? null : substr(str_replace('T', ' ', $repo['pushed_at']), 0, 19);
  }

  public function getRepositoryUrl($repo) {
     return empty($repo['html_url']) ? null : $repo['html_url'];
  }

}
