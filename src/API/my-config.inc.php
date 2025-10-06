<?php

/**
 * Sous Place v1 (Azur), depuis la version 4 du squelette,
 * la configuration SQL se trouve dans un fichier /home/{SERVER_NAME}/.env
 * Idem pour la configuration WebSSO.
 * Sous Place v2, la configuration SQL est contenue dans des variables d'environnement
 * et la config WebSSO est gérée par l'infra.
 *
 * Dans ce fichier-ci, on trouvera la variable $_tablePrefix, la matrice des droits,
 * et éventuellement $gardianUserSearchLdapDomains
 * Cette variable $gardianUserSearchLdapDomains pourra être effacée pour les applications mono-DR.
 */

$_tablesPrefix = 'base_dev_squelette_';

if (getenv('BASE_URL') !== false) {
    $logUsersListUrl = false;
    $lastVersionsUrl = false;
}

$gardianUserSearchLdapDomains = array(
    // '1395M', // DR ALPES
    // '1364M', // DR ALS F COMTE
    // '1449M', // DR AQN
    // '1497M', // DR AUVERGNE
    // '1397M', // DR BOURGOGNE
    '1464M', // DR BRETAGNE
    // '1494M', // DR CENTRE-VDL
    // '1366M', // DR CHAMP ARDEN
    // '1429M', // DR COTE D AZUR
    // '1306M', // DR IDF EST
    // '1308M', // DR IDF OUEST
    // '1424M', // DR LARO
    // '1496M', // DR LIMOUSIN
    // '1365M', // DR LORRAINE
    // '1445M', // DR MPS
    // '1450M', // DR NMP
    // '1336M', // DR NORMANDIE
    // '1334M', // DR NPDC
    // '1307M', // DR PARIS
    // '1465M', // DR PAYS LOIRE
    // '1335M', // DR PICARDIE
    // '1466M', // DR POIT-CHARENT
    // '1425M', // DR PROV ALP SUD
    // '1444M', // DR PYL
    // '1396M', // DR SILL RHODAN
);

/**
 * Matrice des droits associés aux profils de l'application
 * Les droits doivent commencer par "{INS de l'application}_", par exemple 73Y_DEVELOPPEUR
 */

// Les autorisations
// $visitorGrants = array('LECTURE');
$grants['XXX_METIER'] = array('ROLES', 'METIER');
$grants['XXX_DEVELOPER'] = array('HABILITATIONS', 'ROLES', 'IMPORTS', 'PARAMETRES');

// Les pages de démarrage
// $visitorBaseUrl = '/aide';
$baseUrl['XXX_METIER'] = '/metier';
$baseUrl['XXX_DEVELOPER'] = '/admin';
