<?php
namespace Sonic;
use Sonic\Database\Query;
use Sonic\Database\QueryAsync;
use Sonic\App;
use Sonic\Database\ShardSelector;

/**
 * db-shards extension
 *
 * @category Extensions
 * @package db-shards
 * @subpackage Object
 * @author Craig Campbell
 */
abstract class Object extends Object
{
    protected $_indexed_properties;
    protected $_shard_id;

    /**
     * gets multiple objects by ids
     *
     * @todo implement
     */
    protected static function _getMultiple(array $ids)
    {
    }

    /**
     * gets a single object
     *
     * @param mixed $value
     * @param string $column
     * @return Object
     */
    protected static function _getSingle($column, $value)
    {
    }

    /**
     * saves or updates an object
     *
     * @return void
     */
    public function save()
    {
        if ($this->id === null) {
            return $this->_add();
        }

        return $this->_update();
    }

    protected function _getIndexedProperties()
    {
        if ($this->_indexed_properties !== null) {
            return $this->_indexed_properties;
        }

        $definition = self::getDefinition();

        $properties = array();
        foreach ($definition['columns'] as $column => $info) {
            if (!isset($info['indexed'])) {
                continue;
            }

            if ($info['indexed'] === false) {
                continue;
            }

            $properties[] = $column;
        }

        $this->_indexed_properties = $properties;
        return $this->_indexed_properties;
    }

    /**
     * adds an object to the database
     *
     * @return bool
     */
    protected function _add()
    {
        $shard_number = ShardSelector::random();
        $shard_name = ShardSelector::getShardByIndex($shard_number);

        $definition = self::getDefinition();
        $sql = "INSERT INTO " . $definition['table'] . " (body, created) VALUES (:body, NOW())";
        $query = new Query($sql, $shard_name);

        $this->reset();
        $query->bindValue(':body', gzcompress(serialize($this), 9));
        $query->execute();
        $this->id = $query->lastInsertId();
        $this->_shard_id = $shard_number;

        // index this object
        $indexes = $this->_getIndexedProperties();
        $index_id = $shard_number . '.' . $this->id;
        foreach ($indexes as $index) {
            $shard_number = ShardSelector::getByKey($this->$index);
            $shard_name = ShardSelector::getShardByIndex($shard_number);
            $sql = "INSERT INTO " . $definition['table'] . '_' . $index . " (" . $index . ", object_id) VALUES (:" . $index . ", :object_id)";
            $query = new QueryAsync($sql, $shard_name);
            $query->bindValue(':' . $index, $this->$index);
            $query->bindValue(':object_id', $index_id);
        }
        $query->execute();
    }

    /**
     * updates an object in the database
     *
     * @return bool
     */
    protected function _update()
    {
    }

    /**
     * permanently delete an object from the database
     *
     * WARNING!!! BE CAREFUL WITH THIS!!!
     *
     * you can easily use soft deletes by adding an "is_deleted" column in your definitions
     *
     * @return bool
     */
    public function delete()
    {
    }

    /**
     * puts this object into cache
     *
     * @return void
     */
    protected function _cache()
    {
        $cache_key = self::_getCacheKey('id', $this->_shard_id . '.' . $this->id);
        App::getMemcache()->set($cache_key, $this, '1 week');
    }
}
