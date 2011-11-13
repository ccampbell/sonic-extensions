<?php
namespace Sonic\Cache;
use Sonic\Util;

/**
 * Memcached
 *
 * @category Sonic
 * @package Cache
 * @author Craig Campbell
 */
class Memcached
{
    /**
     * @var Memcached
     */
    protected $_memcached;

    /**
     * constructs a new Memcached class
     *
     * pass in an array formatted like this:
     *
     * array(
     *     0 => array('host' => '127.0.0.1', 'port' => '11211'),
     *     1 => array('host' => '127.0.0.1', 'port' => '11311')
     * )
     *
     * @param array $servers
     * @param string $pool
     * @return void
     */
    public function __construct(array $servers, $pool = null)
    {
        $this->_memcached = new \Memcached($pool);
        foreach ($servers as $server) {
            $this->_addServer($server['host'], $server['port']);
        }
    }

    /**
     * adds a server to the pool
     *
     * @param string $host
     * @param int $port
     */
    protected function _addServer($host, $port)
    {
        try {
            $this->_memcached->addServer($host, $port);
        } catch (\Exception $e) {

        }
    }

    /**
     * sets a key in cache
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return bool
     */
    public function set($key, $value, $ttl = 7200)
    {
        $ttl = Util::toSeconds($ttl);
        return $this->_memcached->set($key, $value, $ttl);
    }

    /**
     * gets a key from cache
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->_memcached->get($key);
    }

    /**
     * determines if the last requested key was found in cache
     *
     * @return bool
     */
    public function wasFound()
    {
        return $this->_memcached->getResultCode() != \Memcached::RES_NOTFOUND;
    }

    /**
     * multigets an array of keys from cache
     *
     * @param array $keys
     * @return array
     */
    public function getMulti(array $keys)
    {
        // grab whatever items we can from cache
        $items = $this->_memcached->getMulti($keys) ?: array();

        // set all keys to null so if something is not found in cache it won't
        // be set to a value
        $results = array_fill_keys($keys, null);

        // merge the two arrays so the returned values from cache overwrite
        // the starting values
        return array_merge($results, $items);
    }

    /**
     * deletes a key from cache
     *
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        return $this->_memcached->delete($key);
    }

    /**
     * sets options
     *
     * @param array $options
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $this->_memcached->setOption($key, $value);
        }
    }

    /**
     * gets memcached object
     *
     * @return Memcached
     */
    public function getMemcached()
    {
        return $this->_memcached;
    }
}
