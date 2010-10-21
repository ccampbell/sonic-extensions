<?php
namespace Sonic\Database;
use Sonic\Database;
use Sonic\Database\Sync\Dao;
use Sonic\App;
use Sonic\Object\DefinitionFactory;

/**
 * db-shards extension
 *
 * @category Extensions
 * @package db-shards
 * @subpackage Sync
 * @author Craig Campbell
 */
class Sync extends Sync
{
    /**
     * runs the special db sync for sharding
     *
     * @return void
     */
    public static function run()
    {
        $dao = new Dao();
        $config = App::getConfig();
        $shards = $config->get('db.shards');

        $indexes_to_create = self::_getIndexesToCreate();
        $tables_to_create = self::_getTablesToCreate();

        foreach ($shards as $schema) {
            $dao->setSchema($schema);
            $dao->createObjectTables($tables_to_create);
            $dao->createIndexTables($indexes_to_create);
        }
    }

    /**
     * gets a list of tables that need to be created based on the definitions
     *
     * @return array
     */
    protected static function _getTablesToCreate()
    {
        $definitions = DefinitionFactory::getDefinitions();
        $tables = array();
        foreach ($definitions as $definition) {
            $tables[] = $definition['table'];
        }
        return $tables;
    }

    /**
     * gets a list of indexes that need to be created
     *
     * @return array
     */
    protected static function _getIndexesToCreate()
    {
        $definitions = DefinitionFactory::getDefinitions();
        $indexes = array();
        foreach ($definitions as $definition) {
            $table = $definition['table'];
            foreach ($definition['columns'] as $column => $info) {
                if (isset($info['indexed']) && $info['indexed']) {
                    $info['column'] = $column;
                    $indexes[$table . '_' . $column] = $info;
                }
            }
        }
        return $indexes;
    }
}
