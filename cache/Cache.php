<?php
namespace Sonic;
use Sonic\Cache\Memcache, Sonic\Cache\Memcached, Sonic\App;

/**
 * Cache
 *
 * @category Sonic
 * @package Cache
 * @author Craig Campbell <iamcraigcampbell@gmail.com>
 */
class Cache
{
    const SETTING_ENABLED = 'enabled';
    const DEFAULT_CACHE_POOL = 'default';
    const DEFAULT_FILE_CACHE_PATH = 'caches';
    const SETTING_CLASS_PREFERENCE = 1;
    const MEMCACHE = 2;
    const MEMCACHED = 3;

    /**
     * @var array
     */
    protected static $_caches_memcache = array();

    /**
     * @var array
     */
    protected static $_caches_memcached = array();

    /**
     * @var array
     */
    protected static $_servers = array();

    /**
     * gets Memcache or Memcached depending on setting preference
     *
     * @param string $pool
     * @return Memcache || Memcached
     */
    public static function getCache($pool = self::DEFAULT_CACHE_POOL)
    {
        $preference = App::getInstance()->extension('Cache')->getSetting(self::SETTING_CLASS_PREFERENCE);
        switch ($preference) {
            case self::MEMCACHED:
                $cache = self::getMemcached($pool);
                break;
            default:
                $cache = self::getMemcache($pool);
                break;
        }

        return $cache;
    }

    /**
     * gets memcache object for a specific pool
     *
     * @param string $pool
     * @return Memcache
     */
    public static function getMemcache($pool = self::DEFAULT_CACHE_POOL)
    {
        if (isset(self::$_caches_memcache[$pool])) {
            return self::$_caches_memcache[$pool];
        }

        if (!self::getConfig()->get(self::SETTING_ENABLED)) {
            self::$_caches_memcache[$pool] = new Disabled();
            return self::$_caches_memcache[$pool];
        }

        $servers = self::getServers($pool);
        self::$_caches_memcache[$pool] = new Memcache($servers);

        return self::$_caches_memcache[$pool];
    }

    /**
     * gets memcached object for a specific pool
     *
     * @param string $pool
     * @return Memcached
     */
    public static function getMemcached($pool = self::DEFAULT_CACHE_POOL)
    {
        if (isset(self::$_caches_memcached[$pool])) {
            return self::$_caches_memcached[$pool];
        }

        if (!self::getConfig()->get(self::SETTING_ENABLED)) {
            self::$_caches_memcached[$pool] = new Disabled();
            return self::$_caches_memcached[$pool];
        }

        $servers = self::getServers($pool);
        self::$_caches_memcached[$pool] = new Memcached($servers, $pool);

        return self::$_caches_memcached[$pool];
    }

    /**
     * gets file cache object
     *
     * @param string $path path to cache to
     * @return File
     */
    public static function getFile($path = self::DEFAULT_FILE_CACHE_PATH)
    {
        $path = App::getInstance()->getPath($path);
        return new File($path);
    }

    /**
     * gets memcache servers for a specific pool
     *
     * @param string $pool
     * @return array
     */
    public static function getServers($pool = 'default')
    {
        if (isset(self::$_servers[$pool])) {
            return self::$_servers[$pool];
        }

        $servers = self::getConfig()->get('cache.' . $pool);
        if ($servers === null) {
            throw new Exception('no servers found for cache pool: ' . $pool);
        }

        self::$_servers[$pool] = array();
        foreach ($servers as $server) {
            $bits = explode(':', $server);
            $server = array();
            $server['host'] = $bits[0];
            $server['port'] = $bits[1];

            self::$_servers[$pool][] = $server;
        }

        return self::$_servers[$pool];
    }

    /**
     * gets cache config
     *
     * @return Sonic\Config
     */
    public static function getConfig()
    {
        return App::getInstance()->extension('Cache')->getConfig();
    }
}
