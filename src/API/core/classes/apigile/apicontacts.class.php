<?php

namespace EnedisLabBZH\Core\Apigile;

use EnedisLabBZH\Core\Apigile;
use EnedisLabBZH\Core\CoreException;
use EnedisLabBZH\Core\CoreMysqli;
use EnedisLabBZH\Core\CoreParameters;

final class ApiContacts
{
    private Apigile $apigile;
    private string $dr = '';
    private const CACHE_TABLE = 'core_cache_contacts';
    private const MAX_CACHE_DAYS = 6;

    public function __construct(string $clientId = null, string $clientSecret = null, string $endpointApi = null, bool $rawErrors = false)
    {
        $this->apigile = new Apigile($clientId, $clientSecret, $endpointApi, $rawErrors);
    }

    public function selectDR(string $dr): void
    {
        $this->dr = $dr;
    }

    public function getAllEmployees($maxResults = 5000): array
    {
        return $this->requestContactsAPI('', $maxResults);
    }

    public function searchByFirstname(string $firstName, int $maxResults = 100): array
    {
        return $this->requestContactsAPI('first_name=' . urlencode($firstName), $maxResults);
    }

    public function searchByLastname(string $lastName, int $maxResults = 50): array
    {
        return $this->requestContactsAPI('name=' . urlencode($lastName), $maxResults);
    }

    public function searchWithQuery(string $query, int $maxResults = 100): array
    {
        return $this->requestContactsAPI($query, $maxResults);
    }

    public function searchByNNI(string $nni)
    {
        $table = $this->getCacheTableName();
        // Vérification de l'existence de la table et création si nécessaire
        if (!CoreMysqli::get()->tableExists($table)) {
            $this->createCacheTable($table);
        }
        $employee = $this->getEmployeeFromCache($nni, $table);
        if (null === $employee || $this->shouldUpdateCache($employee->last_updated ?? null)) {
            $employee = $this->fetchAndUpdateCache($nni, $table);
        }

        return $employee;
    }

    public function search(string $search): array
    {
        if (preg_match('~\d+~', $search) && (strlen($search) === 6 || strlen($search) === 8)) {
            $employee = $this->searchByNNI(strtoupper($search));

            return $employee !== null ? [$employee] : [];
        }

        if (!preg_match('~\d+~', $search) && (strlen($search) >= 3)) {
            return array_merge(
                $this->searchByFirstname($search),
                $this->searchByLastname($search)
            );
        }

        return [];
    }

    private function requestContactsAPI($query = '', $maxResults = 500): array
    {
        try {
            $completeQuery = 'sort=contacts.identity.name';
            $completeQuery .= $this->dr ? '&organization=' . strtoupper(rawurlencode($this->dr)) : '';
            $completeQuery .= $query ? '&' . $query : '';
            $ret = array();
            $url = '/contact_data/v3/search';
            $coreParameters = new CoreParameters();
            if ($coreParameters->parameterExists('contacts_search_endpoint')) {
                $url = $coreParameters->getParameter('contacts_search_endpoint');
            }
            $ret = $this->apigile->paginatedQuery(
                $url,
                $completeQuery,
                $maxResults,
                100,
                function ($data) {
                    return $data->contacts ?? [];
                }
            );
            !isset($ret) && !is_array($ret) && $ret = [];

            return $ret;
        } catch (CoreException $e) {
            $this->throwException($e);
        }
    }

    private function throwException($e): void
    {
        $code = $e->getCode();
        $message = $e->getMessage();

        if ($this->apigile->getRawErrors()) {
            throw new CoreException("HTTP $code : $message", $code);
        }

        $frenchMessage = match ($code) {
            400 => "Erreur dans le format de la requête.",
            404 => "Pas de ressource correspondant à la requête.",
            500 => "Erreur serveur.",
            default => "",
        };

        throw new CoreException("$frenchMessage\n$message", $code);
    }

    public function formatAsArray(array &$employees): void
    {
        $employees = array_map([$this, 'employeeToArray'], $employees);
    }

    public function sortByName(&$employees): void
    {
        if (is_array($employees)) {
            uasort($employees, [$this, 'sortByLastnameAndFirstname']);
        }
    }

    private function getCacheTableName(): string
    {
        $prefix = null;

        !defined('TABLE_PREFIX')
            && define('TABLE_PREFIX', '_tablesPrefix');

        (array_key_exists(TABLE_PREFIX, $GLOBALS))
            && $GLOBALS[TABLE_PREFIX] !==  null
            && $prefix = $GLOBALS[TABLE_PREFIX];

        if (null === $prefix) {
            throw new CoreException('Missing $_tablesPrefix');
        }

        return $prefix . self::CACHE_TABLE;
    }

