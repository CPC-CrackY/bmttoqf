<?php

namespace EnedisLabBZH\Core\Apigile;

use EnedisLabBZH\Core\Apigile;
use EnedisLabBZH\Core\CoreException;

final class ApiOrganizations
{
    private Apigile $apigile;
    private string $dr;

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
        $this->apigile = new Apigile($clientId, $clientSecret, $endpointApi, $rawErrors);
    }

    /**
     * selectDR sélectionne une DR pour obtenir sa liste hiérarchique
     * @param string $dr La DR au format texte, par ex. selectDR('DR Bretagne')
     * @return void
     */
    public function selectDR($dr)
    {
        $this->dr = $dr;

        return $this;
    }
    public function getOrganizations($depth = 3, $max_results = 500)
    {
        return $this->requestOrganizationsAPI(
            null,
            'exact_search=true&organisation_level=4&depth=' . $depth . '&direction=down&name=' . urlencode(strtoupper($this->dr)),
            $max_results
        );
    }
    public function getOrganizationsById($organisation_id, $depth = 3, $max_results = 500)
    {
        return $this->requestOrganizationsAPI(
            $organisation_id,
            'exact_search=true&organisation_level=4&depth=' . $depth . '&direction=down',
            $max_results
        );
    }

    private function requestOrganizationsAPI($organisation_id = null, $query = '', $max_results = 500)
    {
        try {
            $path = '/organisation_data/v2';
            $organisation_id && $path .= '/' . $organisation_id;

            return $this->apigile->paginatedQuery($path, $query, $max_results, 100, function ($data) {
                return $data->organisation_data->organisations[0]->child_organisations;
            });
        } catch (CoreException $e) {
            $this->throwException($e);
        }
    }

    private function throwException($e)
    {
        $code = $e->getCode();
        $message = $e->getMessage();

        if ($this->apigile->getRawErrors() === true) {
            throw new CoreException('HTTP ' . $code . ' : ' . $message, $code);
        }
        $frenchMessage = '';
        if (400 === $code) {
            $frenchMessage = "Erreur dans le format de la requête.";
        }
        if (401 === $code) {
            $frenchMessage = "Authentication required.";
        }
        if (403 === $code) {
            $frenchMessage = "Unauthorized.";
        }
        if (404 === $code) {
            $frenchMessage = "Pas de ressource correspondant à la requête.";
        }
        if (500 === $code) {
            $frenchMessage = "Erreur serveur.";
        }
        if (500 === $code) {
            $frenchMessage = "Service Unavailable.";
        }
        throw new CoreException($frenchMessage . "\n" . $message, $code);
    }

    public function formatAsArray(&$organizations)
    {

        /**
         * Pretifying results:
         * Organizations API returns an object.
         * It needs to be converted in associative array for simplicity.
         */
        $objectToAssociativeArray = function ($organization) {

            return @array(
                "name"                   => $organization->name,
                "organisation_id"        => $organization->organisation_id,
                "organisation_level"     => $organization->organisation_level,
                "parent_organisation_id" => $organization->parent_organisation_id,
            );
        };

        $organizations = array_map($objectToAssociativeArray, $organizations);
    }
}
