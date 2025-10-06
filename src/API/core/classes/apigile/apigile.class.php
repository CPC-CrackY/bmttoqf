<?php

namespace EnedisLabBZH\Core;

use CurlHandle;
use EnedisLabBZH\Core\CoreException;

/**
 * Cette classe permet de gérer le dialogue avec Apigile (authentification, etc.).
 * Chaque API doit faire l'objet d'une classe qui étend celle-ci.
 */
final class Apigile
{
    private string $clientId;
    private string $clientSecret;
    private string $endpointApi;
    private string $tokenType;
    private string $accessToken;
    private CurlHandle $curlHandler;
    private bool $rawErrors;
    private int $retriesCount;
    private int $secondsBeforeRetry;

    /**
     * __construct
     *
     * @param string $clientId Apigile credentials
     * @param string $clientSecret Apigile credentials
     * @param string $endpointApi url of the endpoint API
     * @param bool   $rawErrors true to display original error in case of exception.
     * @return void
     */
    public function __construct(string $clientId = null, string $clientSecret = null, string $endpointApi = null, bool $rawErrors = false)
    {
        if (!$clientId) {
            $appParameters = new CoreParameters();
            $endpointApi = $appParameters->getParameter(
                'endpoint_api',
                'Serveur APIGILE de cette application (recette ou prod)'
            );
            $clientId = $appParameters->getParameter(
                'apigile_id',
                'Login APIGILE de cette application'
            );
            $clientSecret = $appParameters->getParameter(
                'apigile_secret',
                'Password APIGILE de cette application',
                true
            );
        }
        if ($clientId == '') {
            throw new CoreException("Missing APIGILE parameters!");
        }
        $this->retriesCount = 5;
        $this->secondsBeforeRetry = 1;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->endpointApi = $endpointApi;
        $this->rawErrors = $rawErrors;
        $this->obtainNewAccessToken();
    }

    public function getRawErrors(): bool
    {
        return $this->rawErrors;
    }

    private function setCurlOptions()
    {
        curl_setopt(
            $this->curlHandler,
            CURLOPT_URL,
            "https://{$this->endpointApi}/oauth2/v3/token"
        );
        curl_setopt(
            $this->curlHandler,
            CURLOPT_RETURNTRANSFER,
            true
        );
        curl_setopt(
            $this->curlHandler,
            CURLOPT_HTTPHEADER,
            array(
                'Content-type: application/x-www-form-urlencoded'
            )
        );
        curl_setopt(
            $this->curlHandler,
            CURLOPT_POST,
            true
        );
        curl_setopt(
            $this->curlHandler,
            CURLOPT_POSTFIELDS,
            'grant_type=client_credentials'
        );
        curl_setopt(
            $this->curlHandler,
            CURLOPT_USERPWD,
            $this->clientId . ':' . $this->clientSecret
        );
    }

    private function obtainNewAccessToken()
    {
        try {
            $this->curlHandler = curl_init();
            $this->setCurlOptions();

            $json_reply = curl_exec($this->curlHandler);
            $http_code = curl_getinfo($this->curlHandler, CURLINFO_HTTP_CODE);
            if (200 !== $http_code) {
                if ($this->rawErrors === true) {
                    throw new CoreException($json_reply, $http_code);
                }

                throw new CoreException('error: Oauth2 returns code in line ' . __LINE__, $http_code);
            }
            if (false === $json_reply) {
                throw new CoreException('error: Oauth2 API is unreachable in line ' . __LINE__);
            }
            curl_close($this->curlHandler);

            $token_object = json_decode($json_reply);
            unset($json_reply);
            if (is_null($token_object)) {
                throw new CoreException('error: Oauth2 json can\'t be decoded in line ' . __LINE__);
            }

            if (property_exists($token_object, 'error')) {
                throw new CoreException("APIGILE ERROR: {$token_object->error}: {$token_object->error_description}");
            }

            $this->tokenType = $token_object->token_type;
            $this->accessToken = $token_object->access_token;
        } catch (CoreException $e) {
            $this->throwException($e);
        }
    }

    private function throwException($e)
    {
        $code = $e->getCode();
        $message = $e->getMessage();

        if ($this->rawErrors === true) {
            throw new CoreException('HTTP ' . $code . ' : ' . $message, $code);
        }
        $frenchMessage = '';
        if (400 === $code) {
            $frenchMessage = 'Erreur dans le format de la requête.';
        }
        if (401 === $code) {
            $frenchMessage = 'L\'accès à la ressource demandée est restreint, l\'utilisateur doit être authentifié.';
        }
        if (403 === $code) {
            $frenchMessage = 'Le serveur a bien reçu et compris la requête, mais refuse de la traiter.';
        }
        if (404 === $code) {
            $frenchMessage = 'La ressource requêtée n\'existe pas.';
        }
        if (405 === $code) {
            $frenchMessage = 'L\'appel d\'une méthode n\'a pas de sens sur cette ressource, '
                . 'ou l\'utilisateur n\'a pas l\'autorisation de réaliser cette requête';
        }
        if (406 === $code) {
            $frenchMessage = 'Le code décrit une incompatibilité de HTTP Header Accept.';
        }
        if (429 === $code) {
            $frenchMessage = 'Le quota d\'appel négocié pour l\'application a été dépassé '
                . '(quota instantané ou quota sur une période donnée), les appels sont ignorés temporairement.';
        }
        if (500 === $code) {
            $frenchMessage = 'La requête est valide, mais un problème a été rencontré lors du traitement.';
        }
        if (503 === $code) {
            $frenchMessage = 'Le serveur est temporairement incapable de traiter la requête, '
                . 'o\'à cause d\'une sur-charge temporaire ou une maintenance.';
        }
        error_log($frenchMessage);

        throw new CoreException($frenchMessage . "\n" . $message, $code);
    }

