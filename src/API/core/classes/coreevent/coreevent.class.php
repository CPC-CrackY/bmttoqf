<?php

namespace EnedisLabBZH\Core;

class CoreEvent
{
    private $table;

    private $lastEventId;
    private $data;
    private $type;
    private $timeStamp;
    private $db;
    private $tablesPrefix;

    /**
     * CoreEvent insert events in memory table
     */
    public function __construct($db, $_tablesPrefix = null)
    {
        $this->db = $db;
        $this->tablesPrefix = $_tablesPrefix;
        !$this->tablesPrefix
            && (array_key_exists('_tablesPrefix', $GLOBALS))
            && $this->tablesPrefix = $GLOBALS['_tablesPrefix'];
        (array_key_exists('_sessionId', $GLOBALS))
            && $this->lastEventId = $GLOBALS['_sessionId'];
    }

    /**
     * storeEvent store an event in `{$_tablesPrefix}_core_event` table
     * $event is an array [lastEventId:string, data:application/json, type: string]
     * The "data" string is 21740 long max because of memory table limitations.
     *
     * @param  array $event
     * @return void
     */
    public function storeEvent($event)
    {
        // event creation. TimeStamp is calculated.
        $this->timeStamp = $this->getIsoDateWithMilliseconds();

        $this->data = '';
        $this->type = '';
        $event && $event['data'] && $this->data = $event['data'];
        $event && $event['type'] && $this->type = $event['type'];

        $this->tablesPrefix
            && $this->table = $this->tablesPrefix . 'core_events';
        if (!$this->table) {
            throw new CoreException('Missing $_tablesPrefix');
        }


        // Table creation (in memory)
        $query =
            "CREATE TABLE IF NOT EXISTS `{$this->table}` (
          `lastEventId` varchar(40) DEFAULT NULL,
          `type` varchar(40) DEFAULT NULL,
        
          `data` varchar( 21340 ) DEFAULT NULL,
        
          `timeStamp` char(24) DEFAULT NULL,
          KEY `timeStampIndex` (`timeStamp`)
        ) ENGINE=MEMORY DEFAULT CHARSET=utf8 COMMENT='Table de stockage des Server Side Events';
        ";
        $this->db->query($query);

        // event insertion (in memory)
        $query = "INSERT INTO `{$this->table}` (`lastEventId`,`type`,`data`,`timeStamp`) VALUES (?, ?, ?, ?);";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ssss', $this->lastEventId, $this->type, $this->data, $this->timeStamp);
        $stmt->execute();
        $stmt->close();

        return $this->timeStamp;
    }

    /**
     * getIsoDateWithMilliseconds return Javascript-like ISO date
     *
     * @return string Iso date with milliseconds.
     */
    private function getIsoDateWithMilliseconds()
    {
        $microtime = microtime(true);
        $seconds = floor($microtime);
        $milliseconds = round(($microtime - $seconds) * 1000);
        // Utiliser str_pad pour ajouter des zéros au début si nécessaire
        $millisecondsPadded = str_pad((string)$milliseconds, 3, '0', STR_PAD_LEFT);

        $date = new \DateTime("@$seconds");

        // Définir le fuseau horaire sur UTC
        $date->setTimezone(new \DateTimeZone('UTC'));

        return $date->format('Y-m-d\TH:i:s') . '.' . $millisecondsPadded . 'Z';
    }
}
