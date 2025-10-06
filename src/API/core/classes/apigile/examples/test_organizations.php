<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr">

<head>
    <title>Test organisations API</title>
    <meta charset="UTF-8">
</head>

<body>
    <pre>
<?php

include_once __DIR__ . '/../../../core_include.php'; // for $_tablesPrefix
include_once __DIR__ . '/../../../../my-config.inc.php'; // for $_tablesPrefix
require_once __DIR__ . '/../../../autoload.php';

use EnedisLabBZH\Core\Apigile\ApiOrganizations;
use EnedisLabBZH\Core\CoreException;

try {

    /**
     * Start counter to measure total execution time
     */
    $time_start = microtime(true);

    try {
        $apiOrganizations = new ApiOrganizations();
        $apiOrganizations->selectDR('DR BRETAGNE');
        $organizations = $apiOrganizations->getOrganizations();
        $apiOrganizations->formatAsArray($organizations);
    } catch (Exception $e) {
        if ($e->getMessage() === 'error: Oauth2 json returned code 403!') {
            throw new CoreException(__LINE__ . " : cette application n'est pas raccordée à l'API organisations !");
        } elseif ($e->getMessage() === 'error: Oauth2 json returned code 503!') {
            throw new CoreException(__LINE__ . " : cette application ne peut pas requêter l'API organisations !");
        } else {
            throw new CoreException($e->getMessage());
        }
    }

    /**
     * Sort the array $organizations by organisation_level
     */
    function compareOrganisationId($a, $b)
    {
        return strcmp($a['organisation_id'], $b['organisation_id']);
    }
    usort($organizations, 'compareOrganisationId');

    /**
     * Create a string containing a table full of organizations
     */
    $table = '
    <table>
        <tr>
            <th>code_uo<th>
            <th>name</th>
        </tr>';
    foreach ($organizations as $organization) {
        $table .= "
        <tr>
            <td>{$organization['organisation_id']}</td>
            <td>" . str_repeat('&nbsp;', 15 * ($organization['organisation_level'] - 5)) . "{$organization['name']}</td>
        </tr>";
    }
    $table .= '</table>';

    /**
     * Calculate execution time and display the organizations table.
     */
    echo "\nSur $endpoint_api, il y a " . count($organizations) . " résultats.\n"
        . "La requête a duré " . round((microtime(true) - $time_start) * 10) / 10 . " secondes.$table";
    unset($organizations, $table);
} catch (Exception $e) {

    error_log($e->getMessage());
    error_log($e->getTraceAsString());

    echo $e->getMessage();
    echo $e->getTraceAsString();
}