    public function refreshAccessToken()
    {
        $this->obtainNewAccessToken();
    }

    private function initCurl()
    {
        /**
         * Init cURL used for each request to an API (other then Oauth2).
         * The 600 seconds timeout seams right, as many request returning 100 results reply in less than 4 seconds.
         */
        $this->curlHandler = curl_init();
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: ' . $this->tokenType . ' ' . $this->accessToken
        );
        curl_setopt($this->curlHandler, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->curlHandler, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($this->curlHandler, CURLOPT_TIMEOUT, 600);
        curl_setopt($this->curlHandler, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->curlHandler, CURLOPT_RETURNTRANSFER, true);
    }

    private function get($url)
    {
        curl_setopt($this->curlHandler, CURLOPT_URL, $url);

        return $this->curlExecAndHandleHttpErrors();
    }

    private function post($url, $postFields)
    {
        curl_setopt($this->curlHandler, CURLOPT_URL, $url);
        curl_setopt($this->curlHandler, CURLOPT_POST, true);

        $json = json_encode($postFields);
        curl_setopt($this->curlHandler, CURLOPT_POSTFIELDS, $json);

        return $this->curlExecAndHandleHttpErrors();
    }

    private function curlExecAndHandleHttpErrors()
    {
        $nbRetry = $this->retriesCount;
        do {
            $message = null;
            $code = null;

            $jsonResponse = curl_exec($this->curlHandler);
            $httpCode = curl_getinfo($this->curlHandler, CURLINFO_HTTP_CODE);

            if ($this->rawErrors === true && $httpCode !== 200) {
                $message = $jsonResponse;
                $code = $httpCode;
            }
            if ($httpCode >= 400 && $httpCode !== 404) {
                $message = "Apigile::post() returned code {$httpCode}!";
                $code = $httpCode;
            }
            if ($jsonResponse === false) {
                $message = 'Apigile::post() returns false!';
                $code = null;
            }
            $nbRetry--;
            if ($message === null) {
                $nbRetry = 0;
            } else {
                sleep($this->secondsBeforeRetry);
            }
        } while ($nbRetry > 0);

        if ($message !== null) {
            throw new CoreException($message, $code);
        }

        $results = json_decode($jsonResponse, false);
        if ($results === null) {
            error_log(__FILE__ . '(' . __LINE__ . ') : Apigile::post() json format is invalid!');
        }

        return $results;
    }

    public function query($path, $query = '')
    {

        $this->initCurl();

        $url = "https://{$this->endpointApi}{$path}";
        '' !== $query
            && $url .= "?{$query}";

        return $this->get($url);
    }

    /**
     * postQuery send a post query to APIGILE API
     *
     * @param  mixed $path
     * @param  array $postFields
     * @return void
     */
    public function postQuery($path, array $postFields)
    {
        $this->initCurl();

        $url = "https://{$this->endpointApi}{$path}";

        return $this->post($url, $postFields);
    }

    /**
     * setSecondsBeforeRetry sets the number seconds to wait before a request is done again after error.
     * Default is 1
     *
     * @param  mixed $seconds
     * @return void
     */
    public function setSecondsBeforeRetry($seconds)
    {
        $this->secondsBeforeRetry = $seconds;
    }

    /**
     * setRetriesCount sets the number of requests to try while errors are return.
     *
     * @param  mixed $count
     * @return void
     */
    public function setRetriesCount($count)
    {
        $this->retriesCount = $count;
    }

    /**
     * paginatedQuery allows to do paginated queries.
     *
     * @param  mixed $path
     * @param  mixed $query
     * @param  mixed $max_results
     * @param  mixed $per_page
     * @param  mixed $mapFunc
     * @return void
     */
    public function paginatedQuery($path, $query, $max_results = 5000, $per_page = 100, $mapFunc = null): array
    {

        if (is_null($mapFunc)) {
            $mapFunc = function ($data) {
                !is_array($data)
                    && $data = [];

                return $data;
            };
        }

        $this->initCurl();
        $results = array();

        /**
         * This API will fail if too much results are requested.
         * Solution : loop to fetch all the expected results.
         */
        $per_page > $max_results
            && $per_page = $max_results;

        $page = 1;  // page to fetch: used in the loop.
        $count = 0; // $count is used to avoid infinite loop.
        do {
            $tries = 1;
            do {
                $url = "https://{$this->endpointApi}{$path}?per_page={$per_page}&page={$page}&{$query}";
                $data = $this->get($url);
                !$data && sleep(2);
                $tries++;
            } while (!$data && $tries < 6);
            $corrected = $mapFunc($data);
            is_array($corrected)
                && $results = array_merge($results, $corrected);
            $page++;
            $count++;
        } while ((count($mapFunc($data)) === $per_page) && ($count < ceil($max_results / $per_page)));

        curl_close($this->curlHandler);

        return $results;
    }
}
