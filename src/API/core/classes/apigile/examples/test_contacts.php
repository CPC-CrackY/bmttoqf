<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr">

<head>
    <title>Test contacts API</title>
    <meta charset="UTF-8">
</head>
<style>
    table,
    tr,
    td,
    th {
        border-collapse: collapse;
        border: 1px solid #888;
    }

    td,
    th {
        padding: 0 3px;
    }

    th {
        text-align: left;
        background-color: black;
        color: white;
    }

    tr:nth-child(even) {
        background: #fff
    }

    tr:nth-child(odd) {
        background: #ddd
    }
</style>

<body>
    <pre>
<?php

include_once __DIR__ . '/../../../core_include.php'; // for $_tablesPrefix
include_once __DIR__ . '/../../../../my-config.inc.php'; // for $_tablesPrefix
require_once __DIR__ . '/../../../autoload.php';

use EnedisLabBZH\Core\Apigile\ApiContacts;
use EnedisLabBZH\Core\CoreException;

try {

    function displayTable($employees)
    {
        /**
         * Create a string containing a table full of employees
         */
        $table = '
<table>
    <tr>
        <th>NNI</th>
        <th>NOM</th>
        <th>Prénom</th>
        <th>Manager</th>
        <th>Assistant</th>
        <th>Métier</th>
        <th>business</th>
        <th>referent</th>
        <th>Employeur</th>
        <th>UM</th>
        <th>DUM</th>
        <th>SDUM</th>
        <th>FSDUM</th>
        <th>Code fsdum</th>
        <th>Code UO</th>
        <th>Code UM</th>
        <th>Site</th>
        <th>Adresse</th>
        <th>Ville</th>
        <th>Code postal</th>
        <th>Bâtiment</th>
        <th>Etage</th>
        <th>Salle</th>
        <th>e-mail</th>
        <th>Tél. fixe</th>
        <th>Tél. portable</th>
    </tr>';
        foreach ($employees as $employee) {
            $table .= "
    <tr>
        <td>{$employee['nni']}</td>
        <td>{$employee['last_name']}</td>
        <td>{$employee['first_name']}</td>
        <td>{$employee['manager']}</td>
        <td>{$employee['assistant']}</td>
        <td>{$employee['activity']}</td>
        <td>{$employee['business']}</td>
        <td>{$employee['referent']}</td>
        <td>{$employee['employer']}</td>
        <td>{$employee['um_label']}</td>
        <td>{$employee['dum_label']}</td>
        <td>{$employee['sdum_label']}</td>
        <td>{$employee['fsdum_label']}</td>
        <td>{$employee['fsdum']}</td>
        <td>{$employee['code_uo']}</td>
        <td>{$employee['code_um']}</td>
        <td>{$employee['sitename']}</td>
        <td>{$employee['address']}</td>
        <td>{$employee['city']}</td>
        <td>{$employee['zip_code']}</td>
        <td>{$employee['building']}</td>
        <td>{$employee['floor']}</td>
        <td>{$employee['room']}</td>
        <td>{$employee['email']}</td>
        <td>{$employee['phone']}</td>
        <td>{$employee['mobile']}</td>
    </tr>";
        }
        $table .= '</table>';
        echo $table;
        unset($table);
    }

    /**
     * Initiate Contacts API & fetch all employees in $employees array.
     * It's that simple.
     */
    $nni = 'F29645';
    // Si un nni est passé en paramètre, le script servira à afficher les données de l'agent.
    isset($_GET['nni']) && $nni = $_GET['nni'];
    echo "<h2>searchByNNI('" . htmlspecialchars($nni) . "')</h2>";

    try {
        $contacts = new ApiContacts();
    } catch (Exception $e) {
        throw new CoreException($e->getMessage(), $e->getCode());
    }

    try {
        $employees = array($contacts->searchByNNI($nni));
        if (isset($_GET['nni'])) {
            echo json_encode($employees, JSON_PRETTY_PRINT);
        }
        $contacts->formatAsArray($employees);
        displayTable($employees);
        echo '<hr>';
    } catch (Exception $e) {
        throw new CoreException($e->getMessage(), $e->getCode());
    }

    if (!isset($_GET['nni'])) {
        echo "<h1>BEFORE selectDR()</h1>";
        doTests($contacts, $nni);

        echo "<h1>AFTER selectDR()</h1>";

        try {
            $contacts->selectDR('DR BRETAGNE');
        } catch (Exception $e) {
            throw new CoreException($e->getMessage(), $e->getCode());
        }
        doTests($contacts, $nni);
    }
} catch (Exception $e) {

    error_log($e->getMessage());
    error_log($e->getTraceAsString());

    echo $e->getMessage();
    echo $e->getTraceAsString();
}
function doTests($contacts, $nni)
{
    try {
        // search(first_name)
        echo "<h2>search('nadege')->sortByName()</h2>";
        $employees = $contacts->search('nadege');
        $contacts->formatAsArray($employees);
        $contacts->sortByName($employees);
        displayTable($employees);
        echo '<hr> ';
    } catch (Exception $e) {
        throw new CoreException($e->getMessage(), $e->getCode());
    }

    try {
        // search(last_name)
        echo "<h2>search('le roux')->sortByName()</h2>";
        $employees = $contacts->search('le roux');
        $contacts->formatAsArray($employees);
        $contacts->sortByName($employees);
        displayTable($employees);
        echo '<hr>   ';
    } catch (Exception $e) {
        throw new CoreException($e->getMessage(), $e->getCode());
    }

    try {
        // search(nni)
        echo "<h2>search('" . htmlspecialchars($nni) . "')</h2>";
        $employees = $contacts->search($nni);
        $contacts->formatAsArray($employees);
        displayTable($employees);
        echo '<hr>  ';
    } catch (Exception $e) {
        throw new CoreException($e->getMessage(), $e->getCode());
    }
}
