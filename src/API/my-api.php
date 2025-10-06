<?php

/**
 * Construisez ici vos API.
 *
 * Il ne faut pas placer de die() dans ce fichier, sous peine d'annuler la gestion d'erreur, etc.
 *
 * $globalResult n'est plus utilisé.
 * Toute requête qui produit une erreur part dans le catch(Exception) et provoque un mysqli_rollback().
 * La syntaxe :
 *     $result = mysqli_query($query);
 *     $globalResult = $globalResult && $result;
 * peut être remplacée par :
 *     mysqli_query($query);
 * ou mieux encore :
 *     CoreMysqli::get()->query($query);
 *
 * $debug est à false sur le serveur de production, sinon à true.
 *
 * Le préfixe de table est $_tablesPrefix, merci de l'utiliser même s'il est vide, avec la syntaxe suivante :
 *     $query = "SELECT * FROM `{$_tablesPrefix}horaires` WHERE `jour` = CURDATE()";
 *
 * Une API qui renvoit un JSON doit simplement le placer dans $JSONoutput.
 * Par exemple :
 *     $JSONoutput = json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC), JSON_NUMERIC_CHECK);
 *     break;
 * Autre exemple, si la requête n'a pas besoin d'être préparée :
 *     $JSONoutput = json_encode(CoreMysqli::get()->query($query)->fetch_all(MYSQLI_ASSOC), JSON_NUMERIC_CHECK);
 *     break;
 *
 * Une API qui retourne des DCP ou des ICS *DOIT* utiliser la fonction abortIfNotGranted(String[] rights),
 * qui permet d'interdire l'accès à une API si l'utilisateur connecté ne dispose pas
 * d'un des droits mentionnés => erreur 403
 * Le front-end traite automatiquement l'erreur et affiche un message.
 *
 * Pour forcer l'affichage d'un Toastr côté frontEnd, il suffit d'utiliser les fonctions
 * displaySuccessInFront($title, $message) ou displayAlertInFront($title, $message)
 * Par exemple :
 *     displaySuccessInFront('Pour votre information', 'Votre compétance arrive à échéance bientôt.');
 *     $JSONoutput = json_encode($tempArray);
 *     break;
 *
 * S'il est nécessaire d'utiliser un second fichier d'API, il faut importer le fichier depuis celui-ci (require...),
 * et renseigner ici la variable $_nb_api_files = 2 (ou plus : elle vaut 1 par défaut).
 */

use EnedisLabBZH\Core\CoreMysqli;
use EnedisLabBZH\Core\CoreException;
use EnedisLabBZH\Core\Apigile\ApiMessage;
use EnedisLabBZH\Core\CoreParameters;
use EnedisLabBZH\Core\GitlabApi;
use EnedisLabBZH\Core\GitlabApi\GitlabProject;
// This is to demonstrate app classes
use EnedisLabBZH\MyDemoApp\Test;

