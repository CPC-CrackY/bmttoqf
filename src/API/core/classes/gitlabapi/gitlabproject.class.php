<?php

namespace EnedisLabBZH\Core\GitlabApi;

use EnedisLabBZH\Core\CoreException;

/**
 * Project
 */
class GitlabProject
{
    private $id;
    private $gitlab;

    /**
     * __construct
     *
     * @param  mixed $id
     * @return void
     */
    public function __construct($gitlab, $id)
    {
        $this->id = $id;
        $this->gitlab = $gitlab;

        return $this;
    }

    /**
     * get
     *
     * @return object
     */
    public function getProperties()
    {
        return $this->gitlab->request("/projects/{$this->id}/");
    }

    /**
     * getIssues
     *
     * @return array
     */
    public function getIssues()
    {
        return $this->gitlab->request("/projects/{$this->id}/issues?state=opened");
    }

    /**
     * getIssuesWithLabel
     *
     * @param  mixed $labels
     * @return array
     */
    public function getIssuesWithLabel(null|array $labels)
    {
        if (!is_array($labels)) {
            throw new CoreException('You must pass (array) $labels to retrieve labelized issues');
        }
        $params = urlencode(implode(",", $labels));

        return $this->gitlab->request("/projects/{$this->id}/issues?state=opened&labels=$params");
    }

    /**
     * getUsers
     *
     * @return array
     */
    public function getUsers()
    {
        return $this->gitlab->request("/projects/{$this->id}/users");
    }

    /**
     * getBranches
     *
     * @return array
     */
    public function getBranches()
    {
        return $this->gitlab->request("/projects/{$this->id}/repository/branches");
    }

    /**
     * getCommits
     *
     * @return array
     */
    public function getCommits()
    {
        return $this->gitlab->request("/projects/{$this->id}/repository/commits?per_page=200");
    }

    /**
     * getEnvironments
     *
     * @return array
     */
    public function getEnvironments()
    {
        return $this->gitlab->request("/projects/{$this->id}/environments");
    }

    /**
     * getEnvironment
     *
     * @param  mixed $environment_id
     * @return object
     */
    public function getEnvironment($environment_id)
    {
        if (false === is_int($environment_id)) {
            throw new CoreException('You must pass (int) $environment_id to retrieve environment details');
        }

        return $this->gitlab->request("/projects/{$this->id}/environments/$environment_id");
    }

    /**
     * getTags
     *
     * @return array
     */
    public function getTags()
    {
        return $this->gitlab->request("/projects/{$this->id}/repository/tags?per_page=200");
    }
}
