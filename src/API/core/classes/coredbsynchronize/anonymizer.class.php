<?php

declare(strict_types=1);

namespace EnedisLabBZH\Core\CoreDbSynchronize;

use EnedisLabBZH\Core\CoreException;
use EnedisLabBZH\Core\CoreMysqli;

class Anonymizer
{
    private array $dictionaryCache = [];

    private function getDictionaryValues(string $type): array
    {
        if (!isset($this->dictionaryCache[$type])) {
            $table = null;
            !defined('TABLE_PREFIX')
                && define('TABLE_PREFIX', '_tablesPrefix');
            (array_key_exists(TABLE_PREFIX, $GLOBALS))
                && $GLOBALS[TABLE_PREFIX] !==  null
                && $table = $GLOBALS[TABLE_PREFIX] . 'core_substitutions';
            if (!$table) {
                throw new CoreException('Missing $_tablesPrefix');
            }
            $mysqli = CoreMysqli::get();
            $query = "SELECT `value` FROM `{$table}` WHERE `type` = ?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param("s", $type);
            $stmt->execute();
            $result = $stmt->get_result();
            $values = [];
            while ($row = $result->fetch_assoc()) {
                $values[] = $row['value'];
            }
            $this->dictionaryCache[$type] = $values;
        }

        return $this->dictionaryCache[$type];
    }

    /**
     * hashIdentifier hash the identifier : it will be different for each iteration.
     * Don't use this function if you need to keep relations between tables. See shuffleIdentifier() instead.
     *
     * @param  mixed $identifier
     * @param  mixed $config
     * @return string
     */
    public function hashIdentifier(string $identifier, $config): string
    {
        return substr(hash('sha256', $identifier), 0, $config->length);
    }

    /**
     * shuffleIdentifier scramble the identifier.
     * Use this function if you need to keep relations between tables.
     *
     * @param  mixed $identifier is the identifier
     * @return string the scrambled identifier
     */
    public function shuffleIdentifier(string $identifier): string
    {
        // Tableau des 26 lettres de l'alphabet en majuscule dans l'ordre et dans le désordre
        $upercases = [
            'A' => 'Q',
            'B' => 'L',
            'C' => 'P',
            'D' => 'A',
            'E' => 'U',
            'F' => 'T',
            'G' => 'M',
            'H' => 'Z',
            'I' => 'E',
            'J' => 'O',
            'K' => 'Y',
            'L' => 'H',
            'M' => 'C',
            'N' => 'K',
            'O' => 'X',
            'P' => 'W',
            'Q' => 'G',
            'R' => 'I',
            'S' => 'B',
            'T' => 'F',
            'U' => 'J',
            'V' => 'S',
            'W' => 'R',
            'X' => 'D',
            'Y' => 'N',
            'Z' => 'V'
        ];

        // Tableau des 26 lettres de l'alphabet en minuscule dans l'ordre et dans le désordre
        $lowercases = [
            'a' => 'x',
            'b' => 'e',
            'c' => 'b',
            'd' => 'l',
            'e' => 'u',
            'f' => 't',
            'g' => 's',
            'h' => 'f',
            'i' => 'j',
            'j' => 'a',
            'k' => 'y',
            'l' => 'o',
            'm' => 'p',
            'n' => 'r',
            'o' => 'z',
            'p' => 'm',
            'q' => 'k',
            'r' => 'v',
            's' => 'h',
            't' => 'c',
            'u' => 'n',
            'v' => 'i',
            'w' => 'w',
            'x' => 'q',
            'y' => 'g',
            'z' => 'd'
        ];

        // Tableau des 10 chiffres dans l'ordre et dans le désordre
        $numbers = [
            '0' => '7',
            '1' => '2',
            '2' => '9',
            '3' => '0',
            '4' => '5',
            '5' => '3',
            '6' => '8',
            '7' => '4',
            '8' => '1',
            '9' => '6'
        ];

        return strtr(
            $identifier,
            array_merge($upercases, $lowercases, $numbers)
        );
    }

    public function substituteFromDictionary(string $originalName, $config): string
    {
        $dictionaryValues = $this->getDictionaryValues($config->type);

        return $dictionaryValues[array_rand($dictionaryValues)];
    }

    public function generalizeAddress(string $originalAddress, $config): string
    {
        $dictionaryValues = $this->getDictionaryValues($config->type ?? 'address');

        return $dictionaryValues[array_rand($dictionaryValues)];
    }

    public function maskPhoneNumber(string $originalPhone): string
    {
        if (strpos($originalPhone, '+33') === 0) {
            return '+33' . str_repeat('x', strlen($originalPhone) - 3);
        } else {
            return str_repeat('x', strlen($originalPhone));
        }
    }

    public function fuzzyDate(string $originalDate, $config): string
    {
        $date = new \DateTime($originalDate);
        $fuzzFactor = random_int(- ($config->range ?? 30), ($config->range ?? 30));
        $date->modify("$fuzzFactor days");

        return $date->format('Y-m-d');
    }

    public function maskEmail(string $originalEmail): string
    {
        return 'john.doe@test-enedis.fr';
    }

    public function emptyValue(string $originalEmail): string
    {
        return '';
    }
}
