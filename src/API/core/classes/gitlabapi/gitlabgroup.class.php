<?php

namespace EnedisLabBZH\Core\GitlabApi;

use EnedisLabBZH\Core\CoreException;

/**
 * Group
 */
class GitlabGroup
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
        return $this->gitlab->request("/groups/{$this->id}/");
    }

    /**
     * getIssues
     *
     * @return array
     */
    public function getIssues()
    {
        return $this->gitlab->request("/groups/{$this->id}/issues?state=opened&per_page=200");
    }

    /**
     * getIssuesWithLabel
     *
     * @param  mixed $labels
     * @return array
     */
    public function getIssuesWithLabel($labels)
    {
        if (!is_array($labels)) {
            throw new CoreException('You must pass (array) $labels to retrieve labelized issues');
        }
        $params = urlencode(implode(",", $labels));

        return $this->gitlab->request("/groups/{$this->id}/issues?state=opened&labels=$params");
    }

    /**
     * getSubgroups
     *
     * @return array
     */
    public function getSubgroups()
    {
        return $this->gitlab->request("/groups/{$this->id}/subgroups");
    }

    /**
     * getProjects
     *
     * @return array
     */
    public function getProjects()
    {
        return $this->gitlab->request("/groups/{$this->id}/projects?per_page=200");
    }

    /**
     * getBadges
     *
     * @return array
     */
    public function getBadges()
    {
        return $this->gitlab->request("/groups/{$this->id}/badges");
    }

    /**
     * getAccessTokens
     *
     * @return array
     */
    public function getAccessTokens()
    {
        return $this->gitlab->request("/groups/{$this->id}/access_tokens");
    }

    /**
     * getLabels
     *
     * @return array
     */
    public function getLabels()
    {
        return $this->gitlab->request("/groups/{$this->id}/labels");
    }

    /**
     * getMembers
     *
     * @return array
     */
    public function getMembers()
    {
        return $this->gitlab->request("/groups/{$this->id}/members?per_page=200");
    }
}
