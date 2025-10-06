<?php

namespace EnedisLabBZH\Core;

// Maps "databridge_" core params with PDO params
define('PARAM_KEYS', [
    'databridge_port' => 'Port',
    'databridge_database' => 'Database',
    'databridge_user' => 'Uid',
    'databridge_password' => 'Pwd'
]);

/**
 * Étend la classe PDO pour une connection vers Databridge.
 *
 * PRÉREQUIS :
 * 1. Uniquement sur PLACE-CLOUD
 * 2. Serveur en "zone raccordée"
 * 3. Demande d'accès aux données validée par le GTAR (formulaire Excel)
 * 4. Avoir un compte Databridge et les vues nécessaires pour accéder aux données
 * 5. Extensions PHP "pdo_odbc" et "odbc" (place-cloud.conf)
 * 6. Avoir créé les coreParameters suivants :
 * - databridge_port
 * - databridge_database
 * - databridge_user
 * - databridge_password
 */
final class CoreDatabridgePDO extends CorePDO
{
    private static ?CoreDatabridgePDO $instance = null;

    public static function &init(string $tablesPrefix = null): CoreDatabridgePDO
    {
        if (is_null(self::$instance)) {
            self::$instance = new CoreDatabridgePDO($tablesPrefix);
        }

        return self::$instance;
    }

    public static function &getInstance(): CoreDatabridgePDO
    {
        return self::$instance;
    }

    private function __construct($tablesPrefix = null)
    {
        $coreParametersHandler = new CoreParameters($tablesPrefix);
        $coreParams = $coreParametersHandler->getParameters();

        $databridgeParams = [
            'Driver' => 'DenodoODBCDriver',
            'Server' => getenv('TO_DC_ENDPOINT'),
            "sslmode" => "required",
            array(
                CorePDO::ATTR_TIMEOUT => 5, // in seconds
                CorePDO::ATTR_ERRMODE => CorePDO::ERRMODE_EXCEPTION
            )
        ];

        foreach ($coreParams as $coreParam) {
            if (array_key_exists($coreParam['name'], PARAM_KEYS)) {
                $pdoKey = PARAM_KEYS[$coreParam['name']];
                $databridgeParams[$pdoKey] = $coreParam['value'];
            }
        }

        parent::__construct($databridgeParams);
    }
}