switch ($subject) {

    case 'setText':
        $value = @$parameters['value'];
        if ('' == $value) {
            displayAlertInFront('Erreur', 'La valeur ne peut pas être vide !');

            throw new CoreException('La valeur ne peut pas être vide !');
        }
        $test = new Test();
        $test->setText($value);
        displaySuccessInFront('Succès', 'La valeur a été enregistrée.');

        break;

    case 'getText':
        $test = new Test();
        $value = $test->getText();
        $JSONoutput = json_encode(["value" => $value]);

        break;

        /**
         * Le case 'rgdp' est obligatoire !
         * A adpter selon votre application.
         */
    case 'rgpd':
        $tempArray['content'] = "
        Conformément à la loi n°78-17 du 6 janvier 1978 modifiée et au règlement (UE) n°2016/679 du 27 avril 2016,
        les informations recueillies sont enregistrées dans un fichier informatisé
        par ENEDIS - DR Bretagne en sa qualité de responsable de traitement
        dans le cadre ...[A adapter en fonction des cas :
                            - dans le cadre de l’exécution du contrat … »
                            ou « dans le cadre de la loi … »
                            ou « dans le cadre de la mission de service public »
                            ou dans le cadre « du recueil de vote consentement » etc.]
        pour ... [préciser la finalité du traitement, le but de la collecte des DCP].
        Elles sont conservées pendant... [Préciser la durée de conservation des données]
        et sont destinées à [indiquer la liste des destinataires interne ENEDIS + externes
        (éventuels prestataires) auxquels les DCP sont transmises].
        Vous disposez d’un droit d'accès à vos données, de rectification, d’opposition
        et d’effacement pour motifs légitimes.
        Vous disposez, également, d’un droit à la limitation du traitement
        et à la portabilité des données à caractère personnel vous concernant.
        Vous pouvez exercer vos droits par courrier à l’adresse suivante :
        ENEDIS - DR Bretagne - Equipe numérique - 62 boulevard Voltaire - 35000 RENNES.
        Votre courrier doit préciser votre nom et prénom ainsi que votre NNI.
        Conformément à la loi « informatique et libertés », vous disposez
        de la faculté d’introduire une réclamation auprès de la CNIL.";
        $JSONoutput = json_encode($tempArray);

        break;

        /**
         * Les cases 'dummyRead', 'dummyUpdate' et 'dummyInsert' sont des exemples que vous pouvez effacer après vous en être inspiré.
         */
    case 'dummyRead':
        abortIfNotGranted(array('HABILITATIONS', 'ROLES'));
        if (!array_key_exists('input', $parameters)) {
            throw new CoreException('$_GET[input] not found!', LEVEL_ERROR);
        }
        $query = "
        SELECT
            `table1`.`field1`,
            `table2`.`field2`
        FROM
            `{$_tablesPrefix}table1` `table1`
            LEFT JOIN `{$_tablesPrefix}table2` table2` ON `table2`.`table1field` = `table1`.`id`
        WHERE
            `field` LIKE '%?%'
        ";
        $stmt = CoreMysqli::get()->prepare($query);
        $stmt->bind_param('s', $parameters['input']);
        $stmt->execute();
        $JSONoutput = json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC), JSON_NUMERIC_CHECK);

        break;

    case 'dummyUpdate':
        abortIfNotGranted('MANAGER');
        $participation = $parameters['participation'];
        $query = "
        UPDATE
            `{$_tablesPrefix}participations` `participations`
        SET
            `participations`.`dateheure_ajout` = NOW(),
            `participations`.`role` = ?,
            `participations`.`dateRDV` = ?,
            `participations`.`montant` = ?
        FROM
            `participations`
        WHERE
            `id` LIKE CONCAT('%', ?, '%')
        ";
        $stmt = CoreMysqli::get()->prepare($query);
        $stmt->bind_param(
            'ssis',
            $participation['role'],
            dateUS($participation['dateRDV']), // dateUS() se trouve dans core/core_functions.php
            $participation['montant'],
            $parameters['id']
        );
        $stmt->execute();
        displaySuccessInFront('MERCI !', 'Votre participation a été prise en compte.');

        break;

    case 'dummyInsert':
        abortIfNotGranted('MANAGER');
        $participation = $parameters['participation'];
        $query = "
        INSERT INTO
            `{$_tablesPrefix}participations`
        (
            `dateheure_ajout`,
            `role`,
            `dateRDV`,
            `montant`
        ) VALUES (
            NOW(),
            ?,
            ?,
            ?
        )
        ON DUPLICATE KEY UPDATE
            `dateheure_ajout` = NOW(),
            `role` = ?,
            `dateRDV` = ?,
            `montant` = ?
        ";
        $stmt = CoreMysqli::get()->prepare($query);
        $stmt->bind_param(
            'ssis',
            $participation['role'],
            dateUS($participation['dateRDV']), // dateUS() se trouve dans core/core_functions.php
            $participation['montant'],
            $participation['role'],
            dateUS($participation['dateRDV']),
            $participation['montant']
        );
        $stmt->execute();
        displaySuccessInFront('MERCI !', 'Votre participation a été prise en compte.');

        break;

    case 'dummySendEmail':
        abortIfNotGranted('MANAGER');
        if (isRuningOnDevServer()) {
            break;
        }
        $repicient = $parameters['repicient'];
        $subject = $parameters['subject'];
        $HTMLbody = '' . @$parameters['HTMLbody'];
        $body = '' . @$parameters['body'];
        $apiMessage = new ApiMessage();
        $apiMessage->sendEmail(
            'Enedis_Prev_Travx_BI',
            ['prenom.nom@enedis.fr'],
            'Test objet',
            'Test message'
        );

        break;

    case 'dummyAdageApi':
        // Warning : you need a DMF to open the connexion from Place v2
        $results = [];

        $appParameters = new CoreParameters();
        $gitlab_source_api_url = $appParameters->getParameter('gitlab_source_api_url');
        $gitlab_source_token = $appParameters->getParameter('gitlab_source_token');

        $gh = new GitlabApi();
        $gh->setToken($gitlab_source_token);
        $gh->setApiUrl($gitlab_source_api_url);

        $gitlab_source_group_id = $appParameters->getParameter('gitlab_source_group_id');
        $gitlab_source_project_id = $appParameters->getParameter('gitlab_source_project_id');

        $group = $gh->getGroup($gitlab_source_group_id);
        $myDR = 'DR Bretagne';
        $res[$myDR]['properties'] = $group->getProperties();
        $res[$myDR]['issues'] = $group->getIssues();
        $res[$myDR]['projects'] = $group->getProjects();
        $res[$myDR]['members'] = $group->getMembers();

        $project = $gh->getProject($gitlab_source_project_id);
        $projectProperties = $project->getProperties();
        $projectName = $projectProperties['name'];
        $res[$projectName]['properties'] = $project->getProperties();
        $res[$projectName]['issues'] = $project->getIssues();
        $res[$projectName]['commits'] = $project->getCommits();
        $res[$projectName]['users'] = $project->getUsers();

        $JSONoutput = json_encode($res);

        break;

    case 'dummyZacApi':
        $results = [];

        $appParameters = new CoreParameters();
        $gitlab_zac_api_url = $appParameters->getParameter('gitlab_zac_api_url');
        $gitlab_zac_token = $appParameters->getParameter('gitlab_zac_token');

        $gh = new GitlabApi();
        $gh->setToken($gitlab_zac_token);
        $gh->setApiUrl($gitlab_zac_api_url);

        $gitlab_zac_project_id = $appParameters->getParameter('gitlab_zac_project_id');
        $project = new GitlabProject($gh, $gitlab_zac_project_id);

        $envs = $project->getEnvironments();
        foreach ($envs as $env) {
            $env['name'] === getenv('ENVIRONMENT')
                && $id = $env['id'];
        }
        !$id && die('no id found');

        $environment = $project->getEnvironment($id);
        $dateTime = new DateTime($environment['last_deployment']['created_at']);
        $dockerCreationDate = $dateTime->format('d/m/Y à H:i:s');

        break;

    default:
        // Laisser cette ligne pour la gestion des erreurs.
        $has_not_match_a_subject++;

        break;
}
