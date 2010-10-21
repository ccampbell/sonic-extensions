<?php
namespace Sonic\Database;
use Sonic\App;

/**
 * db-shards extension
 *
 * @category Extensions
 * @package db-shards
 * @subpackage ShardSelector
 * @author Craig Campbell
 */
class ShardSelector
{
    public static function random($num_shards = null)
    {
        if ($num_shards === null) {
            $num_shards = self::getShardCount();
        }
        return mt_rand(0, $num_shards - 1);
    }

    public static function getByKey($key, $count = null)
    {
        if ($count === null) {
            $count = self::getShardCount();
        }

        $hex = substr(md5(crc32($key)), 0, 8);
        $percent = $hex % 100;
        $groups = 100 / $count;

        $shard = 0;
        while ($shard < $percent) {
            $shard += $groups;
        }

        return floor($shard / $groups);
    }

    public static function getShards()
    {
        return App::getConfig()->get('db.shards');
    }

    public static function getShardByIndex($index)
    {
        $shards = self::getShards();
        return $shards[$index];
    }

    public static function getShardCount()
    {
        return count(self::getShards());
    }
}
