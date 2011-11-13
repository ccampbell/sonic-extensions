<?php
namespace Sonic\Database;
use Sonic\Util;
use Sonic\App;
use Sonic\Cache;

/**
 * QueryCached Class
 *
 * @category Sonic
 * @package Database
 * @subpackage Query
 * @author Craig Campbell
 */
class QueryCached extends Query
{
    /**
     * @var string
     */
    protected $_cache_key;

    /**
     * @var int
     */
    protected $_cache_time;

    /**
     * @var bool
     */
    protected $_in_cache;

    /**
     * @var mixed
     */
    protected $_cached_value;

    /**
     * @var string
     */
    protected $_cache_pool = 'default';

    /**
     * constructor
     *
     * @param string $sql
     * @param string $schema
     * @return void
     */
    public function __construct($sql, $cache_key, $time = 7200, $schema = null)
    {
        parent::__construct($sql, $schema);
        $this->_cache_key = $cache_key;

        $app = App::getInstance();
        if (!$app->extensionLoaded('Cache')) {
            throw new Exception('QueryCached depends on the Cache extension. It is not currently loaded.');
        }

        $app->includeFile('Sonic/Util.php');
        $this->_cache_time = Util::toSeconds($time);
    }

    /**
     * sets cache pool to use for this query
     *
     * @param string $pool
     * @return void
     */
    public function setCachePool($pool)
    {
        $this->_cache_pool = $pool;
    }

    /**
     * determines if this query is in cache or not
     *
     * @return bool
     */
    public function wasInCache()
    {
        if ($this->_in_cache !== null) {
            return $this->_in_cache;
        }

        $cache = Cache::getCache($this->_cache_pool);
        $data = $cache->get($this->_cache_key);

        if ($data === false) {
            $this->_in_cache = false;
            return false;
        }

        $this->_cached_value = $data;
        $this->_in_cache = true;
        return true;
    }

    /**
     * caches data for this cache key
     *
     * @param mixed $data
     * @return void
     */
    protected function _cache($data)
    {
        Cache::getCache($this->_cache_pool)->set($this->_cache_key, $data, $this->_cache_time);
        $this->_cached_value = $data;
    }

    /**
     * fetch value
     *
     * @return mixed
     */
    public function fetchValue()
    {
        if ($this->_filter !== null || $this->_sort !== null) {
            return parent::fetchValue();
        }

        if (!$this->wasInCache()) {
            $this->_cache(parent::fetchValue());
        }
        return $this->_cached_value;
    }

    /**
     * fetch row
     *
     * @return mixed
     */
    public function fetchRow()
    {
        if ($this->_filter !== null || $this->_sort !== null) {
            return parent::fetchRow();
        }

        if (!$this->wasInCache()) {
            $this->_cache(parent::fetchRow());
        }
        return $this->_cached_value;
    }

    /**
     * fetch object
     *
     * @return Object
     */
    public function fetchObject($class)
    {
        if (!$this->wasInCache()) {
            $this->_cache(parent::fetchObject($class));
        }
        return $this->_cached_value;
    }

    /**
     * fetch all
     *
     * @return array
     */
    public function fetchAll()
    {
        if (!$this->wasInCache()) {
            $this->_cache(parent::_fetchAll());
        }
        $results = $this->_cached_value;
        $results = $this->_filter($results);
        $results = $this->_sort($results);

        return $results;
    }
}
