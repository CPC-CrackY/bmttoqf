<?php

namespace EnedisLabBZH\Core;

use EnedisLabBZH\Core\CoreException;
use EnedisLabBZH\Core\CoreParameters;
use EnedisLabBZH\Core\GitlabApi\GitlabGroup;
use EnedisLabBZH\Core\GitlabApi\GitlabProject;

define('GITLAB_USE_CURL', 1);
define('GITLAB_USE_HTTPS', 2);

/**
 * GitlabApi
 */
class GitlabApi
{
    private $token;
    private $apiUrl;
    private $ch;
    private $initialized;
    private $method = GITLAB_USE_CURL;

    /**
     * __construct
     *
     * @param  mixed $token the token obtained via gitlab
     * @param  mixed $apiUrl the url to gitlab API
     * @return void
     */
    public function __construct($token = null, $apiUrl = null)
    {
        if ($token) {
            $this->token = $token;
            $this->apiUrl = $apiUrl;
        } else {
            $appParameters = new CoreParameters();
            $this->token = $appParameters->getParameter('gitlab_zac_token');
            $this->apiUrl = $appParameters->getParameter('gitlab_zac_api_url');
        }
    }

    /**
     * Singletons should not be cloneable.
     */
    protected function __clone()
    {
    }

    /**
     * setToken.
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * setApiUrl.
     */
    public function setApiUrl($apiUrl)
    {
        $this->apiUrl = $apiUrl;
    }

    /**
     * getApiUrl.
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * initCurl
     *
     * @param  mixed $ch the curl handler
     * @return void
     */
    private function initCurl()
    {
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $this->token));
    }

    /**
     * useCurl set the curl method for this class
     *
     * @return void
     */
    public function useCurl()
    {
        $this->method = GITLAB_USE_CURL;
    }

    /**
     * useHttps set the https method for this class
     *
     * @return void
     */
    public function useHttps()
    {
        $this->method = GITLAB_USE_HTTPS;
    }

    /**
     * request
     *
     * @param  string $request the url to request
     * @return object the json response
     * @throws CoreException if the cURL request fails
     */
    public function request($request)
    {
        if (null === $this->token) {
            throw new CoreException("You MUST initialize token!");
        }
        if (null === $this->apiUrl) {
            throw new CoreException("You MUST initialize apiUrl!");
        }
        if ($this->method === GITLAB_USE_CURL) {
            return $this->curlRequest($request);
        }
        if ($this->method === GITLAB_USE_HTTPS) {
            return $this->httpsRequest($request);
        }
    }

    /**
     * curlRequest
     *
     * @param  string $request the url to request
     * @return object the json response
     * @throws CoreException if the cURL request fails
     */
    private function curlRequest($request)
    {
        if ($this->method === GITLAB_USE_CURL && !$this->initialized) {
            $this->initCurl();
            $this->initialized = true;
        }
        curl_setopt($this->ch, CURLOPT_URL, $this->apiUrl . $request);
        if (false === ($result = curl_exec($this->ch))) {
            throw new CoreException("cURL error");
        }

        return json_decode($result, true);
    }

    /**
     * httpsRequest
     *
     * @param  string $request the url to request
     * @return object the json response
     * @throws CoreException if the cURL request fails
     */
    private function httpsRequest($request)
    {
        $request .= strpos($request, '?') !== false ? '&' : '?';
        $request .= 'private_token=' . $this->token;
        echo $this->apiUrl . $request;
        if (false === ($result = file_get_contents($this->apiUrl . $request))) {
            throw new CoreException("https error");
        }

        return json_decode($result, true);
    }

    /**
     * getGroup
     *
     * @return object group
     */
    public function getGroup($id)
    {
        if (0 === intval($id)) {
            throw new CoreException('You must pass (int) $id to retrieve group');
        }

        return new GitlabGroup($this, $id);
    }

    /**
     * getProject
     *
     * @return object project
     */
    public function getProject($id)
    {
        if (0 === intval($id)) {
            throw new CoreException('You must pass (int) $id to retrieve project');
        }

        return new GitlabProject($this, $id);
    }
}