    private function createCacheTable(string $table): void
    {
        CoreMysqli::get()->query(
            "CREATE TABLE `{$table}` (
            `contact_id` VARCHAR(8) NOT NULL,
            `name` text NOT NULL,
            `first_name` text NOT NULL,
            `manager` text NOT NULL,
            `assistant` text NOT NULL,
            `activity` text NOT NULL,
            `business` text NOT NULL,
            `referent` text NOT NULL,
            `employer` text NOT NULL,
            `structure_name_6` text NOT NULL,
            `structure_name_7` text NOT NULL,
            `structure_name_8` text NOT NULL,
            `structure_name_9` text NOT NULL,
            `code_uo` text NOT NULL,
            `code_um` text NOT NULL,
            `sitename` text NOT NULL,
            `street` text NOT NULL,
            `locality` text NOT NULL,
            `zip_code` text NOT NULL,
            `building` text NOT NULL,
            `floor` text NOT NULL,
            `room` text NOT NULL,
            `email` text NOT NULL,
            `phone` text NOT NULL,
            `mobile` text NOT NULL,
            `last_updated` date NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );

        CoreMysqli::get()->query("ALTER TABLE `{$table}` ADD PRIMARY KEY(`contact_id`);");
    }

    private function getEmployeeFromCache(string $nni, string $table): object|null
    {
        $query = "SELECT * FROM `{$table}` WHERE `contact_id` = ?";
        $stmt = CoreMysqli::get()->prepare($query);
        $stmt->bind_param('s', $nni);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row) {
            $employee = new \stdClass();
            $employee->identity = new \stdClass();
            $employee->organization = new \stdClass();
            $employee->organization->structure = new \stdClass();
            $employee->organization->structure->entry = new \stdClass();
            $employee->organization->structure->entry->entry = new \stdClass();
            $employee->organization->structure->entry->entry->entry = new \stdClass();
            $employee->organization->structure->entry->entry->entry->entry = new \stdClass();
            $employee->organization->structure->entry->entry->entry->entry->entry = new \stdClass();
            $employee->organization->structure->entry->entry->entry->entry->entry->entry = new \stdClass();
            $employee->organization->structure->entry->entry->entry->entry->entry->entry->entry = new \stdClass();
            $employee->organization->structure->entry->entry->entry->entry->entry->entry->entry->entry = new \stdClass();
            $employee->organization->structure->entry->entry->entry->entry->entry->entry->entry->entry->entry = new \stdClass();
            $employee->localization = new \stdClass();
            $employee->localization->address = new \stdClass();
            $employee->contact_point = new \stdClass();

            $employee->identity->contact_id = $row['contact_id'];
            $employee->identity->name = $row['name'];
            $employee->identity->first_name = $row['first_name'];
            $employee->organization->manager = $row['manager'];
            $employee->organization->assistant = $row['assistant'];
            $employee->organization->activity = $row['activity'];
            $employee->organization->business = $row['business'];
            $employee->organization->referent = $row['referent'];
            $employee->organization->employer = $row['employer'];
            $employee->organization->structure->entry->entry->entry->entry->entry->entry->structure_name_6 = $row['structure_name_6'] ?? null;
            $employee->organization->structure->entry->entry->entry->entry->entry->entry->entry->structure_name_7 = $row['structure_name_7'] ?? null;
            $employee->organization->structure->entry->entry->entry->entry->entry->entry->entry->entry->structure_name_8 = $row['structure_name_8'] ?? null;
            $employee->organization->structure->entry->entry->entry->entry->entry->entry->entry->entry->entry->structure_name_9 = $row['structure_name_9'] ?? null;
            $employee->organization->code_uo = $row['code_uo'];
            $employee->organization->code_um = $row['code_um'];
            $employee->localization->address->sitename = $row['sitename'];
            $employee->localization->address->street = $row['street'];
            $employee->localization->address->locality = $row['locality'];
            $employee->localization->address->zip_code = $row['zip_code'];
            $employee->localization->building = $row['building'];
            $employee->localization->floor = $row['floor'];
            $employee->localization->room = $row['room'];
            $employee->contact_point->email = $row['email'];
            $employee->contact_point->phone = $row['phone'];
            $employee->contact_point->mobile = $row['mobile'];
            $employee->last_updated = $row['last_updated'];

            return $employee;
        }

