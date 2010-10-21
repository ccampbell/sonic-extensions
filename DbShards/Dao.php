<?php
namespace Sonic\Database\Sync;
use Sonic\Database\Factory, Sonic\Object\DefinitionFactory, Sonic\Database\Sync, Sonic\Database\Query;

/**
 * db-shards extension
 *
 * @category Extensions
 * @package db-shards
 * @subpackage Dao
 * @author Craig Campbell
 */
class Dao
{
    /**
     * @var string
     */
    protected $_schema;

    /**
     * sets schema
     *
     * @param string $schema
     * @return void
     */
    public function setSchema($schema)
    {
        $this->_schema = $schema;
    }

    /**
     * gets schema
     *
     * @return string
     */
    public function getSchema()
    {
        return $this->_schema;
    }

    /**
     * creates tables needed to store objects
     *
     * @param array $tables
     * @return void
     */
    public function createObjectTables(array $tables)
    {
        foreach ($tables as $table) {
            $this->createObjectTable($table);
        }
    }

    /**
     * creates a single object table
     *
     * @param string $table
     * @return void
     */
    public function createObjectTable($table)
    {
        if ($this->tableExists($table)) {
            return;
        }

        Sync::output('creating table "' . $table . '" in database: ' . $this->getSchema());

        $sql = '/* ' . __METHOD__ . ' */' . "\n" .
            "CREATE TABLE `{$table}` (
                `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `body` MEDIUMBLOB NOT NULL,
                `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                `updated` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                INDEX `created_idx` (`created`),
                INDEX `updated_idx` (`updated`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        $query = new Query($sql, $this->getSchema());

        return Sync::execute($query);
    }

    /**
     * checks if a table exists
     *
     * @param string $table
     * @return bool
     */
    public function tableExists($table)
    {
        $sql = '/* ' . __METHOD__ . ' */' . "\n" .
            "SELECT count(*) num_tables
               FROM information_schema.tables
              WHERE table_schema = :table_schema
                AND table_name = :table_name";

        $query = new Query($sql);
        $query->bindValue(':table_schema', $this->getSchema());
        $query->bindValue(':table_name', $table);

        $tables = $query->fetchValue();

        return $tables == 1;
    }

    /**
     * creates tables for all the given indexes
     *
     * @param array $indexes
     * @return void
     */
    public function createIndexTables(array $indexes)
    {
        foreach ($indexes as $table => $index) {
            $this->_createIndexTable($table, $index);
        }
    }

    /**
     * creates a single table
     *
     * @param string $table
     * @param array $definition
     * @return void
     */
    protected function _createIndexTable($table, $definition)
    {
        if ($this->tableExists($table)) {
            return;
        }

        $create_sql = Sync::getCreateField($definition['column'], $definition, $table);

        Sync::output('creating table: "' . $table . '" in database: ' . $this->getSchema());

        $sql = '/* ' . __METHOD__ . ' */' . "\n" .
            "CREATE TABLE {$table} (
                {$create_sql},
                `object_id` VARCHAR(16) NOT NULL,
                PRIMARY KEY (`{$definition['column']}`, `object_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        $query = new Query($sql, $this->getSchema());
        Sync::execute($query);
    }
}
