<?php
namespace Sonic;
use Sonic\App;

/**
 * CurlCached
 *
 * @category Sonic
 * @package Curl
 * @author Craig Campbell <iamcraigcampbell@gmail.com>
 */
class CurlCached extends Curl
{
    /**
     * @var string
     */
    protected $_cache_key;

    /**
     * @var int
     */
    protected $_ttl;

    /**
     * constructor
     *
     * @param string $url
     * @param string $cache_key
     * @param int $ttl
     */
    public function __construct($url, $cache_key, $ttl = 7200)
    {
        parent::__construct($url);
        $this->_cache_key = $cache_key;
        $this->_ttl = $ttl;
    }

    /**
     * gets cached response
     *
     * @return string || false on failure
     */
    public function getResponse()
    {
        $cache = App::getMemcache();
        $result = $cache->get($this->_cache_key);
        if ($result !== false) {
            return $result;
        }

        $response = parent::getResponse();
        $cache->set($this->_cache_key, $response, $this->_ttl);

        return $response;
    }
}