        return null;
    }

    private function shouldUpdateCache(string|null $lastUpdated): bool
    {
        if ($lastUpdated === null) {
            return true;
        }
        $dateToCompare = new \DateTime($lastUpdated);
        $now = new \DateTime();

        return $dateToCompare->diff($now)->days > self::MAX_CACHE_DAYS;
    }

    private function fetchAndUpdateCache(string $nni, string $table)
    {
        $url = '/contact_data/v3/';
        $coreParameters = new CoreParameters();
        if ($coreParameters->parameterExists('contacts_nni_search_endpoint')) {
            $url = $coreParameters->getParameter('contacts_nni_search_endpoint');
        }
        $url .= $nni;
        $result = $this->apigile->query($url);
        if (isset($result->contacts[0])) {
            $employee = $result->contacts[0];
            $this->updateCacheTable($employee, $table);

            return $employee;
        }

        return null;
    }

    private function employeeToArray(object $employee): array
    {
        $structure5 = $employee->organization->structure->entry->entry->entry->entry->entry->entry ?? new \stdClass();

        return [
            "nni" => $employee->identity->contact_id ?? '',
            "last_name" => $employee->identity->name ?? '',
            "first_name" => $employee->identity->first_name ?? '',
            "manager" => $employee->organization->manager ?? '',
            "assistant" => $employee->organization->assistant ?? '',
            "activity" => $employee->organization->activity ?? '',
            "business" => $employee->organization->business ?? '',
            "referent" => $employee->organization->referent ?? '',
            "employer" => $employee->organization->employer ?? '',
            "um_label" => $structure5->structure_name_6 ?? '',
            "dum_label" => $structure5->entry->structure_name_7 ?? '',
            "sdum_label" => $structure5->entry->entry->structure_name_8 ?? '',
            "fsdum_label" => $structure5->entry->entry->entry->structure_name_9 ?? '',
            "fsdum" => $employee->organization->code_uo ?? '',
            "code_uo" => $employee->organization->code_uo ?? '',
            "code_um" => $employee->organization->code_um ?? '',
            "sitename" => $employee->localization->address->sitename ?? '',
            "address" => $employee->localization->address->street ?? '',
            "city" => $employee->localization->address->locality ?? '',
            "zip_code" => $employee->localization->address->zip_code ?? '',
            "building" => $employee->localization->building ?? '',
            "floor" => $employee->localization->floor ?? '',
            "room" => $employee->localization->room ?? '',
            "email" => $employee->contact_point->email ?? '',
            "phone" => $employee->contact_point->phone ?? '',
            "mobile" => $employee->contact_point->mobile ?? '',
        ];
    }

    private function updateCacheTable(object $employee, string $table): void
    {
        $stmt = CoreMysqli::get()->prepare("DELETE FROM `$table` WHERE `contact_id` = ?");
        $stmt->bind_param('s', $employee->identity->contact_id);
        $stmt->execute();

        $query = "INSERT INTO `$table` (
        `contact_id`, `name`, `first_name`, `manager`, `assistant`, `activity`,
        `business`, `referent`, `employer`, `structure_name_6`, `structure_name_7`,
        `structure_name_8`, `structure_name_9`, `code_uo`, `code_um`, `sitename`,
        `street`, `locality`, `zip_code`, `building`, `floor`, `room`, `email`,
        `phone`, `mobile`, `last_updated`
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())";

        $stmt = CoreMysqli::get()->prepare($query);
        $structure_5 = @$employee->organization->structure->entry->entry->entry->entry->entry->entry;

        $contactId = $employee->identity->contact_id;
        $name = $employee->identity->name;
        $firstName = $employee->identity->first_name;
        $manager = $employee->organization->manager;
        $assistant = $employee->organization->assistant;
        $activity = $employee->organization->activity;
        $business = $employee->organization->business;
        $referent = $employee->organization->referent;
        $employer = $employee->organization->employer;
        $structureName_6 = $structure_5->structure_name_6 ?? '';
        $structureName_7 = $structure_5->entry->structure_name_7 ?? '';
        $structureName_8 = $structure_5->entry->entry->structure_name_8 ?? '';
        $structureName_9 = $structure_5->entry->entry->entry->structure_name_9 ?? '';
        $codeUo = $employee->organization->code_uo;
        $codeUm = $employee->organization->code_um;
        $sitename = $employee->localization->address->sitename;
        $street = $employee->localization->address->street;
        $locality = $employee->localization->address->locality;
        $zipCode = $employee->localization->address->zip_code;
        $building = $employee->localization->building;
        $floor = $employee->localization->floor;
        $room = $employee->localization->room;
        $email = $employee->contact_point->email;
        $phone = $employee->contact_point->phone;
        $mobile = $employee->contact_point->mobile;

        $stmt->bind_param(
            'sssssssssssssssssssssssss',
            $contactId,
            $name,
            $firstName,
            $manager,
            $assistant,
            $activity,
            $business,
            $referent,
            $employer,
            $structureName_6,
            $structureName_7,
            $structureName_8,
            $structureName_9,
            $codeUo,
            $codeUm,
            $sitename,
            $street,
            $locality,
            $zipCode,
            $building,
            $floor,
            $room,
            $email,
            $phone,
            $mobile
        );

        $stmt->execute();
    }

    private function sortByLastnameAndFirstname(array $a, array $b): int
    {
        return $a['last_name'] <=> $b['last_name'] ?: $a['first_name'] <=> $b['first_name'];
    }
}
